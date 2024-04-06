<?php

namespace Elenyum\Maker\Service\Module\Handler;

interface ServiceCreateInterface
{
    /**
     * @return array - return created files path
     */
    public function create(array $data): array;
}