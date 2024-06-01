<?php
namespace Elenyum\Maker\Tests\EventListener;

use Elenyum\Maker\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionListenerTest extends TestCase
{
    public function testOnKernelException()
    {
        $message = 'Test Exception Message';
        $exception = new HttpException(500, $message);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = $this->createMock(Request::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener = new ExceptionListener();
        $listener->onKernelException($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(417, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals($message, $content['message']);
        $this->assertFalse($content['status']);
    }
}