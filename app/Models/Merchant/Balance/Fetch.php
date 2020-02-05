<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TYPE           =>    'sometimes|string|max:32',
            Entity::MERCHANT_ID    =>    'sometimes|alpha_num|size:14',
            Entity::ACCOUNT_NUMBER =>    'sometimes|string',
        ]
    ];
    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
        ],
        AuthType::ADMIN_AUTH =>  [
            Entity::MERCHANT_ID,
            Entity::ACCOUNT_NUMBER,
        ]
    ];
}
