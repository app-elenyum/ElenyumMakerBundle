<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;

interface ServiceAddToClassInterface
{
    /**
     * @param ClassType $class
     * @param array $data
     * @return ClassType
     */
    public function create(ClassType $class, array $data): ClassType;
}