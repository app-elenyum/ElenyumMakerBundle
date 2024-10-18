<?php

namespace Elenyum\Maker\Service\Module;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ServiceShowModule
{
    /**
     * @var string|null
     */
    private ?string $path;

    public function __construct(
        public Registry $registry,
        public array $options
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
     * @return array
     */
    public function getModules(): array
    {
        $managerNames = $this->registry->getManagerNames();
        $result = [];
        $rootNamespace = $this->options["root"]["namespace"];
        foreach ($managerNames as $connectionName => $connection) {
            $metas = $this->registry->getManager($connectionName)->getMetadataFactory()->getAllMetadata();
            foreach ($metas as $key => $meta) {
                $item = [];
                $reflectionClass = $meta->getReflectionClass();

                /** Если находится не в деректории с модулями то пропускаем */
                if (!str_starts_with($reflectionClass->getFileName(), $this->path)) {
                    continue;
                }

                    $classNamespace = str_replace($rootNamespace.'\\', '', $reflectionClass->getName());
//                if (!empty($reflectionClass->getAttributes(NotEditable::class))) {
//                    continue;
//                }

                $arrayNamespace = explode('\\', $classNamespace);
                $moduleName = $arrayNamespace[0];
                $dirVersion = $arrayNamespace[1];
                $version = $this->getVersion($dirVersion);
                $entityName = $arrayNamespace[3];
                $item['name'] = $moduleName;

                $columns = $this->getColumns($reflectionClass);


                $item = [
                    'name' => $entityName,
                    'isEndpoint' => $this->checkController($moduleName, $dirVersion, $entityName),
                    'group' => $this->getGroups($reflectionClass),
                    'column' => $columns,
                    'validator' => $this->getValidator($reflectionClass),
                    'updatedAt' => $this->getLastModifiedDate($moduleName, $dirVersion, $entityName),
                ];
                $result[$moduleName]['name'] = $moduleName;
                $result[$moduleName]['version'][$version]['entity'][] = $item;
            }
        }

        return array_values($result);
    }

    private function getGroups(ReflectionClass|ReflectionProperty $property, ?string $type = null): array
    {
        $attributeGroups = $property->getAttributes(Groups::class);
        $result = [];
        if (isset($attributeGroups[0]) && isset($attributeGroups[0]?->getArguments()[0])) {
            $groups = $attributeGroups[0]->getArguments()[0];
            if (!empty($groups) && $type !== null) {
                foreach ($groups as $group) {
                    if ($group === 'Default') {
                        continue;
                    }

                    // Разделяем строку на метод и роль
                    list($method, $role) = explode('_', $group);

                    if ($type === $method) {
                        // Добавляем роль в массив для метода, если ее еще нет
                        $result[] = $role;
                    }
                }
            } elseif (!empty($groups) && $type === null) {
                $result = $groups;
            }
        }

        return $result;
    }

    private function getVersion(string $version): string
    {
        return mb_strtolower(str_replace('_', '.', $version));
    }

    private function getColumns(ReflectionClass $reflectionClass): array
    {
        $columns = [];
        /**
         * @var  $propertyKey
         * @var ReflectionProperty $property
         */
        foreach ($reflectionClass->getProperties() as $propertyKey => $property) {
            $columnAttribute = $property->getAttributes(Column::class);
            $joinColumnAttribute = $property->getAttributes(JoinColumn::class);
            $columnName = $property->getName();
            if (isset($columnAttribute[0])) {
                $columnName = $columnAttribute[0]->getArguments()['name'] ?? $property->getName();
            } elseif (isset($joinColumnAttribute[0])) {
                $columnName = $joinColumnAttribute[0]->getArguments()['name'] ?? $property->getName();
            }
            $column = [
                'name' => $columnName,
                'info' => [
                    'type' => $this->getType($property),
                    'isPrimary' => $this->isPrimary($property),
                    'targetEntity' => $this->getTargetEntity($property),
                    'inversedBy' => $this->getInversedBy($property),
                    'mappedBy' => $this->getMappedBy($property),
                ],
                'validator' => $this->getValidator($property),
                'group' => [
                    'GET' => $this->getGroups($property, 'GET'),
                    'POST' => $this->getGroups($property, 'POST'),
                    'PUT' => $this->getGroups($property, 'PUT'),
                    'DELETE' => $this->getGroups($property, 'DELETE'),
                ],
            ];

            $columns[] = $column;
        }

        return $columns;
    }

    private function getType(ReflectionProperty $property): string
    {
        $type = 'string';
        /** @var \ReflectionAttribute $column */
        $columnAttribute = $property->getAttributes(Column::class)[0] ?? null;

        /** @var \ReflectionAttribute $manyToOne */
        $manyToOne = $property->getAttributes(ManyToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToOne */
        $oneToOne = $property->getAttributes(OneToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToMany */
        $oneToMany = $property->getAttributes(OneToMany::class)[0] ?? null;
        /** @var \ReflectionAttribute $manyToMany */
        $manyToMany = $property->getAttributes(ManyToMany::class)[0] ?? null;

        if ($columnAttribute !== null) {
            $type = $columnAttribute->getArguments()['type'];
        } elseif ($manyToOne !== null) {
            $type = 'many-to-one';
        } elseif ($oneToOne !== null) {
            $type = 'one-to-one';
        } elseif ($manyToMany !== null) {
            $type = 'many-to-many';
        } elseif ($oneToMany !== null) {
            $type = 'one-to-many';
        }

        return $type;
    }

    private function isPrimary(ReflectionProperty $property): bool
    {
        $result = false;

        $idAttribute = $property->getAttributes(Id::class)[0] ?? null;

        if ($idAttribute !== null) {
            $result = true;
        }

        return $result;
    }

    private function getTargetEntity(ReflectionProperty $property): ?string
    {
        $result = null;

        /** @var \ReflectionAttribute $manyToOne */
        $manyToOne = $property->getAttributes(ManyToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToOne */
        $oneToOne = $property->getAttributes(OneToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToMany */
        $oneToMany = $property->getAttributes(OneToMany::class)[0] ?? null;
        /** @var \ReflectionAttribute $manyToMany */
        $manyToMany = $property->getAttributes(ManyToMany::class)[0] ?? null;

        if ($manyToOne !== null) {
            $explode = explode('\\Entity\\', $manyToOne->getArguments()['targetEntity']);
            $result = end($explode);
        } elseif ($oneToOne !== null) {
            $explode = explode('\\Entity\\', $oneToOne->getArguments()['targetEntity']);
            $result = end($explode);
        } elseif ($oneToMany !== null) {
            $explode = explode('\\Entity\\', $oneToMany->getArguments()['targetEntity']);
            $result = end($explode);
        } elseif ($manyToMany !== null) {
            $explode = explode('\\Entity\\', $manyToMany->getArguments()['targetEntity']);
            $result = end($explode);
        }

        return $result;
    }

    private function getInversedBy(ReflectionProperty $property): ?string
    {
        $result = null;

        /** @var \ReflectionAttribute $manyToOne */
        $manyToOne = $property->getAttributes(ManyToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToOne */
        $oneToOne = $property->getAttributes(OneToOne::class)[0] ?? null;
        /** @var \ReflectionAttribute $oneToMany */
        $oneToMany = $property->getAttributes(OneToMany::class)[0] ?? null;
        /** @var \ReflectionAttribute $manyToMany */
        $manyToMany = $property->getAttributes(ManyToMany::class)[0] ?? null;

        if ($manyToOne !== null) {
            $inversedBy = $manyToMany?->getArguments()['inversedBy'] ?? null;
            if ($inversedBy !== null) {
                $explode = explode('\\Entity\\', $inversedBy);
                $result = end($explode);
            }
        } elseif ($oneToOne !== null) {
            $inversedBy = $manyToMany?->getArguments()['inversedBy'] ?? null;
            if ($inversedBy !== null) {
                $explode = explode('\\Entity\\', $inversedBy);
                $result = end($explode);
            }
        } elseif ($oneToMany !== null) {
            $inversedBy = $manyToMany?->getArguments()['inversedBy'] ?? null;
            if ($inversedBy !== null) {
                $explode = explode('\\Entity\\', $inversedBy);
                $result = end($explode);
            }
        } elseif ($manyToMany !== null) {
            $inversedBy = $manyToMany?->getArguments()['inversedBy'] ?? null;
            if ($inversedBy !== null) {
                $explode = explode('\\Entity\\', $inversedBy);
                $result = end($explode);
            }
        }

        return $result;
    }

    private function getMappedBy(ReflectionProperty $property)
    {
        $result = null;
        /** @var \ReflectionAttribute $oneToMany */
        $oneToMany = $property->getAttributes(OneToMany::class)[0] ?? null;

        if ($oneToMany !== null) {
            $mappedBy = $oneToMany?->getArguments()['mappedBy'] ?? null;
            if ($mappedBy !== null) {
                $explode = explode('\\Entity\\', $mappedBy);
                $result = end($explode);
            }
        }

        return $result;
    }

    private function getValidator(ReflectionProperty|ReflectionClass $property): array
    {
        $validators = [];
        $attributes = $property->getAttributes();
        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            $prefix = 'Symfony\Component\Validator\Constraints\\';

            if (str_starts_with($attributeName, $prefix)) {
                $shortName = substr($attributeName, strlen($prefix));

                $validators[$shortName] = $attribute->getArguments();
            }
        }

        return $validators;
    }

    public function checkController(string $moduleName, string $version, string $entityName): bool
    {
        $path = sprintf(
            '%s/%s/%s/Controller/%s/',
            $this->path,
            $moduleName,
            $version,
            $entityName,
        );

        if (is_dir($path)) {
            $files = scandir($path);
            // Убираем из списка "." и ".."
            $files = array_diff($files, array('.', '..'));
            return !empty($files);
        }

        return false;
    }

    public function getLastModifiedDate(string $moduleName, string $version, string $entityName): ?string
    {
        $path = sprintf(
            '%s/%s/%s/Entity/%s.php',
            $this->path,
            $moduleName,
            $version,
            $entityName
        );

        if (!file_exists($path)) {
            return null; // Возвращаем null, если файл не существует
        }
//1717759807
//1717759807
        $lastModifiedTime = filemtime($path);

        $date = new DateTime();
        $date->setTimestamp($lastModifiedTime);

        return $date->format('Y-m-d H:i:s');
    }
}