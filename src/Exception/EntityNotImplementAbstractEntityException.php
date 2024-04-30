<?php

namespace Elenyum\Maker\Exception;

use Elenyum\Maker\Entity\AbstractEntity;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EntityNotImplementAbstractEntityException extends Exception
{
    public function __construct($class, Throwable $previous = null)
    {
        $message = sprintf('Entity %s not implement %s', $class, AbstractEntity::class);
        parent::__construct($message, Response::HTTP_EXPECTATION_FAILED, $previous);
    }
}