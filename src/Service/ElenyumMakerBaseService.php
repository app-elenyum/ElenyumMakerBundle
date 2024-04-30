<?php

namespace Elenyum\Maker\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Elenyum\Maker\Entity\AbstractEntity;
use Elenyum\Maker\Exception\EntityNotImplementAbstractEntityException;
use Elenyum\Maker\Exception\NotFoundByIdException;
use Elenyum\Maker\Exception\ValidationException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ElenyumMakerBaseService extends AbstractService
{
    /**
     * @param int $id
     * @param array $groups
     * @param array $fields
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getOne(int $id, array $groups, ?array $fields = null): array
    {
        /** @var ?AbstractEntity $item */
        $item = $this->getRepository()->findOneBy(['id' => $id]);

        if ($item === null) {
            throw new NotFoundByIdException($id, $this->getRepository()->getClassName());
        }
        if (!$item instanceof AbstractEntity) {
            throw new EntityNotImplementAbstractEntityException($this->getRepository()->getClassName());
        }
//        if ($this->getUser() !== null) {
//            $this->isGranted('GET', $item);
//        }
        return $item->toArray($groups, $fields);
    }

    public function getList(
        int $offset,
        int $limit,
        array $orderBy,
        array $groups,
        array $filter = [],
        array $fields = []
    ) {
        $total = $this->getRepository()->count($filter);
        $items = $this->getRepository()->findBy($filter, $orderBy, $limit, $offset);

        $result = [];
        /** @var AbstractEntity $item */
        foreach ($items as $item) {
            $result[] = $item->toArray($groups, $fields);
        }

        return [$total, $result];
    }

    /**
     * @param string $json
     * @return string
     */
    public function prepareJsonFormat(string $json): string
    {
        // Удаляем все пробельные символы перед и после двоеточия
        $json = preg_replace('/\s*(:|,)\s*/', '$1', $json);

        // Добавляем кавычки вокруг ключей (предполагается, что ключи могут состоять из букв, цифр и символов подчеркивания)
        $json = preg_replace('/([{\[,]\s*)([a-zA-Z0-9_\.]+)/i', '$1"$2"$3', $json);

        // Добавляем двойные кавычки вокруг строковых значений, которые ещё не заключены в двойные кавычки, но могут быть в одинарных
        $json = preg_replace("/:(\s*)'([^']+)'(\s*[},\]])/", ':$1"$2"$3', $json);

        //Заменяем одинарные на двойные ковычки
        $json = preg_replace('/(\')([a-zA-Z0-9_]+)(\')/', '"$2"', $json);

        // Обрабатываем случаи, где значения не заключены ни в какие кавычки
        return preg_replace('/:(\s*)([a-zA-Z0-9_]+)(\s*[},\]])/', ':$1"$2"$3', $json);
    }

    /** todo проблема может быть если связали с одним потом связываем с другим
     * @param object $entity
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function findOrPersist(object &$entity): object
    {
        $entityId = $entity->id ?? null;
        foreach ($entity as $key => &$item) {
            if (is_object($item)) {
                if ($item instanceof Collection && $item->count() > 0) {
                    $cloneCollection = clone $item;
                    $item->clear();
                    foreach ($cloneCollection as &$cEntity) {
                        $cEntity = $this->findOrPersist($cEntity);
                        $entity->{'add'.ucfirst($key)}($cEntity);
                    }
                } else {
                    $id = $item->id ?? null;
                    if ($id !== null) {
                        $item = $this->getEntityManager()->find($item::class, $id);
                    } else {
                        $this->getEntityManager()->persist($item);
                    }
                }
            }
        }

        if ($entityId === null) {
            $this->getEntityManager()->persist($entity);
        } else {
            $entity = $this->getEntityManager()->find($entity::class, $entityId);
        }

        return $entity;
    }

    /**
     * @param mixed $data
     * @param array $groups
     * @param array $outputGroups
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ValidationException
     */
    public function add(mixed $data, array $groups = [], array $outputGroups = []): array
    {
        $entityResult = $this->serializeDataToEntity(data: $data, groups: $groups);
        $validate = $this->validate($entityResult);
        if (!empty($validate)) {
            throw new ValidationException($validate);
        }
        $entityId = $entity->id ?? null;
        if ($entityId !== null) {
            throw new Exception(
                'Cannot use the \'id\' key if you want to add an entity. Please use the "PUT" method for updates or remove the \'id\' key from your request.'
            );
        }
        // need for input elements
        $this->findOrPersist($entityResult);

        $this->getEntityManager()->flush();

        return $entityResult->toArray($outputGroups);
    }

    /**
     * @param string $data
     * @param int $id
     * @param array $groups
     * @param array $outputGroups
     * @return array
     * @throws ContainerExceptionInterface
     * @throws EntityNotImplementAbstractEntityException
     * @throws NotFoundByIdException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ValidationException
     */
    public function update(string $data, int $id, array $groups = [], array $outputGroups = []): array
    {
        $item = $this->getRepository()->findOneBy(['id' => $id]);
        if ($item === null) {
            throw new NotFoundByIdException($id, $this->getRepository()->getClassName());
        }
        if (!$item instanceof AbstractEntity) {
            throw new EntityNotImplementAbstractEntityException($this->getRepository()->getClassName());
        }
        $entityResult = $this->serializeDataToEntity(data: $data, entity: $item, groups: $groups);
        $validate = $this->validate($entityResult);
        if (!empty($validate)) {
            throw new ValidationException($validate);
        }
        $this->findOrPersist($entityResult);
        $this->getEntityManager()->flush();

        return $entityResult->toArray($outputGroups);
    }

    /**
     * @param int $id
     * @param array $groups
     * @return array
     * @throws ContainerExceptionInterface
     * @throws EntityNotImplementAbstractEntityException
     * @throws NotFoundByIdException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function delete(int $id, array $groups = []): array
    {
        $entity = $this->getRepository()->findOneBy(['id' => $id]);
        if ($entity === null) {
            throw new NotFoundByIdException($id, $this->getRepository()->getClassName());
        }
        if (!$entity instanceof AbstractEntity) {
            throw new EntityNotImplementAbstractEntityException($this->getRepository()->getClassName());
        }
        $result = $entity->toArray($groups);

        $this->getEntityManager()->remove($entity);

        return $result;
    }
}