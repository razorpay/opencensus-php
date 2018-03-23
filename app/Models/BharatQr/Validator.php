<?php

namespace RZP\Models\BharatQr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD                => 'required|string|in:upi,card',
        Entity::AMOUNT                => 'required|integer',
        Entity::PROVIDER_REFERENCE_ID => 'required|string|max:20',
        Entity::MERCHANT_REFERENCE    => 'required|string',
    ];
}

