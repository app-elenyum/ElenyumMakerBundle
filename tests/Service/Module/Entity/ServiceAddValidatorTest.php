<?php

namespace Elenyum\Maker\Tests\Service\Module\Entity;

use Elenyum\Maker\Service\Module\Entity\ServiceAddValidator;
use Nette\PhpGenerator\ClassType;
use PHPUnit\Framework\TestCase;

class ServiceAddValidatorTest extends TestCase
{
    public function testCreateAddsValidatorsToProperties()
    {
        $service = new ServiceAddValidator();
        $class = new ClassType('TestEntity');

        $property1 = $class->addProperty('name')
            ->setType('string');

        $property2 = $class->addProperty('email')
            ->setType('string');

        $data = [
            'column' => [
                [
                    'name' => 'name',
                    'validator' => [
                        'NotBlank' => null,
                        'Length' => ['max' => 255],
                    ],
                ],
                [
                    'name' => 'email',
                    'validator' => [
                        'Email' => null,
                    ],
                ],
            ],
        ];

        $classWithValidators = $service->create($class, $data);

        $this->assertCount(2, $classWithValidators->getProperties());

        $nameProperty = $classWithValidators->getProperty('name');
        $this->assertNotNull($nameProperty);
        $this->assertEquals('string', $nameProperty->getType());
        $this->assertCount(2, $nameProperty->getAttributes());

        $emailProperty = $classWithValidators->getProperty('email');
        $this->assertNotNull($emailProperty);
        $this->assertEquals('string', $emailProperty->getType());
        $this->assertCount(1, $emailProperty->getAttributes());
    }

    public function testCreateSkipsPropertiesWithoutValidators()
    {
        $service = new ServiceAddValidator();
        $class = new ClassType('TestEntity');

        $property1 = $class->addProperty('name')
            ->setType('string');

        $property2 = $class->addProperty('age')
            ->setType('int');

        $data = [
            'column' => [
                [
                    'name' => 'name',
                    'validator' => [
                        'NotBlank' => null,
                    ],
                ],
                [
                    'name' => 'age',
                    'validator' => [],
                ],
            ],
        ];

        $classWithValidators = $service->create($class, $data);

        $this->assertCount(2, $classWithValidators->getProperties());

        $nameProperty = $classWithValidators->getProperty('name');
        $this->assertNotNull($nameProperty);
        $this->assertEquals('string', $nameProperty->getType());
        $this->assertCount(1, $nameProperty->getAttributes());

        $ageProperty = $classWithValidators->getProperty('age');
        $this->assertNotNull($ageProperty);
        $this->assertEquals('int', $ageProperty->getType());
        $this->assertCount(0, $ageProperty->getAttributes());
    }

    public function testCreateHandlesEmptyValidatorArray()
    {
        $service = new ServiceAddValidator();
        $class = new ClassType('TestEntity');

        $property = $class->addProperty('name')
            ->setType('string');

        $data = [
            'column' => [
                [
                    'name' => 'name',
                    'validator' => [],
                ],
            ],
        ];

        $classWithValidators = $service->create($class, $data);

        $this->assertCount(1, $classWithValidators->getProperties());

        $nameProperty = $classWithValidators->getProperty('name');
        $this->assertNotNull($nameProperty);
        $this->assertEquals('string', $nameProperty->getType());
        $this->assertCount(0, $nameProperty->getAttributes());
    }
}