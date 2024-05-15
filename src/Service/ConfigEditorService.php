<?php

namespace Elenyum\Maker\Service;

use Symfony\Component\Yaml\Yaml;

class ConfigEditorService
{
    private string|null $configFile = null;

    public function __construct(string $configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * @return string|null
     */
    public function getConfigFile(): ?string
    {
        return $this->configFile;
    }

    public function parse(): array
    {
        return Yaml::parseFile($this->getConfigFile());
    }

    /**
     * @param array $value
     * @param int $inline
     * @return void
     */
    public function save(array $value, int $inline = 10): void
    {
        file_put_contents($this->getConfigFile(), Yaml::dump($value, $inline));
    }
}