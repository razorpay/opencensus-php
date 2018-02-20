<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID    => 'sometimes|alpha_num|size:14',
            Entity::ACTIVE         => 'sometimes|in:0,1',
            Entity::APPLICATION_ID => 'sometimes|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::APPLICATION_ID,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::ACTIVE,
        ],
    ];
}
