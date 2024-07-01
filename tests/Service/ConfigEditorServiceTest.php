<?php

namespace Elenyum\Maker\Tests\Service;

use Elenyum\Maker\Service\Module\Config\ConfigEditorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ConfigEditorServiceTest extends TestCase
{
    private $configEditorService;
    private $testFilePath = __DIR__ . '/config.yaml';

    protected function setUp(): void
    {
        parent::setUp();
        // Создаем временный тестовый файл
        file_put_contents($this->testFilePath, "test: value");

        $this->configEditorService = new ConfigEditorService($this->testFilePath);
    }

    protected function tearDown(): void
    {
        // Удаляем тестовый файл
        unlink($this->testFilePath);

        parent::tearDown();
    }

    public function testGetConfigFile()
    {
        $this->assertEquals($this->testFilePath, $this->configEditorService->getConfigFile());
    }

    public function testParse()
    {
        $expected = ['test' => 'value'];
        $result = $this->configEditorService->parse();
        $this->assertEquals($expected, $result);
    }

    public function testSave()
    {
        $newConfig = ['new' => 'config'];
        $this->configEditorService->save($newConfig);

        $savedData = Yaml::parseFile($this->testFilePath);
        $this->assertEquals($newConfig, $savedData);
    }
}