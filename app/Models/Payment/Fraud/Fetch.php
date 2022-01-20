<?php

namespace RZP\Models\Payment\Fraud;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PAYMENT_ID      => 'sometimes|string|size:14',
            Entity::REPORTED_BY     => 'sometimes|string',
            Entity::BATCH_ID        => 'sometimes|string|size:14',
            Entity::ARN             => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            self::COUNT,
            self::SKIP,
            Entity::ARN,
            Entity::PAYMENT_ID,
            Entity::REPORTED_BY
        ],
        AuthType::PRIVILEGE_AUTH => [
            self::COUNT,
            self::SKIP,
            Entity::PAYMENT_ID,
            Entity::REPORTED_BY,
            Entity::BATCH_ID,
        ],
    ];
}
