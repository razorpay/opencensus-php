<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $getStatementRules = [
        Entity::CUSTOMER_ID         => 'required|string|size:14',
        'from'                      => 'integer',
        'to'                        => 'integer',
        'count'                     => 'integer|min:1',
        'skip'                      => 'integer'
    ];

    public static function validateType($type)
    {
        if (defined(__CLASS__ . '::' . strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid type: ' . $type);
        }
    }
}
