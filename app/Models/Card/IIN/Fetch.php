<?php

namespace RZP\Models\Card\IIN;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::IIN             => 'sometimes|integer|digits:6',
            Entity::NETWORK         => 'sometimes|alpha_space',
            Entity::INTERNATIONAL   => 'sometimes|in:0,1',
            Entity::EMI             => 'sometimes|in:0,1',
            Entity::TYPE            => 'sometimes|string|in:debit,credit,unknown',
            Entity::OTP_READ        => 'sometimes|in:0,1',
            Entity::ISSUER          => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::IIN,
            Entity::NETWORK,
            Entity::INTERNATIONAL,
            Entity::EMI,
            Entity::TYPE,
            Entity::OTP_READ,
            Entity::ISSUER,
        ],
    ];
}
