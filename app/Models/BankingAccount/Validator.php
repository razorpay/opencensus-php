<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $availabilityRules = [
        Entity::BANK            => 'required|string|in:rbl',
        Entity::PINCODE         => 'required_if:bank,rbl',
    ];

     protected static $createRules = [
         Entity::BANK            => 'required|string|in:rbl',
         Entity::PINCODE         => 'required_if:bank,rbl',
     ];
}
