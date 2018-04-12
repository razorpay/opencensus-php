<?php

namespace RZP\Models\Risk;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID   => 'sometimes|alpha_num|size:14',
            Entity::PAYMENT_ID    => 'sometimes|alpha_dash|max:18',
            Entity::FRAUD_TYPE    => 'sometimes|string|max:20',
            Entity::SOURCE        => 'sometimes|string|max:20',
            Entity::RISK_SCORE    => 'sometimes|integer',
            Entity::REASON        => 'sometimes|string|max:150',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::PAYMENT_ID,
            Entity::FRAUD_TYPE,
            Entity::SOURCE,
            Entity::RISK_SCORE,
            Entity::REASON,
        ],
    ];

    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
    ];
}
