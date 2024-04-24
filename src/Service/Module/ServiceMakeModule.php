<?php

namespace Elenyum\Maker\Service\Module;

use Countable;
use Elenyum\Maker\Service\Module\Handler\ServiceCreateInterface;

class ServiceMakeModule
{
    public function __construct(
        /** @var ServiceCreateInterface[] */
        private readonly Countable $create
    ) {
    }

    /**
     * @param array $data
     * @return array - return created files and path
     */
    public function createModule(array $data): array
    {
        $result = [];

        foreach ($this->prepareData($data) as $item) {
            foreach ($this->create as $create) {
//                if (
//                    /** Если не endpoint то не нужно создавать сервисы и контроллеры */
//                    ($create instanceof ServiceCreateControllerHandler || $create instanceof ServiceCreateServiceHandler)
//                    && $item['isEndpoint'] === false
//                ) {
//                    continue;
//                }
                $paths = $create->create($item);
                $result = array_merge($result, $paths);
            }
        }


        return $result;
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