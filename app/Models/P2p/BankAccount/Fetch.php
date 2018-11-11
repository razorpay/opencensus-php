<?php

namespace Rzp\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                       => 'required|string|regex:^ba_(.*){14}$',
            Entity::ENTITY                   => 'required|string',
            Entity::IFSC                     => 'required|string|regex:^[\w]{11}$',
            Entity::BANK_NAME                => 'required|string|regex:^(.*)$',
            Entity::BENEFICIARY_NAME         => 'required|string|regex:^(.*)$',
            Entity::MASKED_ACCOUNT_NUMBER    => 'required|string|regex:^(.*)$',
            Entity::CREDS                    => 'required|string',
            Entity::CL_REGISTRATION_FORMAT   => 'required|string',
            Entity::REFRESHED_AT             => 'required|string',
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
