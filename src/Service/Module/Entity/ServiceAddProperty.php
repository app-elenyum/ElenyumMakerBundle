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
        foreach ($dataColumn as $column) {
            $columnName = mb_strtolower($column['name']);
            $addProperty = $class->addProperty($columnName);
            $getPhpType = $this->getPhpType($column['info']['type'], $column['info']['targetEntity'] ?? null);
            $addProperty->setType('?'.$getPhpType);
            $this->addDoctrineAttributeForProperty($addProperty, $column);
            $this->addSetter($class, $columnName, $getPhpType);
            $this->addGetter($class, $columnName, $getPhpType);
        }

        return $class;
    }

    private function addSetter(ClassType $class, $propertyName, $phpType)
    {
        $setter = $class->addMethod('set'.ucfirst($propertyName));
        $setter->addParameter($propertyName)->setType($phpType);
        $setter->addBody(
            '$this->'.$propertyName.' = $'.$propertyName.';'.
            PHP_EOL.
            PHP_EOL.
            'return $this;'
        );
        $setter->setReturnType('self');
    }

    private function addGetter(ClassType $class, $propertyName, $phpType): void
    {
        $getter = $class->addMethod('get'.ucfirst($propertyName));
        $getter->addBody('return $this->'.$propertyName.';');
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
            'integer' => $property->addAttribute('ORM\Column', ['type' => Types::INTEGER]),
            'float' => $property->addAttribute('ORM\Column', ['type' => Types::FLOAT]),
            'text' => $property->addAttribute('ORM\Column', ['type' => Types::TEXT]),
            'string' => $property->addAttribute('ORM\Column', ['type' => Types::STRING]),
            'json' => $property->addAttribute('ORM\Column', ['type' => Types::JSON]),
            /** @todo пока оставлю для примера в функции которая сама себя вызовет, для many-to-many может пригодится */
            'many-to-one' => (fn() => $property->addAttribute('ORM\ManyToOne', [
                'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                'inversedBy' => $columnData['info']['inversedBy'],
                'cascade' => ['persist']
            ]))(),
            'one-to-one' => $property->addAttribute('ORM\OneToOne', [
                'targetEntity' => $this->namespace.'\\'.$columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['inversedBy'],
                'cascade' => ['persist']
            ]),
            'many-to-many' => $property->addAttribute('ORM\ManyToMany', [
                'targetEntity' =>  $this->namespace.'\\'.$columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['inversedBy'],
                'cascade' => ['persist']
            ]),
            'one-to-many' => $property->addAttribute('ORM\OneToMany', [
                'targetEntity' =>  $this->namespace.'\\'.$columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['inversedBy'],
                'cascade' => ['persist']
            ]),
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
}