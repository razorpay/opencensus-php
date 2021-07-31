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
            self::EXPAND_EACH          => 'filled|string|in:payment,transaction.settlement',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::INTERNAL_STATUS             => 'sometimes|string',
            Entity::INTERNAL_RESPOND_BY_FROM    => 'sometimes|epoch',
            Entity::INTERNAL_RESPOND_BY_TO      => 'sometimes|epoch',
            Entity::ORDER_BY_INTERNAL_RESPOND   => 'sometimes|boolean',
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
        ],
        AuthType::ADMIN_AUTH => [
            self::EXPAND_EACH,
            Entity::INTERNAL_STATUS,
            Entity::INTERNAL_RESPOND_BY_FROM,
            Entity::INTERNAL_RESPOND_BY_TO,
            Entity::ORDER_BY_INTERNAL_RESPOND,
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH,
        ],
    ];


    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
    ];
}
