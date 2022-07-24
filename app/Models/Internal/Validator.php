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
        Entity::MODE             => 'required|filled|string',
        Entity::BANK_NAME        => 'nullable|string',
        Entity::ENTITY_ID        => 'required|filled|string',
        Entity::ENTITY_TYPE      => 'required|filled|string',
        Entity::REMARKS          => 'sometimes|nullable|string|in:test_payout,ondemand_settlement_xva_payout',
    ];
}
