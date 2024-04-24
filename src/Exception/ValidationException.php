<?php

namespace Elenyum\Maker\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, ?Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct('Invalid data', Response::HTTP_PRECONDITION_FAILED, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}