<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CHANNEL             => 'required|string|custom',
        Entity::MERCHANT_ID         => 'required|string|size:14',
        Entity::ACCOUNT_NUMBER      => 'required|string|max:40',
        Entity::BANK_TRANSACTION_ID => 'required|string',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'required|size:3',
        Entity::TYPE                => 'required|string|custom',
        Entity::DESCRIPTION         => 'required|string',
        Entity::CATEGORY            => 'required|string|custom',
        Entity::BANK_SERIAL_NUMBER  => 'required|string',
        Entity::BANK_INSTRUMENT_ID  => 'sometimes|string',
        Entity::BALANCE             => 'required|integer',
        Entity::BALANCE_CURRENCY    => 'required|size:3',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_TYPE         => 'sometimes|string',
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14|unique:transactions',
        Entity::POSTED_DATE         => 'required|integer',
        Entity::TRANSACTION_DATE    => 'required|integer',
    ];

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }

    protected function validateType($attribute, $type)
    {
        Type::validate($type);
    }

    protected function validateCategory($attribute, $category)
    {
        Category::validate($category);
    }
}
