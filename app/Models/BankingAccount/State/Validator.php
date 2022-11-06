<?php

namespace RZP\Models\BankingAccount\State;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::STATUS          => 'required|string',
        Entity::BANK_STATUS     => 'sometimes|string|nullable',
        Entity::SUB_STATUS      => 'sometimes|string|nullable',
        Entity::ASSIGNEE_TEAM   => 'sometimes|string|nullable',
    ];

    protected static $editRules = [
        Entity::STATUS          => 'sometimes|string',
        Entity::BANK_STATUS     => 'sometimes|string|nullable',
        Entity::SUB_STATUS      => 'sometimes|string|nullable',
        Entity::ASSIGNEE_TEAM   => 'sometimes|string|nullable',
    ];
}
