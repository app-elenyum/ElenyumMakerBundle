<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Doctrine\DBAL\Types\Types;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

class ServiceAddProperty implements ServiceAddToClassInterface
{
    private array $dataEntity = [];

    /**
     * @param PhpNamespace $namespace
     * @param ClassType $class
     * @param array $data
     * @return ClassType
     * @throws Exception
     */
    public function create(PhpNamespace $namespace, ClassType $class, array $data): ClassType
    {
        $this->dataEntity['entity'] = $data['entity_name_lower'];
        $this->dataEntity['version'] = mb_strtolower($data['version_namespace']);
        $this->dataEntity['module'] = mb_strtolower($data['module_name_lower']);

        $dataColumn = $data['column'];
        $this->addConstruct($class, $dataColumn);
        foreach ($dataColumn as $column) {
            $columnName = lcfirst($column['camel_case_name']);
            $addProperty = $class->addProperty($columnName, null);

            $getPhpType = $this->getPhpType($column['info']['type'], $column['info']['targetEntity'] ?? null);
            $addProperty->setType('?'.$getPhpType);

            try {

            $this->addDoctrineAttributeForProperty($namespace, $addProperty, $column);
            $this->addSetter($namespace, $class, $addProperty, $column);
            $this->addGetter($namespace, $class, $addProperty, $column);

            }catch (Exception $e) {
                dd($e->getMessage(), $e->getFile(), $e->getLine());
            }
        }

        return $class;
    }

    private function addSetter(PhpNamespace $namespace, ClassType $class, Property $property, array $columnData): void
    {
        $propertyName = $property->getName();
        $phpType = $property->getType();

        $typeIsArrayCollection = [
            'one-to-many',
            'many-to-many',
        ];
        if (!empty(ucfirst($columnData['info']['targetEntity']))) {
            $namespace->addUse($namespace->getName().'\\'.ucfirst($columnData['info']['targetEntity']));
        }
//        $column = $columnData['info']['camel_case_mapped_by'] ?? $columnData['info']['camel_case_inversed_by'];
        if (in_array($columnData['info']['type'], $typeIsArrayCollection)) {
            $methodAddName = 'add'.ucfirst($propertyName);
            $add = $class->addMethod($methodAddName);
            $add->addParameter($propertyName)->setType(ucfirst($columnData['info']['targetEntity']));

            $add->addBody(
                sprintf(
                    '
if (!$this->%1$s->contains($%1$s)) {
    $this->%1$s->add($%1$s);
} 

return $this;
',
                    $propertyName,
                )
            );
            $add->setReturnType('self');

            $setter = $class->addMethod('set'.ucfirst($propertyName));
            $setter->addParameter('items')->setType('array');
            $setter->addBody(
                sprintf(
                    '
foreach ($items as $item) {
    $this->%1$s($item);
}

return $this;
',
                    $methodAddName
                )
            );
        } else {
            $setter = $class->addMethod('set'.ucfirst($propertyName));
            $setter->addParameter($propertyName)->setType($phpType);
            $setter->addBody(
                '$this->'.$propertyName.' = $'.$propertyName.';'.
                PHP_EOL.
                PHP_EOL.
                'return $this;'
            );
        }
        $setter->setReturnType('self');
    }

    private function addGetter(PhpNamespace $namespace, ClassType $class, Property $property, array $data): void
    {
        $propertyName = $property->getName();
        $phpType = $property->getType();

        $getter = $class->addMethod('get'.ucfirst($propertyName));

        if ($phpType === 'Collection') {
            $phpType = 'array';
            $getter->addBody(sprintf('return $this->%s->toArray();', $propertyName));
        } else {
            $getter->addBody(sprintf('return $this->%s;', $propertyName));
        }
        $getter->setReturnType('?'.$phpType);
    }

    private function addDoctrineAttributeForProperty(
        PhpNamespace $namespace,
        Property $property,
        array $columnData
    ): void {
        $columnType = $columnData['info']['type'];
        if (isset($columnData['info']['isPrimary']) && $columnData['info']['isPrimary'] === true) {
            $property->addAttribute('ORM\Id');
            $property->addAttribute('ORM\GeneratedValue');
        }

        match ($columnType) {
            'integer' => $property->addAttribute('ORM\Column', ['name' => $columnData['name'], 'type' => Types::INTEGER, 'nullable' => true]),
            'float' => $property->addAttribute('ORM\Column', ['name' => $columnData['name'], 'type' => Types::FLOAT, 'nullable' => true]),
            'text' => $property->addAttribute('ORM\Column', ['name' => $columnData['name'], 'type' => Types::TEXT, 'nullable' => true]),
            'string' => $property->addAttribute('ORM\Column', ['name' => $columnData['name'], 'type' => Types::STRING, 'nullable' => true]),
            'json' => $property->addAttribute('ORM\Column', ['name' => $columnData['name'], 'type' => Types::JSON, 'nullable' => true]),
            /** @todo пока оставлю для примера в функции которая сама себя вызовет, для many-to-many может пригодится */
            'many-to-one' => (function (PhpNamespace $namespace, Property $property, $columnData) {
                $property->addAttribute('ORM\ManyToOne', [
                    'targetEntity' => $namespace->getName().'\\'.$columnData['info']['targetEntity'],
                    'inversedBy' => lcfirst($columnData['info']['mappedBy'] ?? $columnData['info']['inversedBy']),
                ]);
                $property->addAttribute('ORM\JoinColumn', [
                    'name' => lcfirst($columnData['name']).'_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'onDelete' => 'SET NULL',
                ]);
            })(
                $namespace,
                $property,
                $columnData
            ),
            'one-to-one' => (function (PhpNamespace $namespace, Property $property, $columnData) {
                $parameters['targetEntity'] = $namespace->getName().'\\'.$columnData['info']['targetEntity'];
                if (isset($columnData['info']['mappedBy'])) {
                    $parameters['inversedBy'] = lcfirst($columnData['info']['mappedBy']);
                } else {
                    $parameters['inversedBy'] = lcfirst($columnData['info']['inversedBy']);
                    $property->addAttribute('ORM\JoinColumn', [
                        'name' => lcfirst($columnData['name']),
                        'referencedColumnName' => 'id',
                        'nullable' => true,
                        'onDelete' => 'SET NULL',
                    ]);
                }
                $property->addAttribute('ORM\OneToOne', $parameters);
            })(
                $namespace,
                $property,
                $columnData
            ),

            'many-to-many' => (function (PhpNamespace $namespace, Property $property, $columnData) {
                $parameters['targetEntity'] = $namespace->getName().'\\'.ucfirst($columnData['info']['targetEntity']);
                if (isset($columnData['info']['mappedBy'])) {
                    $parameters['mappedBy'] = lcfirst($columnData['info']['mappedBy']);
                } else {
                    $property->addAttribute(
                        'ORM\JoinTable',
                        ['name' => $this->prepareTableName($columnData['info']['targetEntity'])]
                    );
                    $property->addAttribute('ORM\JoinColumn', ['name' => lcfirst($columnData['name']), 'nullable' => true]);

                    $parameters['inversedBy'] = $columnData['info']['inversedBy'];
                }

                $property->addAttribute('ORM\ManyToMany', $parameters);
            })(
                $namespace,
                $property,
                $columnData
            ),
            'one-to-many' => (function (PhpNamespace $namespace, Property $property, $columnData) {

                $property->addAttribute('ORM\OneToMany', [
                    'targetEntity' => $namespace->getName().'\\'.$columnData['info']['targetEntity'],
                    'mappedBy' => lcfirst($columnData['info']['mappedBy'] ?? $columnData['info']['inversedBy']),
                ]);
            })(
                $namespace,
                $property,
                $columnData
            ),
        };
    }

    /**
     * @throws Exception
     */
    private function getPhpType(string $sourceType, ?string $toEntity = null): string
    {
        $map = [
            'integer' => 'int',
            'float' => 'float',
            'text' => 'string',
            'string' => 'string',
            'json' => 'array',
            'many-to-one' => $toEntity,
            'one-to-one' => $toEntity,
            'many-to-many' => 'Collection',
            'one-to-many' => 'Collection',
        ];

        $result = $map[$sourceType] ?? null;
        if ($result === null) {
            throw new Exception('Undefined type of the property');
        }

        return $result;
    }

//    public function setFullNamespace(string $namespace): void
//    {
//        $this->namespace = $namespace;
//    }

    private function addConstruct(ClassType $class, mixed $dataColumn): void
    {
        $typeIsArrayCollection = [
            'one-to-many',
            'many-to-many',
        ];
        $body = [];
        foreach ($dataColumn as $column) {
            if (in_array($column['info']['type'], $typeIsArrayCollection)) {
                $body[] = sprintf('$this->%s = new ArrayCollection();', lcfirst($column['camel_case_name']));
            }
        }
        if (!empty($body)) {
            $method = $class->addMethod('__construct');
            foreach ($body as $item) {
                $method->addBody($item);
            }
        }
    }

    private function prepareTableName(string $entity): string
    {
        $result = $this->dataEntity['entity'].'_'.$entity;

        $result .= '__'.$this->dataEntity['version'];
        $result .= '__'.$this->dataEntity['module'];

        return mb_strtolower($result);
    }

}