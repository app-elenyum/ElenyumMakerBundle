<?php

namespace Elenyum\Maker\Service\Module;

use Countable;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteControllerHandler;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteInterface;
use Elenyum\Maker\Service\Module\Handler\ServiceExecuteServiceHandler;

class ServiceMakeModule
{
    public function __construct(
        /** @var ServiceExecuteInterface[] */
        private readonly Countable $create,
        private ServiceDoctrineSchemaUpdate $doctrineSchemaUpdate
    ) {
    }

    /**
     * @param array $data
     * @return array - return created files and path
     */
    public function createModule(array $data): array
    {
        $structures = [];

        foreach ($this->prepareData($data) as $item) {
            foreach ($this->create as $create) {
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

        $entityPaths = array_unique(array_column($structures, 'entityPath'));

        /** execute sql */
        $sqls = $this->doctrineSchemaUpdate->execute($entityPaths);

        return [$structures, $sqls];
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $result = [];
        foreach ($data as $module) {
            $name = $module['name'];
            foreach ($module['version'] as $version => $value) {
                /** так-как по каждому сущности из каждой версии нужны контроллеры, сущности и прочее, то расскладываем все в массив */
                foreach ($value['entity'] as $entity) {
                    $result[] = [
                        'module_name' => ucfirst($name),
                        'module_name_lower' => mb_strtolower($name),
                        'version' => mb_strtolower($version),
                        'version_namespace' => ucfirst(str_replace('.', '_', $version)),
                        'entity_name' => ucfirst($entity['name']),
                        'entity_name_lower' => mb_strtolower($entity['name']),
                        'isEndpoint' => $entity['isEndpoint'],
                        'group' => $entity['group'],
                        'validator' => $entity['validator'],
                        'column' => $entity['column'],
                        'updatedAt' => $entity['updatedAt'],
                    ];
                }
            }
        }

        return $result;
    }
}