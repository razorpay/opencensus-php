<?php

namespace RZP\Models\Offer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID         => 'sometimes|alpha_num',
            Entity::PAYMENT_METHOD      => 'sometimes|string',
            Entity::PAYMENT_METHOD_TYPE => 'sometimes|alpha',
            Entity::PAYMENT_NETWORK     => 'sometimes|alpha',
            Entity::ISSUER              => 'sometimes|alpha',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::PAYMENT_METHOD,
            Entity::PAYMENT_METHOD_TYPE,
            Entity::PAYMENT_NETWORK,
            Entity::ISSUER,
        ],
    ];
}
