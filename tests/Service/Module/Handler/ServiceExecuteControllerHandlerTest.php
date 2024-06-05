<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Handler\ServiceExecuteControllerHandler;
use PHPUnit\Framework\TestCase;
use Countable;
use Elenyum\Maker\Service\Module\Controller\ServiceAddControllerInterface;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteControllerHandlerTest extends TestCase
{
    private $filesystem;
    private $controllerServices;
    private $options;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->controllerServices = $this->createMock(Countable::class);
        $this->options = [
            'root' => [
                'path' => '/some/path',
                'namespace' => 'App',
                'prefix' => 'Prefix'
            ]
        ];
        $this->handler = new ServiceExecuteControllerHandler($this->filesystem, $this->options, $this->controllerServices);
    }

    public function testExecuteThrowsMissingOptionsExceptionForRoot()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $handler = new ServiceExecuteControllerHandler($this->filesystem, [], $this->controllerServices);
        $handler->execute([]);
    }

    public function testExecuteThrowsMissingOptionsExceptionForPath()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = [
            'root' => [
                'namespace' => 'App',
                'prefix' => 'Prefix'
            ]
        ];
        $handler = new ServiceExecuteControllerHandler($this->filesystem, $options, $this->controllerServices);
        $handler->execute([]);
    }

    public function testExecuteThrowsMissingOptionsExceptionForNamespace()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = [
            'root' => [
                'path' => '/some/path',
                'prefix' => 'Prefix'
            ]
        ];
        $handler = new ServiceExecuteControllerHandler($this->filesystem, $options, $this->controllerServices);
        $handler->execute([]);
    }

    public function testExecuteCreatesAndUpdatesFiles()
    {
        $data = [
            'entity_name' => 'TestEntity',
            'version_namespace' => 'V1',
            'module_name' => 'TestModule',
            'version' => 'v1'
        ];

        $controllerService = $this->createMock(ServiceAddControllerInterface::class);
        $controllerService->expects($this->once())
            ->method('createController')
            ->willReturn(new PhpNamespace('TestNamespace'));
        $controllerService->expects($this->once())
            ->method('getName')
            ->willReturn('TestController');

        $this->controllerServices = new \ArrayIterator([$controllerService]);

        $this->filesystem->expects($this->once())
            ->method('dumpFile');

        $handler = new ServiceExecuteControllerHandler($this->filesystem, $this->options, $this->controllerServices);
        $result = $handler->execute($data);

        $this->assertCount(1, $result);
        $this->assertSame('TestModule', $result[0]['module']);
        $this->assertSame('v1', $result[0]['version']);
        $this->assertSame('created', $result[0]['operation']);
        $this->assertSame('controller', $result[0]['type']);

        $this->assertStringContainsString('/some/path/TestModule/V1/Controller/TestEntity/TestController.php', $result[0]['file']);
    }
}
