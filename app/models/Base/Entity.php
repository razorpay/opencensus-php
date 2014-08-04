<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Entity extends EloquentEx
{
    protected function asDateTime($value)
    {
        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and return as it is. Otherwise we will call the parent function to handle it.
        if (is_numeric($value))
        {
            return $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
    }
}
