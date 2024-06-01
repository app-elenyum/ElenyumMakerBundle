<?php

namespace Elenyum\Maker\HttpFoundation;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;


/** @todo это может быть полезно, если использовать везде, если доступно удаление или изменение по другому параметру */
class Request extends SymfonyRequest
{
    protected function getOffset(): ?int
    {
        return $this->get('offset', 0);
    }

    protected function getLimit(): ?int
    {
        return $this->get('limit', 10);
    }

    /**
     * @throws \JsonException
     */
    protected function getFilter(): ?array
    {
        $getFilter = $this->get('filter');
        return !empty($getFilter) ? json_decode(
            $getFilter,
            JSON_OBJECT_AS_ARRAY,
            512,
            JSON_THROW_ON_ERROR
        ) : [];
    }

    protected function getSort(): ?array
    {
        $getSort = $this->get('sort');

        return !empty($getSort) ? explode(',', $getSort) : [];
    }

    protected function getField(): ?array
    {
        $getField = $this->get('field');

        return !empty($getField) ? explode(',', $getField) : [];
    }
}