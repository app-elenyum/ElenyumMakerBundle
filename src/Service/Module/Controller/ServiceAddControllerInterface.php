<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\PhpNamespace;

interface ServiceAddControllerInterface
{
    /**
     * @param string $fullNamespace
     * @param string $service
     * @param string $entity
     * @param array $data
     * @param string|null $prefix
     * @return PhpNamespace
     */
    public function createController(string $fullNamespace, string $service, string $entity, array $data, ?string $prefix): PhpNamespace;

    /**
     * @param string $entityName
     * @return string
     */
    public function getName(string $entityName): string;
}