<?php

namespace Rzp\Models\P2p\Device;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID           => 'required|string|regex:^(.*)$',
            Entity::IMEI         => 'required|string|regex:^(.*)$',
            Entity::OS           => 'required|string|regex:^(.*)$',
            Entity::OS_VERSION   => 'required|string|regex:^(.*)$',
            Entity::APP_NAME     => 'required|string|regex:^(.*)$',
            Entity::HANDLE       => 'required|string|regex:^(.*)$',
            Entity::CL_TOKEN     => 'required|string',
            Entity::CL_PAYLOAD   => 'required|string',
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
