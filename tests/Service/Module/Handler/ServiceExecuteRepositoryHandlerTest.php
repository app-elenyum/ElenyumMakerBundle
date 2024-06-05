<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Handler\ServiceExecuteRepositoryHandler;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use PHPUnit\Framework\TestCase;

class ServiceExecuteRepositoryHandlerTest extends TestCase
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
                'namespace' => 'App\Namespace'
            ]
        ];

        $this->service = new ServiceExecuteRepositoryHandler($this->filesystemMock, $this->options);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $service = new ServiceExecuteRepositoryHandler($this->filesystemMock, []);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = ['root' => ['namespace' => 'App\Namespace']];
        $service = new ServiceExecuteRepositoryHandler($this->filesystemMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenNamespaceOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = ['root' => ['path' => '/some/path']];
        $service = new ServiceExecuteRepositoryHandler($this->filesystemMock, $options);
        $service->execute(['version_namespace' => 'v1', 'entity_name' => 'Entity', 'module_name' => 'Module']);
    }

    public function testExecuteCreatesAndUpdatesRepositoryFile()
    {
        $data = [
            'version_namespace' => 'v1_0',
            'entity_name' => 'Entity',
            'module_name' => 'Module',
            'version' => 'v1.0'
        ];
        $dirRepositoryFile = '/some/path/Module/v1_0/Repository/EntityRepository.php';

        // Mock the createRepository and printNamespace methods
        $namespace = new PhpNamespace('App\Namespace\Module\v1_0\Repository');

        // Spy on the filesystem dumpFile method
        $this->filesystemMock->expects($this->once())
            ->method('dumpFile');

        $result = $this->service->execute($data);

        $expectedResult = [[
            'module' => 'Module',
            'version' => 'v1.0',
            'operation' => 'created',
            'type' => 'repository',
            'file' => $dirRepositoryFile,
        ]];

        $this->assertSame($expectedResult, $result);
    }
}