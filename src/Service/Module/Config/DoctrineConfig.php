<?php

namespace Elenyum\Maker\Service\Module\Config;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DoctrineConfig extends AbstractConfig
{
    /**
     * @var string|null
     */
    private ?string $path;

    /**
     * @var array|null
     */
    private ?array $value;

    /**
     * @var string
     */
    private string $file;


    /**
     * @var string|null
     */
    private ?string $namespace;

    public function __construct(
        private array $options
    ) {
        $root = $this->options['root'] ?? null;
        if ($root === null) {
            throw new MissingOptionsException('Not defined "root" option');
        }

        $this->path = $root['path'] ?? null;
        if ($this->path === null) {
            throw new MissingOptionsException('Not defined "path" option');
        }

        $this->namespace = $root['namespace'] ?? null;
        if ($this->namespace === null) {
            throw new MissingOptionsException('Not defined "namespace" option');
        }

        $this->file = $this->path.'/../config/packages/doctrine.yaml';
        $this->value = $this->parseConfig($this->file);
    }

    public function addConfigForModule(string $moduleName, string $version): array
    {
        $fullNamespace = ucfirst($this->namespace).'\\'.$moduleName.'\\'.ucfirst($version).'\\Entity';

        $dirEntityFile = $this->path.'/'.$moduleName.'/'.$version.'/Entity';


        $key = ucfirst($moduleName).ucfirst($version);

        $this->value['doctrine']['orm']['entity_managers']['default']['mappings'] += [
            $key => [
                "is_bundle" => false,
                "type" => "attribute",
                "dir" => $dirEntityFile,
                "prefix" => $fullNamespace,
                "alias" => $key,
            ],
        ];

        return [
            [
                'module' => $moduleName,
                'version' => mb_strtolower(str_replace('_', '.', $version)),
                'operation' => 'updated',
                'type' => 'doctrine',
                'file' => $this->file,
            ],
        ];
    }

    public function deleteConfigForModule(string $type, string $moduleName, string $version): array
    {
        if ($type === 'module') {
            $this->findAndRemoveKeys($this->value['doctrine']['orm']['entity_managers']['default']['mappings'], $moduleName);
        } else {
            $key = ucfirst($moduleName).ucfirst($version);
            unset($this->value['doctrine']['orm']['entity_managers']['default']['mappings'][$key]);
        }

        return [
            [
                'module' => $moduleName,
                'version' => mb_strtolower(str_replace('_', '.', $version)),
                'operation' => 'updated',
                'type' => 'doctrine',
                'file' => $this->file,
            ],
        ];
    }

    function findAndRemoveKeys(array &$array, string $prefix): array
    {
        $foundKeys = [];

        foreach ($array as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $foundKeys[] = $key;
                unset($array[$key]);
            }
        }

        return $foundKeys;
    }


    public function onSave(): void
    {
        $this->save($this->value);
    }
}