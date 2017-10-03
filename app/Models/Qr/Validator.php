<?php

namespace RZP\Models\Qr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD                => 'required|string|in:upi,card',
        Entity::AMOUNT                => 'required|string',
        Entity::VPA                   => 'sometimes|string',
        Entity::CARD_NUMBER           => 'sometimes|string',
        Entity::CARD_NETWORK          => 'sometimes|string',
        Entity::PROVIDER              => 'sometimes|string|max:255',
        Entity::PROVIDER_REFERENCE_ID => 'sometimes|string',
        Entity::MERCHANT_REFERENCE    => 'sometimes|string',
        Entity::TRACE_NUMBER          => 'sometimes|string',
        Entity::RRN                   => 'required|string',
        Entity::STATUS_CODE           => 'sometimes|string',
        Entity::CUSTOMER_NAME         => 'sometimes|string',
        Entity::TRANSACTION_TIME      => 'sometimes|string',
        Entity::TRANSACTION_DATE      => 'sometimes|string',
        Entity::GATEWAY_MERCHANT_ID   => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID   => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_DESC => 'sometimes|string',
    ];
}
