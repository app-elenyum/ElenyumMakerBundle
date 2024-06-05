<?php

namespace Elenyum\Maker\Tests\Service\Module\Entity;

use Elenyum\Maker\Service\Module\Entity\ServiceAddGroup;
use Nette\PhpGenerator\ClassType;
use PHPUnit\Framework\TestCase;

class ServiceAddGroupTest extends TestCase
{
    public function testCreateAddsClassGroupAttribute()
    {
        $service = new ServiceAddGroup();
        $class = new ClassType('TestEntity');

        $data = [
            'group' => 'Admin',
            'column' => [],
        ];

        $classWithGroups = $service->create($class, $data);

        $attributes = $classWithGroups->getAttributes();
        $this->assertNotEmpty($attributes);
        $this->assertEquals('Groups', $attributes[0]->getName());
        $this->assertEquals(['Admin'], $attributes[0]->getArguments());
    }

    public function testCreateAddsPropertyGroupAttributes()
    {
        $service = new ServiceAddGroup();
        $class = new ClassType('TestEntity');
        $class->addProperty('name');

        $data = [
            'column' => [
                [
                    'name' => 'name',
                    'group' => [
                        'create' => ['Admin', 'User'],
                        'update' => ['Admin'],
                    ],
                ],
            ],
        ];

        $classWithGroups = $service->create($class, $data);

        $property = $classWithGroups->getProperty('name');
        $attributes = $property->getAttributes();
        $this->assertNotEmpty($attributes);
        $this->assertEquals('Groups', $attributes[0]->getName());
        $this->assertEquals([['create_Admin', 'create_User', 'update_Admin']], $attributes[0]->getArguments());
    }

    public function testCreateSkipsEmptyPropertyGroup()
    {
        $service = new ServiceAddGroup();
        $class = new ClassType('TestEntity');
        $class->addProperty('name');

        $data = [
            'column' => [
                [
                    'name' => 'name',
                    'group' => [],
                ],
            ],
        ];

        $classWithGroups = $service->create($class, $data);

        $property = $classWithGroups->getProperty('name');
        $attributes = $property->getAttributes();
        $this->assertIsArray($attributes);
        $this->assertEquals($attributes[0]->getArguments()[0], ['Default']);
    }

    public function testPrepareGroupWithNonEmptyGroups()
    {
        $service = new ServiceAddGroup();

        $groups = [
            'create' => ['Admin', 'User'],
            'update' => ['Admin'],
        ];

        $result = $this->invokePrivateMethod($service, 'prepareGroup', [$groups]);

        $expected = ['create_Admin', 'create_User', 'update_Admin'];
        $this->assertEquals($expected, $result);
    }

    public function testPrepareGroupWithEmptyGroups()
    {
        $service = new ServiceAddGroup();

        $groups = [];

        $result = $this->invokePrivateMethod($service, 'prepareGroup', [$groups]);

        $expected = ['Default'];
        $this->assertEquals($expected, $result);
    }

    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}