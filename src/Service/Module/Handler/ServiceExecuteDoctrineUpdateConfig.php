<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Elenyum\Maker\Service\Module\Config\DoctrineConfig;

class ServiceExecuteDoctrineUpdateConfig implements ServiceExecuteInterface
{
    public function __construct(
        private DoctrineConfig $config
    ) {
    }

    public function execute(array $data): array
    {
        $version = $data['version_namespace'];
        $moduleName = $data['module_name'];

        $result = $this->config->addConfigForModule($moduleName, $version);
        $this->config->onSave();

        return $result;
    }
}