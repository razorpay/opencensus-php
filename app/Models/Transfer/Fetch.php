<?php

namespace RZP\Models\Transfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::RECIPIENT           => 'sometimes|string|max:20',
            self::EXPAND_EACH           => 'filled|string|in:recipient_settlement,',
            Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
            Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
            Entity::SOURCE              => 'sometimes|string|min:14',
            Entity::RECIPIENT           => 'sometimes|string|min:14'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::TRANSACTION_ID,
            Entity::MERCHANT_ID,
            Entity::SOURCE,
        ],

        AuthType::PRIVATE_AUTH => [
            Entity::RECIPIENT,
            self::EXPAND_EACH,
        ],
    ];
}
