<?php

namespace RZP\Models\UpiMandate;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ORDER_ID    => 'sometimes|alpha_num',
            Entity::MERCHANT_ID => 'sometimes|alpha_num',
            Entity::TOKEN_ID    => 'sometimes|alpha_num',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ORDER_ID,
            Entity::MERCHANT_ID,
            Entity::TOKEN_ID,
        ],
    ];
}
