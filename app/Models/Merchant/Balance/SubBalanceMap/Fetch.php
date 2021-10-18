<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID       => 'sometimes|alpha_num|size:14',
            Entity::PARENT_BALANCE_ID => 'sometimes|alpha_num|size:14',
            Entity::CHILD_BALANCE_ID  => 'sometimes|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID,
            Entity::PARENT_BALANCE_ID,
            Entity::CHILD_BALANCE_ID,
        ],
    ];
}
