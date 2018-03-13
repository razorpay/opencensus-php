<?php

namespace RZP\Models\Dispute;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::STATUS             => 'sometimes|string',
            Entity::PAYMENT_ID         => 'sometimes|string|size:18',
            Entity::PHASE              => 'sometimes|string',
            Entity::AMOUNT             => 'sometimes|integer',
            Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::STATUS,
            Entity::PAYMENT_ID,
            Entity::PHASE,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::AMOUNT,
            Entity::MERCHANT_ID,
        ]
    ];


    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
    ];
}
