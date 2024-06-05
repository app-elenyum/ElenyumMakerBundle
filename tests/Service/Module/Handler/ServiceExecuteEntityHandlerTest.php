<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Countable;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteEntityHandler;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteEntityHandlerTest extends TestCase
{
    private $filesystemMock;
    private $entityServicesMock;
    private $options;
    private $service;

    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->entityServicesMock = $this->createMock(Countable::class);
        $this->options = [
            'root' => [
                'path' => '/some/path',
                'namespace' => 'App\\Namespace'
            ]
        ];

        $this->service = new ServiceExecuteEntityHandler($this->filesystemMock, $this->entityServicesMock, $this->options);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $service = new ServiceExecuteEntityHandler($this->filesystemMock, $this->entityServicesMock, []);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = ['root' => ['namespace' => 'App\\Namespace']];
        $service = new ServiceExecuteEntityHandler($this->filesystemMock, $this->entityServicesMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenNamespaceOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = ['root' => ['path' => '/some/path']];
        $service = new ServiceExecuteEntityHandler($this->filesystemMock, $this->entityServicesMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteCreatesAndUpdatesEntityFile()
    {
        $data = [
            'version_namespace' => 'v1',
            'entity_name' => 'Entity',
            'module_name' => 'Module',
            'version' => 'v1.0',
            'validator' => [],
        ];
        $dirEntityFile = '/some/path/Module/v1/Entity/Entity.php';

        // Mock the createEntity and printNamespace methods
        $namespace = new PhpNamespace('App\\Namespace\\Module\\v1_0\\Entity');
        $namespace->addClass('Entity');

        // Spy on the filesystem dumpFile method
        $this->filesystemMock->expects($this->once())
            ->method('dumpFile');

        $result = $this->service->execute($data);

        $expectedResult = [[
            'module' => 'Module',
            'version' => 'v1.0',
            'operation' => 'created',
            'type' => 'entity',
            'file' => $dirEntityFile,
            'entityPath' => '/some/path/Module/v1/Entity',
        ]];

        $this->assertSame($expectedResult, $result);
    }

    public function testPrepareTableName()
    {
        $method = new \ReflectionMethod($this->service, 'prepareTableName');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'ModuleName', 'EntityName', 'v1_0');
        $this->assertEquals('entity_name__v1_0__modulename', $result);
    }
}
