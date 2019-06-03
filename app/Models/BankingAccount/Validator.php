<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $preCreateRules = [
        Entity::CHANNEL => 'required|string',
    ];

    protected static $rblAvailabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $createRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];
}
