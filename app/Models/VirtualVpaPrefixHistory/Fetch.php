<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::VIRTUAL_VPA_PREFIX_ID       => 'sometimes|string|size:14',
            Entity::MERCHANT_ID                 => 'sometimes|string|size:14',
            Entity::IS_ACTIVE                   => 'sometimes|bool',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::VIRTUAL_VPA_PREFIX_ID,
            Entity::MERCHANT_ID,
            Entity::IS_ACTIVE,
        ],
    ];
}
