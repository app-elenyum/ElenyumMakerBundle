<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Doctrine\DBAL\Types\Types;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;

class ServiceAddProperty implements ServiceAddToClassInterface, SetFullNamespaceInterface
{
    /**
     * @var string
     */
    private string $namespace;

    /**
     * @throws Exception
     */
    public function create(ClassType $class, array $data): ClassType
    {
        $dataColumn = $data['column'];
        $this->addConstruct($class, $dataColumn);
        foreach ($dataColumn as $column) {
            $columnName = lcfirst($column['name']);
            $addProperty = $class->addProperty($columnName);
            $getPhpType = $this->getPhpType($column['info']['type'], $column['info']['targetEntity'] ?? null);
            $addProperty->setType('?'.$getPhpType);
            $this->addDoctrineAttributeForProperty($addProperty, $column);
            $this->addSetter($class, $addProperty, $column);
            $this->addGetter($class, $addProperty, $column);
        }

        return $class;
    }

    private function addSetter(ClassType $class, Property $property, array $columnData): void
    {
        $propertyName = $property->getName();
        $phpType = $property->getType();

        $typeIsArrayCollection = [
            'one-to-many',
            'many-to-many',
        ];

        if (in_array($columnData['info']['type'], $typeIsArrayCollection)) {
            $methodAddName = 'add'.ucfirst($propertyName);
            $add = $class->addMethod($methodAddName);
            $add->addParameter($propertyName)->setType('\\'.$this->namespace.'\\'.$columnData['info']['targetEntity']);
            $add->addBody(
                sprintf('
if (!$this->%1$s->contains($%1$s)) {
    $this->%1$s->add($%1$s);
    $%1$s->set%2$s($this);
} 

return $this;
', $propertyName, ucfirst($columnData['info']['inversedBy']))
            );
            $add->setReturnType('self');

            $setter = $class->addMethod('set'.ucfirst($propertyName));
            $setter->addParameter('items')->setType('array');
            $setter->addBody(
                sprintf('
foreach ($items as $item) {
    $this->%1$s($item);
}

return $this;
', $methodAddName)
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

    private function addGetter(ClassType $class, Property $property, array $data): void
    {
        $propertyName = $property->getName();
        $phpType = $property->getType();

        $getter = $class->addMethod('get'.ucfirst($propertyName));
        $getter->addBody(sprintf('return $this->%s;', $propertyName));
        $getter->setReturnType('?'.$phpType);
    }

    private function addDoctrineAttributeForProperty(Property $property,  array $columnData): void
    {
        $columnType = $columnData['info']['type'];
        if (isset($columnData['info']['isPrimary']) && $columnData['info']['isPrimary'] === true) {
            $property->addAttribute('ORM\Id');
            $property->addAttribute('ORM\GeneratedValue');
        }

        match ($columnType) {
            'integer' => $property->addAttribute('ORM\Column', ['type' => Types::INTEGER, 'nullable' => true]),
            'float' => $property->addAttribute('ORM\Column', ['type' => Types::FLOAT, 'nullable' => true]),
            'text' => $property->addAttribute('ORM\Column', ['type' => Types::TEXT, 'nullable' => true]),
            'string' => $property->addAttribute('ORM\Column', ['type' => Types::STRING, 'nullable' => true]),
            'json' => $property->addAttribute('ORM\Column', ['type' => Types::JSON, 'nullable' => true]),
            /** @todo пока оставлю для примера в функции которая сама себя вызовет, для many-to-many может пригодится */
            'many-to-one' => (function (Property $property, $columnData) {
                $property->addAttribute('ORM\ManyToOne', [
                    'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                    'inversedBy' => $columnData['info']['inversedBy']
                ]);
                $property->addAttribute('ORM\JoinColumn', [
                    'name' => mb_strtolower($columnData['info']['inversedBy']).'_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'SET NULL',
                ]);
            })($property, $columnData),
            'one-to-one' => (function (Property $property, $columnData) {
                $property->addAttribute('ORM\OneToOne', [
                    'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                    'mappedBy' => $columnData['info']['inversedBy']
                ]);
                $property->addAttribute('ORM\JoinColumn', [
                    'name' => mb_strtolower($columnData['info']['inversedBy']).'_id',
                    'referencedColumnName' => 'id',
                    'onDelete' => 'SET NULL',
                ]);
            })($property, $columnData),

            'many-to-many' => (function (Property $property, $columnData) {
                $property->addAttribute('ORM\ManyToMany', [
                    'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                    'mappedBy' => $columnData['info']['inversedBy']
                ]);
            })($property, $columnData),
            'one-to-many' => (function (Property $property, $columnData) {
                $property->addAttribute('ORM\OneToMany', [
                    'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                    'mappedBy' => $columnData['info']['inversedBy']
                ]);
            })($property, $columnData),
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

    public function setFullNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    private function addConstruct(ClassType $class, mixed $dataColumn): void
    {
        $typeIsArrayCollection = [
            'one-to-many',
            'many-to-many',
        ];
        $body = [];
        foreach ($dataColumn as $column) {
            if (in_array($column['info']['type'], $typeIsArrayCollection)) {
                $body[] = sprintf('$this->%s = new ArrayCollection();', lcfirst($column['name']));
            }
        }
        if (!empty($body)) {
            $method = $class->addMethod('__construct');
            foreach ($body as $item) {
                $method->addBody($item);
            }
        }
    }
}