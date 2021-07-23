<?php

namespace RZP\Models\Reward\RewardCoupon;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::REWARD_ID          => 'sometimes|string',
            Entity::COUPON_CODE        => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::REWARD_ID,
            Entity::COUPON_CODE,
        ],
    ];
}
