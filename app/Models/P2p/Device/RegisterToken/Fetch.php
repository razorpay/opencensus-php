<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID          => 'sometimes|string',
            Entity::DEVICE_ID   => 'sometimes|string',
            Entity::STATUS      => 'sometimes|string',
            Entity::MERCHANT_ID => 'sometimes|alpha_num',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::DEVICE_ID,
        ],
    ];
}
