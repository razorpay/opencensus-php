<?php


namespace RZP\Models\VirtualAccountTpv;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::VIRTUAL_ACCOUNT_ID          => 'sometimes|alpha_num|size:14',
            Entity::ENTITY_TYPE                 => 'sometimes|string',
            Entity::ENTITY_ID                   => 'sometimes|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::VIRTUAL_ACCOUNT_ID,
            Entity::ENTITY_TYPE,
            Entity::ENTITY_ID,
        ],
    ];
}
