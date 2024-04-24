<?php

namespace Elenyum\Maker\Entity;

interface EntityToArrayInterface
{
    public function toArray(array $inputGroups, array $fields = []): array;
}