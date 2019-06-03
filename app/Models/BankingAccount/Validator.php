<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $availabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

     protected static $createRules = [
         Entity::CHANNEL => 'required|string|in:rbl',
         Entity::PINCODE => 'required_if:channel,rbl',
     ];
}
