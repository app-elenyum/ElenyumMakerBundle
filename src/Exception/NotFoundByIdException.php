<?php

namespace Elenyum\Maker\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NotFoundByIdException extends Exception
{
    public function __construct($id, $class, Throwable $previous = null)
    {
        $message = "Entity {$class} with ID {$id} was not found.";
        parent::__construct($message, Response::HTTP_EXPECTATION_FAILED, $previous);
    }
}