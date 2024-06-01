<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;

class ServiceAddValidator implements ServiceAddToClassInterface
{
    public function create(ClassType $class, array $data): ClassType
    {
        $dataColumn = $data['column'];
        foreach ($dataColumn as $item) {
            if (empty($item['validator'])) {
                continue;
            }
            $this->addValidator(
                $class->getProperty(lcfirst($item['name'])),
                $item['validator']
            );
        }

        return $class;
    }

    private function addValidator(Property $property, array $validators)
    {
        foreach ($validators as $validator => $validatorParams) {
//            if (is_string($validatorParams)) {
//                $validatorParams = [$validatorParams];
//            }
            $property->addAttribute('Assert\\' . $validator, $validatorParams ?? []);
        }
    }
}