<?php

namespace Elenyum\Maker\Service\Module\Entity;

use Doctrine\DBAL\Types\Types;
use Exception;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Property;

class ServiceAddProperty implements ServiceAddToClass
{
    /**
     * @throws Exception
     */
    public function create(ClassType $class, array $dataColumn): ClassType
    {
        foreach ($dataColumn as $column) {
            $columnName = mb_strtolower($column['name']);
            $addProperty = $class->addProperty($columnName);
            $getPhpType = $this->getPhpType($column['info']['type'], $column['info']['targetEntity'] ?? null);
            $addProperty->setType($getPhpType);
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

    private function addGetter(ClassType $class, $propertyName, $phpType)
    {
        $getter = $class->addMethod('get'.ucfirst($propertyName));
        $getter->addBody('return $this->'.$propertyName.';');
        $getter->setReturnType($phpType);
    }

    private function addDoctrineAttributeForProperty(Property $property,  array $columnData): Property
    {
        $columnType = $columnData['info']['type'];
        if (isset($columnData['generatedId']) && $columnData['generatedId'] === true) {
            $property->addAttribute('ORM\Id');
            $property->addAttribute('ORM\GeneratedValue');
        }

        match ($columnType) {
            'integer' => $property->addAttribute('ORM\Column', ['type' => Types::INTEGER]),
            'float' => $property->addAttribute('ORM\Column', ['type' => Types::FLOAT]),
            'text' => $property->addAttribute('ORM\Column', ['type' => Types::TEXT]),
            'string' => $property->addAttribute('ORM\Column', ['type' => Types::STRING]),
            'json' => $property->addAttribute('ORM\Column', ['type' => Types::JSON]),
            'many-to-one' => $property->addAttribute('ORM\ManyToOne', [
                'targetEntity' => $columnData['info']['targetEntity'],
            ]),
            'one-to-one' => $property->addAttribute('ORM\OneToOne', [
                'targetEntity' => $columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['mappedBy']
            ]),
            'many-to-many' => $property->addAttribute('ORM\ManyToMany', [
                'targetEntity' => $columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['mappedBy']
            ]),
            'one-to-many' => $property->addAttribute('ORM\OneToMany', [
                'targetEntity' => $columnData['info']['targetEntity'],
                'mappedBy' => $columnData['info']['mappedBy']
            ]),
        };

        return $property;
    }

    /**
     * @throws Exception
     */
    private function getPhpType(string $sourceType, ?string $toEntity = null): string
    {
        $map = [
            'integer' => 'integer',
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
            dd($sourceType);
            throw new Exception('Undefined type of the property');
        }

        return $result;
    }
}