<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FUND_ACCOUNT              => 'required|associative_array',
        Entity::AMOUNT                    => 'sometimes|integer|min:100|max:200',
        Entity::NOTES                     => 'sometimes|notes',
        Entity::CURRENCY                  => 'filled|string|in:INR',
        Entity::RECEIPT                   => 'sometimes|string|min:1|max:40',
    ];
}
