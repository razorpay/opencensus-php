<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID         => 'sometimes|string|size:14',
            Entity::PREFIX              => 'sometimes|alpha_num|min:4|max:10',
            Entity::TERMINAL_ID         => 'sometimes|string|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::PREFIX,
            Entity::TERMINAL_ID,
        ],
    ];
}
