<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                      => 'sometimes|string',
            Entity::DEVICE_ID               => 'sometimes|string',
            Entity::RRN                     => 'sometimes|string',
            Entity::STATUS                  => 'sometimes|string',
            Entity::REF_ID                  => 'sometimes',
            Entity::GATEWAY_REFERENCE_ID    => 'sometimes',
            Entity::GATEWAY_TRANSACTION_ID  => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::DEVICE_ID,
            Entity::RRN,
        ],
    ];
}
