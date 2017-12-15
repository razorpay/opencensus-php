<?php

namespace RZP\Models\Adjustment;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID     => 'sometimes|alpha_num',
            Entity::TRANSACTION_ID  => 'sometimes|alpha_dash',
            Entity::SETTLEMENT_ID   => 'sometimes|alpha_dash',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::TRANSACTION_ID,
            Entity::SETTLEMENT_ID,
        ],
    ];
}
