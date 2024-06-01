<?php

namespace Elenyum\Maker\Tests\DependencyInjection;

use Elenyum\Maker\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $config = [
            'cache' => [],
            'root' => [
                'path' => 'custom/module',
            ]
        ];
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, ['elenyum_maker' => $config]);

        // Проверяем конфигурацию по умолчанию
        $this->assertFalse($config['cache']['enable']);
        $this->assertSame('elenyum_maker', $config['cache']['item_id']);
        $this->assertSame('custom/module', $config['root']['path']);
        $this->assertSame('Module', $config['root']['namespace']);
        $this->assertSame('Module', $config['root']['prefix']);

    }

    public function testCustomConfiguration()
    {
        $config = [
            'cache' => [
                'enable' => true,
                'item_id' => 'custom_item_id',
            ],
            'root' => [
                'path' => 'custom/module',
                'namespace' => 'Custom\\Module',
                'prefix' => 'CustomModule',
            ]
        ];

        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, ['elenyum_maker' => $config]);

        // Проверяем кастомную конфигурацию
        $this->assertTrue($config['cache']['enable']);
        $this->assertSame('custom_item_id', $config['cache']['item_id']);
        $this->assertSame('custom/module', $config['root']['path']);
        $this->assertSame('Custom\\Module', $config['root']['namespace']);
        $this->assertSame('CustomModule', $config['root']['prefix']);
    }

    public function testConfigurationNamespaceIsRequired()
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $config = [
            'root' => [
                // "path" не задан
                'namespace' => 'Custom\\Module',
            ]
        ];

        $processor = new Processor();
        $configuration = new Configuration();
        // Это должно выдать исключение, поскольку "path" является обязательным
        $processor->processConfiguration($configuration, ['elenyum_maker' => $config]);
    }
}