<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID           => 'required',
        Entity::ENTITY_TYPE         => 'required',
        Entity::STATUS              => 'required',
        Entity::AMOUNT              => 'required',
        Entity::CURRENCY            => 'required',
        Entity::CREDIT              => 'required',
        Entity::DEBIT               => 'required',
        Entity::BALANCE             => 'required',
        Entity::DESCRIPTION         => 'required',
    ];

    protected static $getStatementRules = [
        Entity::CUSTOMER_ID         => 'required|string|size:19',
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
