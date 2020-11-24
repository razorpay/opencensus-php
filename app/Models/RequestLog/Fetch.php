<?php

namespace RZP\Models\RequestLog;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID             => 'sometimes|unsigned_id|size:14',
            Entity::MERCHANT_ID    => 'sometimes|unsigned_id|size:14',
            Entity::ROUTE_NAME     => 'sometimes|string|max:100',
            Entity::ENTITY_ID      => 'sometimes|public_id',
            Entity::ENTITY_TYPE    => 'sometimes|string|max:100',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::ROUTE_NAME,
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
        ],
    ];
}
