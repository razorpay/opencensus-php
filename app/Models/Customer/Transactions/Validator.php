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


    public static function validateType($type)
    {
        if (defined(__CLASS__ . '::' . strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Transaction type: ' . $type);
        }
    }
}
