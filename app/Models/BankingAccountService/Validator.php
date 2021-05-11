<?php

namespace RZP\Models\BankingAccountService;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::CHANNEL        => 'required|string|custom',
        Constants::ACCOUNT_NUMBER => 'required|string',
    ];

    protected static $businessRules = [
        Constants::BUSINESS_ID => 'required|string|max:14',
    ];

    protected function validateChannel($attribute, $channel)
    {
        Channel::isValidDirectTypeChannel($channel);
    }
}
