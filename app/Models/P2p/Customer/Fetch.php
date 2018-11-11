<?php

namespace Rzp\Models\P2p\Customer;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID           => 'required|string|regex:^cust_\w{14}$',
            Entity::CONTACT      => 'required|string|regex:^\+91(\d*){10}$',
            Entity::EMAIL        => 'required|string',
            Entity::ACTIVE       => 'required|string',
            Entity::NOTES        => 'required|array',
            Entity::CREATED_AT   => 'required|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH   => [],
        AuthType::PROXY_AUTH     => [],
        AuthType::PRIVILEGE_AUTH => [],
        AuthType::ADMIN_AUTH     => [],
    ];
}
