<?php

namespace RZP\Models\P2p\Device;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID          => 'sometimes|string',
            Entity::CONTACT     => 'sometimes|string|max:12',
            Entity::CUSTOMER_ID => 'sometimes|alpha_num|size:14',
            Entity::MERCHANT_ID => 'sometimes|alpha_num',
        ],
        AuthType::PRIVILEGE_AUTH=> [
            Entity::CONTACT     => 'sometimes|string|max:12',
            Entity::CUSTOMER_ID => 'sometimes|alpha_num|size:14',
        ],
        AuthType::PRIVATE_AUTH=> [
            Entity::CONTACT     => 'required|string|max:12',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::CONTACT,
            Entity::CUSTOMER_ID,
        ],
        AuthType::PRIVATE_AUTH =>[
            Entity::CONTACT,
        ],
    ];
}
