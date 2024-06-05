<?php

namespace Elenyum\Maker\Tests\Service\Module\Controller;

use Elenyum\Maker\Service\Module\Controller\ServiceAddPostController;
use PHPUnit\Framework\TestCase;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Literal;
use Symfony\Component\HttpFoundation\Response;

class ServiceAddPostControllerTest extends TestCase
{
    public function testCreateController()
    {
        $service = new ServiceAddPostController();
        $fullNamespace = 'App\Controller';
        $entity = 'App\Entity\SomeEntity';
        $serviceClass = 'App\Service\SomeService';
        $data = [
            'entity_name' => 'someEntity',
            'module_name_lower' => 'module',
            'entity_name_lower' => 'entity',
            'version' => '1.0',
            'group' => ['group1', 'group2']
        ];
        $prefix = 'api';

        $namespace = $service->createController($fullNamespace, $serviceClass, $entity, $data, $prefix);

        $this->assertInstanceOf(PhpNamespace::class, $namespace);
        $this->assertEquals($fullNamespace, $namespace->getName());

        $class = $namespace->getClasses()['SomeEntityPostController'];
        $this->assertEquals('SomeEntityPostController', $class->getName());
        $this->assertEquals('AbstractController', $class->getExtends());

        // Проверка наличия атрибутов
        $attributes = $class->getAttributes();
        $this->assertCount(6, $attributes);

        $this->assertEquals('Tag', $attributes[0]->getName());
        $this->assertEquals(['name' => 'module'], $attributes[0]->getArguments());

        $this->assertEquals('OA\RequestBody', $attributes[1]->getName());
        $this->assertArrayHasKey('content', $attributes[1]->getArguments());

        $this->assertEquals('OA\Response', $attributes[2]->getName());
        $this->assertEquals(Response::HTTP_OK, $attributes[2]->getArguments()['response']);

        $this->assertEquals('OA\Response', $attributes[3]->getName());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $attributes[3]->getArguments()['response']);

        $this->assertEquals('OA\Response', $attributes[4]->getName());
        $this->assertEquals(Response::HTTP_EXPECTATION_FAILED, $attributes[4]->getArguments()['response']);

        $this->assertEquals('Route', $attributes[5]->getName());
        $this->assertEquals('/api/1_0/module/entity', $attributes[5]->getArguments()['path']);
        $this->assertEquals([new Literal('Request::METHOD_POST')], $attributes[5]->getArguments()['methods']);

        $this->assertTrue($class->hasMethod('__invoke'));
    }

    public function testGetName()
    {
        $service = new ServiceAddPostController();
        $result = $service->getName('entityName');
        $this->assertEquals('EntityNamePostController', $result);
    }
}
