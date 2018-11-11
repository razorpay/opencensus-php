<?php

namespace Rzp\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID               => 'required|string|regex:^vpa_[\w]{14}$',
            Entity::ENTITY           => 'required|string|regex:^(.*)$',
            Entity::ADDRESS          => 'required|string|regex:^(.*)@(.*)$',
            Entity::USERNAME         => 'required|string|regex:^(.*)$',
            Entity::HANDLE           => 'required|string|regex:^(.*)$',
            Entity::BANK_ACCOUNT_ID  => 'required|string|regex:^ba_[\w]{14}$',
            Entity::CREATED_AT       => 'required|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH   => [],
        AuthType::PROXY_AUTH     => [],
        AuthType::PRIVILEGE_AUTH => [],
        AuthType::ADMIN_AUTH     => [],
    ];
}
