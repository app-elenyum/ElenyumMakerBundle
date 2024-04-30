<?php

namespace Elenyum\Maker\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvalidIdKeyUsageException extends \Exception
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(
            'Cannot use the \'id\' key if you want to add an entity. Please use the "PUT" method for updates or remove the \'id\' key from your request.',
            Response::HTTP_CONFLICT,
            $previous
        );
    }
}