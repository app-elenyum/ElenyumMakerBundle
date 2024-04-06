<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;

class ServiceAddGroup implements ServiceAddToClass
{
    /**
     * @param ClassType $class
     * @param array $dataColumn
     * @return ClassType
     */
    public function create(ClassType $class, array $dataColumn): ClassType
    {
        foreach ($dataColumn as $item) {
            if (empty($item['group'])) {
                continue;
            }
            $this->addGroup(
                $class->getProperty($item['name']),
                $item['group']
            );
        }

        return $class;
    }

    /**
     * @param Property $property
     * @param array $groups
     * @return void
     */
    private function addGroup(Property $property, array $groups): void
    {
        $groups = $this->prepareGroup($groups);

        $property->addAttribute('Groups', $groups);
    }

    /**
     * @param array $groups
     * @return array
     */
    private function prepareGroup(array $groups): array
    {
        $result = [];
        foreach ($groups as $type => $group) {
            $result = array_merge($result, array_map(fn($g) => $type.'_'.$g, $group));
        }

        return $result;
    }
}