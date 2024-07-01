<?php

namespace Elenyum\Maker\Tests\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Config\ConfigEditorService;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteDoctrineUpdateConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteDoctrineUpdateConfigTest extends TestCase
{
    private $options;
    private $service;

    protected function setUp(): void
    {
        $this->options = [
            'root' => [
                'path' => '/some/path',
                'namespace' => 'App\\Namespace'
            ]
        ];

        $this->service = new ServiceExecuteDoctrineUpdateConfig($this->options);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        $service = new ServiceExecuteDoctrineUpdateConfig([]);
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        $options = ['root' => ['namespace' => 'App\\Namespace']];
        $service = new ServiceExecuteDoctrineUpdateConfig($options);
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }

    public function testExecuteThrowsMissingOptionsExceptionWhenNamespaceOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "namespace" option');

        $options = ['root' => ['path' => '/some/path']];
        $service = new ServiceExecuteDoctrineUpdateConfig($options);
        $service->execute(['version_namespace' => 'v1', 'module_name' => 'Module']);
    }

    public function testExecuteUpdatesDoctrineConfig()
    {
        $data = [
            'version_namespace' => 'v1',
            'module_name' => 'Module',
            'version' => '1.0'
        ];

        $configFile = '/some/path/../config/packages/doctrine.yaml';
        $dirEntityFile = '/some/path/Module/v1/Entity';
        $fullNamespace = 'App\\Namespace\\Module\\V1\\Entity';
        $key = 'ModuleV1';

        $mockConfigEditorService = $this->createMock(ConfigEditorService::class);
        $mockConfigEditorService->expects($this->once())
            ->method('parse')
            ->willReturn(['doctrine' => ['orm' => ['entity_managers' => ['default' => ['mappings' => []]]]]]);

        $mockConfigEditorService->expects($this->once())
            ->method('save')
            ->with([
                'doctrine' => [
                    'orm' => [
                        'entity_managers' => [
                            'default' => [
                                'mappings' => [
                                    $key => [
                                        "is_bundle" => false,
                                        "type" => "attribute",
                                        "dir" => $dirEntityFile,
                                        "prefix" => $fullNamespace,
                                        "alias" => $key,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $service = $this->getMockBuilder(ServiceExecuteDoctrineUpdateConfig::class)
            ->setConstructorArgs([$this->options])
            ->onlyMethods(['createConfigEditorService'])
            ->getMock();

        $service->method('createConfigEditorService')
            ->with($configFile)
            ->willReturn($mockConfigEditorService);

        $result = $service->execute($data);

        $expectedResult = [
            [
                'module' => 'Module',
                'version' => '1.0',
                'operation' => 'updated',
                'type' => 'doctrine',
                'file' => $configFile,
            ],
        ];

        $this->assertSame($expectedResult, $result);
    }
}
