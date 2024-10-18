<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

interface ServiceAddToClassInterface
{
    /**
     * @param PhpNamespace $namespace
     * @param ClassType $class
     * @param array $data
     * @return ClassType
     */
    public function create(PhpNamespace $namespace, ClassType $class, array $data): ClassType;
}