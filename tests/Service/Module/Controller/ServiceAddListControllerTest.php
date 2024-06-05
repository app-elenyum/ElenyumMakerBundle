<?php

namespace Elenyum\Maker\Tests\Service\Module\Controller;

use Elenyum\Maker\Service\Module\Controller\ServiceAddListController;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddListControllerTest extends TestCase
{
    public function testCreateController()
    {
        $service = new ServiceAddListController();
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

        $class = $namespace->getClasses()['SomeEntityListController'];
        $this->assertEquals('SomeEntityListController', $class->getName());
        $this->assertEquals('AbstractController', $class->getExtends());

        // Проверка наличия атрибутов
        $attributes = $class->getAttributes();
        $this->assertCount(9, $attributes);

        $this->assertEquals('Tag', $attributes[0]->getName());
        $this->assertEquals(['name' => 'module'], $attributes[0]->getArguments());

        $this->assertEquals('OA\Response', $attributes[1]->getName());
        $this->assertEquals(Response::HTTP_OK, $attributes[1]->getArguments()['response']);

        $this->assertEquals('OA\Response', $attributes[2]->getName());
        $this->assertEquals(Response::HTTP_EXPECTATION_FAILED, $attributes[2]->getArguments()['response']);

        $this->assertEquals('OA\Parameter', $attributes[3]->getName());
        $this->assertEquals(['name' => 'limit', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'integer']), 'example' => 10], $attributes[3]->getArguments());

        $this->assertEquals('OA\Parameter', $attributes[4]->getName());
        $this->assertEquals(['name' => 'offset', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'integer']), 'example' => 20], $attributes[4]->getArguments());

        $this->assertEquals('OA\Parameter', $attributes[5]->getName());
        $this->assertEquals(['name' => 'fields', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '["id", "name", "card.id", "card.name"]'], $attributes[5]->getArguments());

        $this->assertEquals('OA\Parameter', $attributes[6]->getName());
        $this->assertEquals(['name' => 'filter', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '{"name":"test"}'], $attributes[6]->getArguments());

        $this->assertEquals('OA\Parameter', $attributes[7]->getName());
        $this->assertEquals(['name' => 'orderBy', 'in' => 'query', 'schema' => Literal::new('OA\Schema', ['type' => 'string']), 'example' => '{"name":"desc"}'], $attributes[7]->getArguments());

        $this->assertEquals('Route', $attributes[8]->getName());
        $this->assertEquals('/api/1_0/module/entity', $attributes[8]->getArguments()['path']);
        $this->assertEquals([new Literal('Request::METHOD_GET')], $attributes[8]->getArguments()['methods']);

        $this->assertTrue($class->hasMethod('__invoke'));
    }

    public function testGetName()
    {
        $service = new ServiceAddListController();
        $result = $service->getName('entityName');
        $this->assertEquals('EntityNameListController', $result);
    }
}
