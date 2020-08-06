<?php

namespace RZP\Models\Settlement\OndemandPayout;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                     => 'sometimes|public_id|size:22',
            Entity::MERCHANT_ID            => 'sometimes|unsigned_id',
            Entity::STATUS                 => 'sometimes|string',
            Entity::SETTLEMENT_ONDEMAND_ID => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::SETTLEMENT_ONDEMAND_ID,
        ],
    ];
}
