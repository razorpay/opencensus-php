<?php

namespace App\Exceptions;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct($entity)
    {
        parent::__construct("Entity: $entity not found", 404);
    }
}
