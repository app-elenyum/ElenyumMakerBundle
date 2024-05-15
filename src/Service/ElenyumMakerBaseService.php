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
     * @param array|null $fields
     *
     * @return array
     *
     * @throws EntityNotImplementAbstractEntityException
     * @throws NotFoundByIdException
     * @throws Exception
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

        return $item->toArray($groups, $fields);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param array $orderBy
     * @param array $groups
     * @param array $filter
     * @param array $fields
     * @return array
     * @throws Exception
     */
    public function getList(
        int $offset,
        int $limit,
        array $orderBy,
        array $groups,
        array $filter = [],
        array $fields = []
    ): array {
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

    /**
     * @param object $entity
     * @param bool $isInner
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotFoundByIdException
     */
    private function findOrPersist(object &$entity, bool $isInner = false): void
    {
        $entityId = $entity->id ?? null;
        if ($isInner && $entityId !== null) {
            $entity = $this->getEntityManager()->find($entity::class, $entityId);
            if ($entity === null) {
                throw new NotFoundByIdException($entityId, $this->getRepository()->getClassName());
            }
            return;
        }
        foreach ($entity as $key => &$item) {
            if (is_object($item)) {
                if ($item instanceof Collection && $item->count() > 0) {
                    $cloneCollection = clone $item;
                    $item->clear();

                    foreach ($cloneCollection as &$cEntity) {
                        $this->findOrPersist($cEntity, true);

                        $entity->{'add'.ucfirst($key)}($cEntity);
                    }
                } elseif (!$item instanceof Collection) {
                    $this->findOrPersist($item, true);
                }
            }
        }

        if ($entityId === null) {
            $this->getEntityManager()->persist($entity);
        } else {
            $entity = $this->getEntityManager()->find($entity::class, $entityId);
            if ($entity === null) {
                throw new NotFoundByIdException($entityId, $this->getRepository()->getClassName());
            }
        }
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
     * @throws ValidationException|NotFoundByIdException
     */
    public function add(mixed $data, array $groups = [], array $outputGroups = []): array
    {
        $entityResult = $this->serializeDataToEntity(data: $data, groups: $groups);
        $validate = $this->validate($entityResult);
        if (!empty($validate)) {
            throw new ValidationException($validate);
        }
        $entityId = $entityResult->id ?? null;
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