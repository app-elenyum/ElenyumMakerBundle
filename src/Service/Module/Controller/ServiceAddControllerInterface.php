<?php

namespace Elenyum\Maker\Service\Module\Controller;

use Nette\PhpGenerator\PhpNamespace;

interface ServiceAddControllerInterface
{
    /**
     * @param string $fullNamespace
     * @param array $data
     * @param string|null $prefix
     * @return PhpNamespace
     */
    public function createController(string $fullNamespace,  array $data, ?string $prefix): PhpNamespace;
}