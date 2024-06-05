<?php

namespace Elenyum\Maker\Service\Module\Handler;

use Elenyum\Maker\Service\ConfigEditorService;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceExecuteDoctrineUpdateConfig implements ServiceExecuteInterface
{
    public function __construct(
        readonly private array $options
    ) {}

    public function execute(array $data): array
    {
        $root = $this->options['root'] ?? null;
        if ($root === null) {
            throw new MissingOptionsException('Not defined "root" option');
        }
        $path = $root['path'] ?? null;
        if ($path === null) {
            throw new MissingOptionsException('Not defined "path" option');
        }
        $namespace = $root['namespace'] ?? null;
        if ($namespace === null) {
            throw new MissingOptionsException('Not defined "namespace" option');
        }

        $version = $data['version_namespace'];
        $moduleName = $data['module_name'];

        $fullNamespace = ucfirst($namespace).'\\'.$moduleName.'\\'.ucfirst($version).'\\Entity';

        $dirEntityFile = $path.'/'.$moduleName.'/'.$version.'/Entity';

        $configFile = $path.'/../config/packages/doctrine.yaml';

        $config = $this->createConfigEditorService($configFile);
        $value = $config->parse();

        $key = ucfirst($moduleName).ucfirst($version);

        $value['doctrine']['orm']['entity_managers']['default']['mappings'] += [
            $key => [
                "is_bundle" => false,
                "type" => "attribute",
                "dir" => $dirEntityFile,
                "prefix" => $fullNamespace,
                "alias" => $key,
            ],
        ];

        $config->save($value);
        return [
            [
                'module' => $moduleName,
                'version' => $data['version'],
                'operation' => 'updated',
                'type' => 'doctrine',
                'file' => $configFile,
            ],
        ];
    }

    public function createConfigEditorService(string $file): ?ConfigEditorService
    {
        $config = null;

        if (file_exists($file)) {
            $config = new ConfigEditorService($file);
        }

        return $config;
    }
}