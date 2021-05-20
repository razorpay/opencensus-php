<?php

namespace RZP\Models\Reward\MerchantReward;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID          => 'sometimes|string',
            Entity::REWARD_ID            => 'sometimes|string|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::REWARD_ID,
        ],
    ];
}
