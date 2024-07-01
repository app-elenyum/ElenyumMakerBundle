<?php

namespace Elenyum\Maker\Service\Module\Config;

abstract class AbstractConfig
{
    private ?ConfigEditorService $config;

    protected function parseConfig(string $file): ?array
    {
        if (file_exists($file)) {
            $this->config = new ConfigEditorService($file);

            return $this->config->parse();
        }

        return null;
    }

    protected function save(array $data): void
    {
        $this->config->save($data);
    }
}