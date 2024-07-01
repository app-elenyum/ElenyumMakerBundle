<?php

namespace Elenyum\Maker\Tests\Controller;

use Doctrine\DBAL\Exception;
use Elenyum\Maker\Controller\MakeController;
use Elenyum\Maker\Service\Module\ServiceMakeModule;
use Elenyum\Maker\Service\Module\ServiceShowModule;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MakeControllerTest extends WebTestCase
{
    private $makeModule;
    private $showModule;
    private $controller;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the ServiceMakeModule dependency
        $this->makeModule = $this->createMock(ServiceMakeModule::class);
        $this->showModule = $this->createMock(ServiceShowModule::class);

        // Create instance of MakeController
        $this->controller = new MakeController($this->makeModule, $this->showModule);

        // Mock Request
        $this->request = $this->createMock(Request::class);

        // Mock ContainerInterface and set it to controller
        $container = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($container);
    }

    protected function tearDown(): void
    {
        $this->makeModule = null;
        $this->controller = null;
        $this->request = null;

        parent::tearDown();
    }

    public function testInvokeSuccess(): void
    {
        // Assume JSON POST request
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getContent')->willReturn('{"moduleName":"Test"}');

        // Configure the stub of ServiceMakeModule
        $this->makeModule->method('createModule')->willReturn([['structure'], ['sqls']]);

        // Perform the request to the controller
        $response = $this->controller->__invoke($this->request);

        // Asserts
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertJson($content);
        $responseData = json_decode($content, true);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('structures', $responseData['data']);
        $this->assertArrayHasKey('sqls', $responseData['data']);
    }

    public function testInvokeNotAllowedMethod(): void
    {
        // Assume GET request
        $this->request->method('getMethod')->willReturn('PUT');

        // Perform the request to the controller
        $response = $this->controller->__invoke($this->request);

        // Asserts
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertJson($content);
        $responseData = json_decode($content, true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Allow only POST or GET method', $responseData['message']);
    }

    public function testInvokeThrowsException(): void
    {
        $this->expectException(Exception::class);

        // Assume JSON POST request that will cause an exception
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getContent')->willReturn('{"moduleName":"Test"}');

        // Configure the ServiceMakeModule to throw an exception
        $this->makeModule->method('createModule')->will($this->throwException(new \RuntimeException()));

        // Perform the request to the controller, expecting an exception
        $this->controller->__invoke($this->request);
    }
}