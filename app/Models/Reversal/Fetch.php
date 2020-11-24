<?php

namespace RZP\Models\Reversal;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_TYPE     => 'sometimes|string|max:255',
            Entity::ENTITY_ID       => 'sometimes|string|size:14',
            Entity::TRANSACTION_ID  => 'sometimes|alpha_num|size:14',
            Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
            self::EXPAND_EACH       => 'filled|string|in:transaction.settlement',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::TRANSACTION_ID,
            Entity::MERCHANT_ID,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::ENTITY_TYPE,
            Entity::ENTITY_ID,
        ],
        AuthType::PROXY_AUTH => [
           self::EXPAND_EACH,
        ],
    ];
}
