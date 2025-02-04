<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

class ServiceAddGroup implements ServiceAddToClassInterface
{
    /**
     * @param PhpNamespace $namespace
     * @param ClassType $class
     * @param array $data
     * @return ClassType
     */
    public function create(PhpNamespace $namespace, ClassType $class, array $data): ClassType
    {
        $dataColumn = $data['column'];
        if (!empty($data['group'])) {
            $class->addAttribute('Groups', [$data['group']]);
        }
        foreach ($dataColumn as $item) {
            $groups = $this->prepareGroup($data['group'], $item['group']);
            if (empty($groups)) {
                continue;
            }

            $this->addGroup(
                $class->getProperty(lcfirst($item['camel_case_name'])),
                $groups
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
        $property->addAttribute('Groups', [$groups]);
    }

    /**
     * @param array $entityGroup
     * @param array $groups
     * @return array|string[]
     */
    private function prepareGroup(array $entityGroup, array $groups): array
    {
        $result = [];
        foreach ($groups as $type => $group) {
            $result = array_merge($result, array_map(fn($g) => $type.'_'.$g, $group));
        }

        return !empty($entityGroup) ? $result : ['Default'];
    }
}