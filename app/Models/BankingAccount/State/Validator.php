<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::STATUS          => 'required|string',
        Entity::BANK_STATUS     => 'sometimes|string',
    ];
}