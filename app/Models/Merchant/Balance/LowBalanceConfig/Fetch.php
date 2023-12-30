<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID   => 'sometimes|alpha_num|size:14',
            Entity::BALANCE_TYPE => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID,
            Entity::BALANCE_TYPE,
        ],
    ];
}
