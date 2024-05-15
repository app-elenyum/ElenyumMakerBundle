<?php

namespace Elenyum\Maker\Service\Module\Handler;

interface ServiceExecuteInterface
{
    /**
     * @return array - return created files path
     */
    public function execute(array $data): array;
}