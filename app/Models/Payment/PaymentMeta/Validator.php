<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY_AMOUNT             => 'sometimes|integer|min:0',
        Entity::GATEWAY_CURRENCY           => 'sometimes|string|size:3|custom',
        Entity::FOREX_RATE                 => 'sometimes|string|max:50',
    ];
}
