<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYOUT_ID              => 'required|size:14',
        Entity::AMOUNT                 => 'required|integer',
        Entity::TRANSACTION_ID         => 'required|size:14',
        Entity::CLOSING_BALANCE        => 'required|integer',
        Entity::TRANSACTION_CREATED_AT => 'required|integer'
    ];
}
