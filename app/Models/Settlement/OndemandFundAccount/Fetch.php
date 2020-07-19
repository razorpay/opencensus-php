<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                    => 'sometimes|public_id|size:20',
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
    ];
}
