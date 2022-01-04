<?php

namespace RZP\Models\Internal;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID      => 'required|filled|string|size:14',
        Entity::UTR              => 'required|filled|string',
        Entity::AMOUNT           => 'required|filled|integer',
        Entity::BASE_AMOUNT      => 'required|filled|integer',
        Entity::CURRENCY         => 'required|filled|string|size:3',
        Entity::TYPE             => 'required|filled|string|in:debit,credit',
        Entity::TRANSACTION_DATE => 'required|filled|integer',
    ];

    protected static $reconcileRules = [
        Entity::STATUS        => 'required|filled|string|in:failed,received',
        Entity::RECONCILED_AT => 'required|filled|integer',
    ];
}
