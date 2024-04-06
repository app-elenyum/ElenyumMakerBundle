<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;

interface ServiceAddToClass
{
    /**
     * @param ClassType $class
     * @param array $dataColumn
     * @return ClassType
     */
    public function create(ClassType $class, array $dataColumn): ClassType;
}