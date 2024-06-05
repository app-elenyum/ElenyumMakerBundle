<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Handler\ServiceExecuteServiceHandler;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteServiceHandlerTest extends TestCase
{
    private $filesystemMock;
    private $options;
    private $service;

    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->options = [
            'root' => [
                'path' => '/some/path',
                'namespace' => 'App\\Test'
            ]
        ];

        $this->service = new ServiceExecuteServiceHandler($this->filesystemMock, $this->options);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $service = new ServiceExecuteServiceHandler($this->filesystemMock, []);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = ['root' => ['namespace' => 'App\\Namespace']];
        $service = new ServiceExecuteServiceHandler($this->filesystemMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenNamespaceOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = ['root' => ['path' => '/some/path']];
        $service = new ServiceExecuteServiceHandler($this->filesystemMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteCreatesAndUpdatesServiceFile()
    {
        $data = [
            'version_namespace' => 'v1_0',
            'entity_name' => 'Entity',
            'module_name' => 'Module',
            'version' => 'v1.0'
        ];
        $dirServiceFile = '/some/path/Module/v1_0/Service/EntityService.php';

        // Mock the createService and printNamespace methods
        $namespace = new PhpNamespace('App\Test\Module\v1_0\Service');

        // Spy on the filesystem dumpFile method
        $this->filesystemMock->expects($this->once())
            ->method('dumpFile');

        $result = $this->service->execute($data);

        $expectedResult = [[
            'module' => 'Module',
            'version' => 'v1.0',
            'operation' => 'created',
            'type' => 'service',
            'file' => $dirServiceFile,
        ]];

        $this->assertSame($expectedResult, $result);
    }
}