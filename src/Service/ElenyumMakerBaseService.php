<?php

namespace Elenyum\Maker\Service;

use Doctrine\Common\Collections\Collection;
use Elenyum\Maker\Entity\AbstractEntity;
use Elenyum\Maker\Exception\ValidationException;
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
        /** @var AbstractEntity $item */
        $item = $this->getRepository()->findOneBy(['id' => $id]);
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
        $items = $this->getRepository()->findBy($filter, $orderBy, $limit, $offset);

        $result = [];
        /** @var AbstractEntity $item */
        foreach ($items as $item) {
            $result[] = $item->toArray($groups, $fields);
        }

        return $result;
    }

    /** todo проблема может быть если связали с одним потом связываем с другим */
    private function findOrPersist(&$entity)
    {
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

        $entityId = $entity->id ?? null;
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
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     */
    public function add(mixed $data, array $groups = []): array
    {
        $entityResult = $this->serializeDataToEntity(data: $data, groups: $groups);
        $validate = $this->validate($entityResult);
        if (!empty($validate)) {
            throw new ValidationException($validate);
        }
        $this->findOrPersist($entityResult);

        $this->getEntityManager()->flush();

        return $entityResult->toArray($groups);
    }

    /**
     * @param string $data
     * @param int $id
     * @param array $groups
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     */
    public function update(string $data, int $id, array $groups = []): array
    {
        $findEntity = $this->getRepository()->findOneBy(['id' => $id]);
        $entityResult = $this->serializeDataToEntity(data: $data, entity: $findEntity, groups: $groups);
        $validate = $this->validate($entityResult);
        if (!empty($validate)) {
            throw new ValidationException($validate);
        }
        $this->findOrPersist($entityResult);
        $this->getEntityManager()->flush();

        return $entityResult->toArray($groups);
    }
}