<?php

namespace RZP\Models\External;

use RZP\Base;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\BankingAccountStatement\Channel;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::CHANNEL                   => 'required|string|custom',
        Entity::BANK_REFERENCE_NUMBER     => 'required|string',
        Entity::TYPE                      => 'required|string|custom',
        Entity::AMOUNT                    => 'required|integer',
        Entity::CURRENCY                  => 'required|size:3|in:INR',
    ];

    protected function validateType($attribute, $type)
    {
        Type::validate($type);
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::validate($channel);
    }
}
