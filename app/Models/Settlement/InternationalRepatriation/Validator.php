<?php

namespace RZP\Models\Settlement\InternationalRepatriation;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::INTEGRATION_ENTITY     => 'required|string',
        Entity::AMOUNT                 => 'required|integer',
        Entity::CREDIT_AMOUNT          => 'required|integer',
        Entity::CURRENCY               => 'required|string|size:3',
        Entity::CREDIT_CURRENCY        => 'required|string|size:3',
        Entity::SETTLEMENT_IDS         => 'required',
        Entity::SETTLED_AT             => 'sometimes',
        Entity::UPDATED_AT             => 'sometimes',
        Entity::PARTNER_TRANSACTION_ID => 'sometimes',
        Entity::PARTNER_SETTLEMENT_ID  => 'sometimes',
        Entity::PARTNER_MERCHANT_ID    => 'sometimes',
        Entity::FOREX_RATE             => 'sometimes',
    ];

}
