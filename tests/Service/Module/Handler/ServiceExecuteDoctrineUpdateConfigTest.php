<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Config\ConfigEditorService;
use Elenyum\Maker\Service\Module\Config\DoctrineConfig;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteDoctrineUpdateConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteDoctrineUpdateConfigTest extends TestCase
{
    public function testExecuteThrowsMissingOptionsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $service = new ServiceExecuteDoctrineUpdateConfig(new DoctrineConfig([]));
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = new DoctrineConfig(['root' => ['namespace' => 'App\\Namespace']]);
        $service = new ServiceExecuteDoctrineUpdateConfig($options);
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenNamespaceOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = new DoctrineConfig(['root' => ['path' => '/some/path']]);
        $service = new ServiceExecuteDoctrineUpdateConfig($options);
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }
}
