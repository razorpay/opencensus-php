<?php

namespace RZP\Models\Transfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::RECIPIENT               => 'sometimes|string|max:20',
            Entity::RECIPIENT_SETTLEMENT_ID => 'filled|string|public_id',
            self::EXPAND_EACH               => 'filled|string|in:recipient_settlement,',
            Entity::TRANSACTION_ID          => 'sometimes|alpha_num|size:14',
            Entity::MERCHANT_ID             => 'sometimes|alpha_num|size:14',
            Entity::SOURCE                  => 'sometimes|string|min:14',
            Entity::RECIPIENT               => 'sometimes|string|min:14'
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
            Entity::RECIPIENT_SETTLEMENT_ID,
            self::EXPAND_EACH,
        ],
    ];
}
