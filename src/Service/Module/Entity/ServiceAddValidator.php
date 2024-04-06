<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;

class ServiceAddValidator implements ServiceAddToClass
{
    public function create(ClassType $class, array $dataColumn): ClassType
    {
        foreach ($dataColumn as $item) {
            if (empty($item['validator'])) {
                continue;
            }
            $this->addValidator(
                $class->getProperty($item['name']),
                $item['validator']
            );
        }

        return $class;
    }

    private function addValidator(Property $property, array $validators)
    {
        foreach ($validators as $validator => $validatorParams) {
            $property->addAttribute('Assert\\' . $validator, $validatorParams ?? []);
        }
    }
}