<?php

namespace Elenyum\Maker\Tests\Service\Module;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\SchemaTool;
use Elenyum\Maker\Service\Module\ServiceDoctrineSchemaUpdate;
use PHPUnit\Framework\TestCase;

class ServiceDoctrineSchemaUpdateTest extends TestCase
{
    private $entityManagerProviderMock;
    private $entityManagerMock;
    private $configurationMock;
    private $classMetadataFactoryMock;
    private $connectionMock;
    private $platformMock;

    protected function setUp(): void
    {
        $this->entityManagerProviderMock = $this->createMock(EntityManagerProvider::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->schemaToolMock = $this->getMockBuilder(SchemaTool::class)->disableOriginalConstructor()->getMock();
        $this->classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->platformMock = $this->createMock(AbstractPlatform::class);
    }

    public function testGetEntityManager()
    {
        $this->entityManagerProviderMock->method('getDefaultManager')->willReturn($this->entityManagerMock);

        $service = new ServiceDoctrineSchemaUpdate($this->entityManagerProviderMock);
        $entityManager = $this->getProtectedMethod($service, 'getEntityManager')->invoke($service);

        $this->assertSame($this->entityManagerMock, $entityManager);
    }

    public function testGetConfigs()
    {
        $service = new ServiceDoctrineSchemaUpdate($this->entityManagerProviderMock);
        $paths = ['path/to/entities'];

        $config = $service->getConfigs($paths);

        $this->assertInstanceOf(Configuration::class, $config);
    }

//    public function testExecute()
//    {
//        $paths = ['path/to/entities'];
//        $metadata = ['metadata'];
//
//        $this->entityManagerProviderMock->method('getDefaultManager')->willReturn($this->entityManagerMock);
//        $this->entityManagerMock->method('getConnection')->willReturn($this->connectionMock);
//        $this->entityManagerMock->method('getMetadataFactory')->willReturn($this->classMetadataFactoryMock);
//        $this->classMetadataFactoryMock->method('getAllMetadata')->willReturn($metadata);
//
//        $this->connectionMock->method('getEventManager')->willReturn(new EventManager());
//        $this->connectionMock->method('getDatabasePlatform')->willReturn($this->platformMock);
//
//        $service = $this->getMockBuilder(ServiceDoctrineSchemaUpdate::class)
//            ->setConstructorArgs([$this->entityManagerProviderMock])
//            ->onlyMethods(['getConfigs'])
//            ->getMock();
//        $this->configurationMock->method('getMetadataDriverImpl')->willReturn(new AttributeDriver($paths));
//        $this->configurationMock->method('getProxyDir')->willReturn('/test');
//        $this->configurationMock->method('getProxyNamespace')->willReturn('Test');
//        $service->method('getConfigs')->willReturn($this->configurationMock);
//        $this->configurationMock->method('getClassMetadataFactoryName')->willReturn(ClassMetadataFactory::class);
//
//        $schemaTool = $this->getMockBuilder(SchemaTool::class)
//            ->setConstructorArgs([$this->entityManagerMock])
//            ->onlyMethods(['getUpdateSchemaSql', 'updateSchema'])
//            ->getMock();
//
//        $schemaTool->expects($this->once())
//            ->method('getUpdateSchemaSql')
//            ->with($metadata)
//            ->willReturn(['SQL QUERY']);
//
//        $schemaTool->expects($this->once())
//            ->method('updateSchema')
//            ->with($metadata);
//
//        // Override the creation of SchemaTool within execute method
//        $reflection = new \ReflectionClass(ServiceDoctrineSchemaUpdate::class);
//        $method = $reflection->getMethod('execute');
//        $method->setAccessible(true);
//
//        $result = $method->invoke($service, $paths);
//
//        $this->assertEquals(['SQL QUERY'], $result);
//    }

    private function getProtectedMethod($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
