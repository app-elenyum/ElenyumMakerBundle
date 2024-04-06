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
//            $module = (array)$module;
            $name = $module['name'];
            foreach ($module['version'] as $version => $value) {
//                $value = (array)$value;
                /** так-как по каждому сущности из каждой версии нужны контроллеры, сущности и прочее, то расскладываем все в массив */
                foreach ($value['entity'] as $entity) {
//                    $entity = (array) $entity;
                    $result[] = [
                        'module_name' => $name,
                        'version' => $version,
                        'entity_name' => $entity['name'],
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