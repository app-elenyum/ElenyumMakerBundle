<?php

namespace Elenyum\Maker\Tests\Service\Module;


use Elenyum\Maker\Service\Module\Handler\ServiceExecuteInterface;
use Elenyum\Maker\Service\Module\ServiceDoctrineSchemaUpdate;
use Elenyum\Maker\Service\Module\ServiceMakeModule;
use PHPUnit\Framework\TestCase;

class ServiceMakeModuleTest extends TestCase
{
    private $createMock;
    private $doctrineSchemaUpdateMock;
    private $serviceMakeModule;

    protected function setUp(): void
    {
        $this->createMock = $this->createMock(\ArrayObject::class);
        $this->doctrineSchemaUpdateMock = $this->createMock(ServiceDoctrineSchemaUpdate::class);

        $this->serviceMakeModule = new ServiceMakeModule($this->createMock, $this->doctrineSchemaUpdateMock);
    }

    public function testCreateModule()
    {
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

        $this->createMock->method('getIterator')->willReturn(new \ArrayIterator([$createHandlerMock]));

        $this->doctrineSchemaUpdateMock->method('execute')->willReturn(['SQL QUERY']);

        $result = $this->serviceMakeModule->createModule($data);

        $expectedStructures = [
            [
                'entityPath' => 'path/to/entity1'
            ]
        ];

        $expectedSqls = ['SQL QUERY'];

        $this->assertEquals([$expectedStructures, $expectedSqls], $result);
    }

    public function testPrepareData()
    {
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

        $expected = [
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

        $method = new \ReflectionMethod(ServiceMakeModule::class, 'prepareData');
        $method->setAccessible(true);

        $result = $method->invoke($this->serviceMakeModule, $data);

        $this->assertEquals($expected, $result);
    }
}
