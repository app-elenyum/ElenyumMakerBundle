<?php

namespace Elenyum\Maker\Tests\Service\Module;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Elenyum\Maker\Service\Module\ServiceShowModule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use ReflectionClass;

class ServiceShowModuleTest extends TestCase
{
    private $registryMock;
    private $objectManagerMock;
    private $metaDataMock;
    private $options;
    private $serviceShowModule;

    protected function setUp(): void
    {
        $this->registryMock = $this->createMock(Registry::class);
        $this->objectManagerMock = $this->createMock(ObjectManager::class);
        $this->metaDataMock = $this->createMock(ClassMetadata::class);

        $this->options = [
            'root' => [
                'path' => '/path/to/modules',
                'namespace' => 'App\\Entity'
            ]
        ];

        $this->serviceShowModule = new ServiceShowModule($this->registryMock, $this->options);
    }

    public function testConstructorThrowsExceptionWhenRootOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "root" option');

        new ServiceShowModule($this->registryMock, []);
    }

    public function testConstructorThrowsExceptionWhenPathOptionIsMissing()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('Not defined "path" option');

        new ServiceShowModule($this->registryMock, ['root' => []]);
    }

    public function testGetModules()
    {
        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->method('getName')->willReturn('App\\Entity\\Module1\\V1_0\\Entity\\Entity1');
        $reflectionClassMock->method('getProperties')->willReturn([]);
        $this->metaDataMock->method('getReflectionClass')->willReturn($reflectionClassMock);

        $this->objectManagerMock->method('getMetadataFactory')->willReturn($this->createMock(\Doctrine\Persistence\Mapping\ClassMetadataFactory::class));
        $this->objectManagerMock->getMetadataFactory()->method('getAllMetadata')->willReturn([$this->metaDataMock]);

        $this->registryMock->method('getManagerNames')->willReturn(['default' => 'default']);
        $this->registryMock->method('getManager')->willReturn($this->objectManagerMock);

        $result = $this->serviceShowModule->getModules();

        $expected = [
            [
                'name' => 'Module1',
                'version' => [
                    'v1.0' => [
                        'entity' => [
                            [
                                'name' => 'Entity1',
                                'isEndpoint' => false,
                                'group' => [],
                                'column' => [],
                                'validator' => [],
                                'updatedAt' => null,
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCheckController()
    {
        $moduleName = 'Module1';
        $version = 'V1_0';
        $entityName = 'Entity1';

        $path = sprintf(
            '%s/%s/%s/Controller/%s/',
            $this->options['root']['path'],
            $moduleName,
            $version,
            $entityName
        );

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $this->assertFalse($this->serviceShowModule->checkController($moduleName, $version, $entityName));

        file_put_contents($path . 'TestController.php', '<?php echo "Test";');

        $this->assertTrue($this->serviceShowModule->checkController($moduleName, $version, $entityName));

        unlink($path . 'TestController.php');
        rmdir($path);
    }

    public function testGetLastModifiedDate()
    {
        $moduleName = 'Module1';
        $version = 'V1_0';
        $entityName = 'Entity1';

        $path = sprintf(
            '%s/%s/%s/Entity/%s.php',
            $this->options['root']['path'],
            $moduleName,
            $version,
            $entityName
        );

        $this->assertNull($this->serviceShowModule->getLastModifiedDate($moduleName, $version, $entityName));

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, '<?php echo "Test";');

        $this->assertNotNull($this->serviceShowModule->getLastModifiedDate($moduleName, $version, $entityName));

        unlink($path);
        rmdir(dirname($path));
    }
}
