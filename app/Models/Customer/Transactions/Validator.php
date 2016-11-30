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
}
