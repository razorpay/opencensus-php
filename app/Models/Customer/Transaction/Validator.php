<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Base;

class Validator extends Base\Validator
{
    public static function validateType($type)
    {
        if (defined(__CLASS__ . '::' . strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid type: ' . $type);
        }
    }
}
