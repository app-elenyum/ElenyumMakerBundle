<?php

namespace Elenyum\Maker\Tests\Service\Module\Entity;

use Doctrine\DBAL\Types\Types;
use Elenyum\Maker\Service\Module\Entity\ServiceAddProperty;
use Nette\PhpGenerator\ClassType;
use PHPUnit\Framework\TestCase;

class ServiceAddPropertyTest extends TestCase
{
    public function testCreateAddsPropertiesWithCorrectTypesAndAttributes()
    {
        $service = new ServiceAddProperty();
        $class = new ClassType('TestEntity');
        $service->setFullNamespace('App\\Entity');

        $data = [
            'entity_name_lower' => 'testentity',
            'version_namespace' => 'v1',
            'module_name_lower' => 'module',
            'column' => [
                [
                    'name' => 'id',
                    'info' => [
                        'type' => 'integer',
                        'isPrimary' => true,
                    ],
                ],
                [
                    'name' => 'name',
                    'info' => [
                        'type' => 'string',
                    ],
                ],
                [
                    'name' => 'attributes',
                    'info' => [
                        'type' => 'json',
                    ],
                ],
                [
                    'name' => 'relatedEntity',
                    'info' => [
                        'type' => 'many-to-one',
                        'targetEntity' => 'RelatedEntity',
                        'inversedBy' => 'testEntities',
                    ],
                ],
            ],
        ];

        $classWithProperties = $service->create($class, $data);

        $this->assertCount(4, $classWithProperties->getProperties());

        $idProperty = $classWithProperties->getProperty('id');
        $this->assertNotNull($idProperty);
        $this->assertEquals('int', $idProperty->getType());
        $this->assertTrue($this->propertyHasAttribute($idProperty, 'ORM\Id'));
        $this->assertTrue($this->propertyHasAttribute($idProperty, 'ORM\GeneratedValue'));
        $this->assertTrue($this->propertyHasAttribute($idProperty, 'ORM\Column', ['type' => Types::INTEGER, 'nullable' => true]));

        $nameProperty = $classWithProperties->getProperty('name');
        $this->assertNotNull($nameProperty);
        $this->assertEquals('string', $nameProperty->getType());
        $this->assertTrue($this->propertyHasAttribute($nameProperty, 'ORM\Column', ['type' => Types::STRING, 'nullable' => true]));

        $attributesProperty = $classWithProperties->getProperty('attributes');
        $this->assertNotNull($attributesProperty);
        $this->assertEquals('array', $attributesProperty->getType());
        $this->assertTrue($this->propertyHasAttribute($attributesProperty, 'ORM\Column', ['type' => Types::JSON, 'nullable' => true]));

        $relatedEntityProperty = $classWithProperties->getProperty('relatedEntity');
        $this->assertNotNull($relatedEntityProperty);
        $this->assertEquals('RelatedEntity', $relatedEntityProperty->getType());
        $this->assertTrue($this->propertyHasAttribute($relatedEntityProperty, 'ORM\ManyToOne', ['targetEntity' => 'App\Entity\RelatedEntity', 'inversedBy' => 'testEntities']));
        $this->assertTrue($this->propertyHasAttribute($relatedEntityProperty, 'ORM\JoinColumn', ['name' => 'relatedentity_id', 'referencedColumnName' => 'id', 'nullable' => true, 'onDelete' => 'SET NULL']));
    }

    public function testCreateAddsSettersAndGetters()
    {
        $service = new ServiceAddProperty();
        $class = new ClassType('TestEntity');
        $service->setFullNamespace('App\\Entity');

        $data = [
            'entity_name_lower' => 'testentity',
            'version_namespace' => 'v1',
            'module_name_lower' => 'module',
            'column' => [
                [
                    'name' => 'name',
                    'info' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        $classWithProperties = $service->create($class, $data);

        $setter = $classWithProperties->getMethod('setName');
        $this->assertNotNull($setter);
        $this->assertEquals('self', $setter->getReturnType());

        $getter = $classWithProperties->getMethod('getName');
        $this->assertNotNull($getter);
        $this->assertEquals('string', $getter->getReturnType());
    }

    private function propertyHasAttribute($property, $attributeName, $expectedParams = null)
    {
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() === $attributeName) {
                if ($expectedParams !== null) {
                    return $attribute->getArguments() == $expectedParams;
                }
                return true;
            }
        }
        return false;
    }
}