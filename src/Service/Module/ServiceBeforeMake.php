<?php

namespace Elenyum\Maker\Service\Module;

use DateTime;
use DateTimeInterface;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceBeforeMake
{
    /**
     * @var string|null
     */
    private ?string $path;

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
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function prepareData(array $data): array
    {
        $dataResult = [];
        $entityPaths = [];

        foreach ($data as $module) {
            $name = $module['name'];
            foreach ($module['version'] as $version => $value) {
                /** так-как по каждому сущности из каждой версии нужны контроллеры, сущности и прочее, то расскладываем все в массив */

                $moduleName = ucfirst($name);
                $versionNamespace = ucfirst(str_replace('.', '_', $version));
                $entityPath = $this->path.'/'.$moduleName.'/'.$versionNamespace.'/Entity';
                $entityPaths[md5($entityPath)] = $entityPath;

                foreach ($value['entity'] as $entity) {
                    // Проверяем были ли обновления
                    if ((new DateTime($entity['updatedAt']))?->getTimestamp() === $this->getLastModifiedDate(
                            $name,
                            $version,
                            $entity['name']
                        )?->getTimestamp()
                    ) {
                        continue;
                    }
                    $entityName = ucfirst($entity['name']);

                    $dataResult[] = [
                        'module_name' => $moduleName,
                        'module_name_lower' => mb_strtolower($name),
                        'version' => mb_strtolower($version),
                        'version_namespace' => $versionNamespace,
                        'entity_name' => $entityName,
                        'entity_name_lower' => mb_strtolower($entity['name']),
                        'isEndpoint' => $entity['isEndpoint'],
                        'group' => $entity['group'],
                        'validator' => $entity['validator'],
                        'column' => $this->prepareColumns($entity['column']),
                        'updatedAt' => $entity['updatedAt'],
                    ];
                }
            }
        }

        return [$dataResult, $entityPaths];
    }

    private function prepareColumns(array $columns): array
    {
        foreach ($columns as &$column) {
            $column['camel_case_name'] = $this->snakeToCamelCase($column['name']);
            $column['info']['camel_case_mapped_by'] = !empty($column['info']['mappedBy']) ? $this->snakeToCamelCase($column['info']['mappedBy']) : null;
            $column['info']['camel_case_inversed_by'] = !empty($column['info']['inversedBy']) ? $this->snakeToCamelCase($column['info']['inversedBy']) : null;
            $column['info']['camel_case_target_entity'] = !empty($column['info']['targetEntity']) ? $this->snakeToCamelCase($column['info']['targetEntity']) : null;
        }

        return $columns;
    }

    private function snakeToCamelCase(string $string): string
    {
        // Разбиваем строку по символу подчеркивания
        $str = str_replace('_', '', ucwords($string, '_'));

        // Приводим первый символ к нижнему регистру
        return lcfirst($str);
    }

    public function collectForDelete(array $data): array
    {
        $result = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && preg_match(
                    '/module\/([^\/]+)\/([^\/]+)\/Entity$/',
                    $fileinfo->getPathname(),
                    $matches
                )) {
                $moduleName = $matches[1];
                $version = null;
                $versionNamespace = $matches[2];
                if (!empty($matches[2])) {
                    $version = mb_strtolower(str_replace('_', '.', $matches[2]));
                }
                $checkModule = array_filter($data, fn($i) => $i['name'] === $moduleName);
                // Если данные по модулю не пришли то удаляем модуль
                if (empty($checkModule)) {
                    $type = 'module';
                    $path = $this->getPath($moduleName);
                    $result[$path] = [
                        'module' => $moduleName,
                        'version_namespace' => $versionNamespace,
                        'version' => $version,
                        'operation' => 'delete',
                        'type' => $type,
                        'file' => $path,
                    ];
                    continue;
                }
                $currentVersion = current($checkModule)['version'][$version] ?? null;
                if (empty($currentVersion)) {
                    $type = 'version';
                    $path = $this->getPath($moduleName, $version);
                    $result[$path] = [
                        'module' => $moduleName,
                        'version_namespace' => $versionNamespace,
                        'version' => $version,
                        'operation' => 'delete',
                        'type' => $type,
                        'file' => $path,
                    ];
                    continue;
                }
                $entityFiles = glob($fileinfo->getPathname().'/*.php');
                foreach ($entityFiles as $entityFile) {
                    $entityName = pathinfo($entityFile, PATHINFO_FILENAME);
                    $entityInData = array_filter($currentVersion['entity'], fn($i) => $i['name'] === $entityName);

                    if (empty($entityInData)) {
                        $type = 'entity';
                        $path = $this->getPath($moduleName, $version, $entityName);
                        $result[$path] = [
                            'module' => $moduleName,
                            'version_namespace' => $versionNamespace,
                            'version' => $version,
                            'operation' => 'delete',
                            'type' => $type,
                            'file' => $path,
                        ];
                    }
                }
            }
        }

        return array_values($result);
    }

    public function getPath(string $moduleName, ?string $version = null, ?string $entityName = null): string
    {
        $path = '';
        $moduleName = ucfirst($moduleName);
        if ($version !== null) {
            $version = ucfirst(str_replace('.', '_', $version));
        }
        if ($entityName !== null) {
            $entityName = ucfirst($entityName);
        }

        if ($version !== null && $entityName !== null) {
            $path = sprintf(
                '%s/%s/%s/Entity/%s.php',
                $this->path,
                $moduleName,
                $version,
                $entityName
            );
        } elseif ($version !== null && $entityName === null) {
            $path = sprintf(
                '%s/%s/%s',
                $this->path,
                $moduleName,
                $version
            );
        } elseif ($version === null && $entityName === null) {
            $path = sprintf(
                '%s/%s',
                $this->path,
                $moduleName,
            );
        }

        return $path;
    }

    public function getLastModifiedDate(string $moduleName, string $version, string $entityName): ?DateTimeInterface
    {
        $path = $this->getPath($moduleName, $version, $entityName);

        if (!file_exists($path)) {
            return null; // Возвращаем null, если файл не существует
        }

        $lastModifiedTime = filemtime($path);

        $date = new DateTime();
        $date->setTimestamp($lastModifiedTime);

        return $date;
    }
}