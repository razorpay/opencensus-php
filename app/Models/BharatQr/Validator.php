<?php

namespace RZP\Models\BharatQr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD                => 'required|string|in:upi,card',
        Entity::GATEWAY               => 'required|string',
        Entity::AMOUNT                => 'required|integer',
        Entity::VPA                   => 'sometimes|string',
        Entity::CARD_FIRST6           => 'sometimes|string',
        Entity::CARD_LAST4            => 'sometimes|string',
        Entity::MERCHANT_REFERENCE    => 'required|string',
        Entity::PROVIDER_REFERENCE_ID => 'required|string',
    ];
}

