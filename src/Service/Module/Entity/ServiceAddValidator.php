<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

class ServiceAddValidator implements ServiceAddToClassInterface
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
        foreach ($dataColumn as $item) {
            if (empty($item['validator'])) {
                continue;
            }
            $this->addValidator(
                $class->getProperty(lcfirst($item['camel_case_name'])),
                $item['validator']
            );
        }

        return $class;
    }

    private function addValidator(Property $property, array $validators)
    {
        foreach ($validators as $validator => $validatorParams) {
            if (is_string($validatorParams)) {
                $validatorParams = [$validatorParams];
            }
            $property->addAttribute('Assert\\' . $validator, $validatorParams ?? []);
        }
    }
}