<?php

namespace Rzp\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                       => 'required|string|regex:^(.*)$',
            Entity::ENTITY                   => 'required|string',
            Entity::BENEFICIARY_NAME         => 'required|string',
            Entity::ADDRESS                  => 'sometimes|string',
            Entity::USERNAME                 => 'sometimes|string',
            Entity::HANDLE                   => 'sometimes|string',
            Entity::MASKED_ACCOUNT_NUMBER    => 'sometimes|string',
            Entity::IFSC_CODE                => 'sometimes|string',
            Entity::BANK_NAME                => 'sometimes|string',
            Entity::CREATED_AT               => 'required|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH   => [],
        AuthType::PROXY_AUTH     => [],
        AuthType::PRIVILEGE_AUTH => [],
        AuthType::ADMIN_AUTH     => [],
    ];
}
