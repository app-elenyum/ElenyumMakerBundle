<?php

namespace Elenyum\Maker\Tests\Service\Module;


use Elenyum\Maker\Service\Module\Config\DoctrineConfig;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteInterface;
use Elenyum\Maker\Service\Module\ServiceBeforeMake;
use Elenyum\Maker\Service\Module\ServiceDoctrineSchemaUpdate;
use Elenyum\Maker\Service\Module\ServiceMakeModule;
use PHPUnit\Framework\TestCase;

class ServiceMakeModuleTest extends TestCase
{
    private $create;
    private $beforeMake;
    private $config;
    private $doctrineSchemaUpdateMock;
    private $serviceMakeModule;

    protected function setUp(): void
    {
        $this->create = $this->createMock(\ArrayObject::class);
        $this->beforeMake = $this->createMock(ServiceBeforeMake::class);
        $this->config = $this->createMock(DoctrineConfig::class);
        $this->doctrineSchemaUpdateMock = $this->createMock(ServiceDoctrineSchemaUpdate::class);

        $this->serviceMakeModule = new ServiceMakeModule($this->beforeMake, $this->create, $this->doctrineSchemaUpdateMock, $this->config);
    }

    public function testCreateModule()
    {
        $this->beforeMake->method('prepareData')->willReturn([[], ['/test/asd']]);
        $data = [
            [
                'name' => 'Module1',
                'version' => [
                    '1.0' => [
                        'entity' => [
                            [
                                'name' => 'Entity1',
                                'isEndpoint' => true,
                                'group' => 'Group1',
                                'validator' => 'Validator1',
                                'column' => 'Column1',
                                'updatedAt' => '2023-01-01',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $preparedData = [
            [
                'module_name' => 'Module1',
                'module_name_lower' => 'module1',
                'version' => '1.0',
                'version_namespace' => '1_0',
                'entity_name' => 'Entity1',
                'entity_name_lower' => 'entity1',
                'isEndpoint' => true,
                'group' => 'Group1',
                'validator' => 'Validator1',
                'column' => 'Column1',
                'updatedAt' => '2023-01-01',
            ],
        ];

        $createHandlerMock = $this->createMock(ServiceExecuteInterface::class);
        $createHandlerMock->method('execute')->willReturn([
            ['entityPath' => 'path/to/entity1']
        ]);

        $this->create->method('getIterator')->willReturn(new \ArrayIterator([$createHandlerMock]));

        $this->doctrineSchemaUpdateMock->method('execute')->willReturn(['SQL QUERY']);

        $result = $this->serviceMakeModule->createModule($data);

        $expectedStructures = [];

        $expectedSqls = ['SQL QUERY'];

        $this->assertEquals([$expectedStructures, $expectedSqls], $result);
    }
}
