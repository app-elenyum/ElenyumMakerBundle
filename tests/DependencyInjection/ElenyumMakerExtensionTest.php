<?php

namespace Elenyum\Maker\Tests\DependencyInjection;

use Elenyum\Maker\DependencyInjection\ElenyumMakerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ElenyumMakerExtensionTest extends TestCase
{
    private $container;
    private $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder(new ParameterBag());
        $this->extension = new ElenyumMakerExtension();
    }

    public function testLoadSetsParameters()
    {
        $configs = [
            'elenyum_maker' => [
                'cache' => [
                    'enable' => true,
                    'item_id' => 'custom_item_id',
                ],
                'root' => [
                    'path' => 'custom/module',
                    'namespace' => 'Custom\\Module',
                    'prefix' => 'CustomModule',
                ]
            ]
        ];

        $this->extension->load($configs, $this->container);

        // Проверяем, что параметры корректно установлены
        $this->assertTrue($this->container->hasParameter('elenyum_maker.config'));
        $params = $this->container->getParameter('elenyum_maker.config');
        $this->assertIsArray($params);
        $this->assertArrayHasKey('cache', $params);
        $this->assertArrayHasKey('enable', $params['cache']);
        $this->assertTrue($params['cache']['enable']);
    }
}