<?php

namespace RZP\Models\Payout\Batch;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BATCH_ID     => 'sometimes|unsigned_id|size:14',
            Entity::MERCHANT_ID  => 'sometimes|unsigned_id|size:14',
            Entity::STATUS       => 'sometimes|string|max:100',
            Entity::REFERENCE_ID => 'sometimes|string|max:40',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::BATCH_ID,
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::REFERENCE_ID,
        ],
    ];
}
