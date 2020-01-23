<?php

namespace RZP\Models\Merchant\Balance\BalanceConfig;

use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID  => 'sometimes|alpha_num|size:14',
            Entity::TYPE        => 'sometimes|string|max:32',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID,
            Entity::TYPE,
        ],
    ];
}
