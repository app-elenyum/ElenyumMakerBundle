<?php

namespace Elenyum\Maker\Entity;

class CacheHashEntity
{
    /**
     * @var array - ['EntityName' => ['id', 'name', ...]]
     */
    private static array $hashEntity = [];

    static public function addEntity(string $entityName): void
    {
        if (!in_array($entityName, self::$hashEntity)) {
            self::$hashEntity[] = $entityName;
        }

    }

    static public function has(string $entityName, string $property): bool
    {
        if (!isset(self::$hashEntity[$entityName])) {
            return false;
        }
        if (!in_array($property, self::$hashEntity[$entityName])) {
            return false;
        }
        
        return true;
    }

    /**
     * @return array
     */
    public static function getHashEntity(): array
    {
        return self::$hashEntity;
    }
}