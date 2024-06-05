<?php

namespace Elenyum\Maker\Tests\Service\Module\Controller;

use Elenyum\Maker\Service\Module\Controller\ServiceAddGetController;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddGetControllerTest extends TestCase
{
    public function testCreateController()
    {
        $service = new ServiceAddGetController();
        $fullNamespace = 'App\Controller';
        $entity = 'App\Entity\SomeEntity';
        $serviceClass = 'App\Service\SomeService';
        $data = [
            'entity_name' => 'someEntity',
            'module_name_lower' => 'module',
            'entity_name_lower' => 'entity',
            'version' => '1.0',
        ];
        $prefix = 'api';

        $namespace = $service->createController($fullNamespace, $serviceClass, $entity, $data, $prefix);

        $this->assertInstanceOf(PhpNamespace::class, $namespace);
        $this->assertEquals($fullNamespace, $namespace->getName());

        $class = $namespace->getClasses()['SomeEntityGetController'];
        $this->assertEquals('SomeEntityGetController', $class->getName());
        $this->assertEquals('AbstractController', $class->getExtends());

        // Check the presence and correctness of attributes
        $attributes = $class->getAttributes();
        $this->assertCount(5, $attributes);

        $this->assertEquals('Tag', $attributes[0]->getName());
        $this->assertEquals(['name' => 'module'], $attributes[0]->getArguments());

        $this->assertEquals('OA\Response', $attributes[1]->getName());
        $this->assertEquals(Response::HTTP_OK, $attributes[1]->getArguments()['response']);

        $this->assertEquals('Route', $attributes[4]->getName());
        $this->assertEquals('/api/1_0/module/entity/{id<\d+>}', $attributes[4]->getArguments()['path']);
        $this->assertEquals([new Literal('Request::METHOD_GET')], $attributes[4]->getArguments()['methods']);

        $this->assertEquals('OA\Parameter', $attributes[3]->getName());
        $this->assertEquals(['name' => 'id', 'in' => 'path', 'schema' => Literal::new('OA\Schema', ['type' => 'integer'])], $attributes[3]->getArguments());

        $this->assertTrue($class->hasMethod('__invoke'));
    }

    public function testGetName()
    {
        $service = new ServiceAddGetController();
        $result = $service->getName('entityName');
        $this->assertEquals('EntityNameGetController', $result);
    }
}
