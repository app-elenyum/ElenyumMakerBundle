<?php

namespace Elenyum\Maker\Service\Module;

use Countable;
use Elenyum\Maker\Service\Module\Config\DoctrineConfig;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteControllerHandler;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteInterface;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteServiceHandler;

class ServiceMakeModule
{
    public function __construct(
        private ServiceBeforeMake $beforeMake,
        /** @var ServiceExecuteInterface[] */
        private readonly Countable $create,
        private ServiceDoctrineSchemaUpdate $doctrineSchemaUpdate,
        private DoctrineConfig $config
    ) {
    }

    /**
     * @param array $data
     * @return array - return created files and path
     * @throws \Exception
     */
    public function createModule(array $data): array
    {
        $structures = $this->delete($data);
        [$prepareData, $entityPaths] = $this->beforeMake->prepareData($data);
        foreach ($prepareData as $item) {
            foreach ($this->create as $create) {
                // Если нет endpoint
                if (
                    ($create instanceof ServiceExecuteControllerHandler || $create instanceof ServiceExecuteServiceHandler)
                    && $item['isEndpoint'] === false
                ) {
                    continue;
                }

                $paths = $create->execute($item);
                $structures = array_merge($structures, $paths);
            }
        }

        /** execute sql */
        $sqls = $this->doctrineSchemaUpdate->execute($entityPaths);

        return [$structures, $sqls];
    }

    /**
     * @description delete on marker module, version and entity
     *
     * @param array $data
     * @return array
     */
    public function delete(array $data): array
    {
        $moduleForDelete = $this->beforeMake->collectForDelete($data);

        foreach ($moduleForDelete as $deleteItem) {
            $this->delTree($deleteItem['file']);
            $this->config->deleteConfigForModule($deleteItem['type'], $deleteItem['module'], $deleteItem['version_namespace']);
        }
        $this->config->onSave();

        return $moduleForDelete;
    }

    /**
     * Удаляет файл или папку с содержимым.
     *
     * @param string $path
     * @return bool
     */
    public function delTree(string $path): bool
    {
        if (is_dir($path)) {
            $files = array_diff(scandir($path), array('.','..'));
            foreach ($files as $file) {
                $fullPath = "$path/$file";
                if (is_dir($fullPath) && !is_link($fullPath)) {
                    $this->delTree($fullPath);
                } else {
                    unlink($fullPath);
                }
            }
            return rmdir($path);
        } else if (is_file($path)) {
            return unlink($path);
        } else {
            return false;
        }
    }

}