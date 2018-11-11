<?php

namespace Rzp\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                   => 'required|string|regex:^ctxn_[\w]{14}$',
            Entity::ENTITY               => 'required|string',
            Entity::TXN_ID               => 'required|string|regex:^(.*)$',
            Entity::STATUS               => 'required|string|regex:^(.*)$',
            Entity::AMOUNT               => 'required|string',
            Entity::DESCRIPTION          => 'required|string|regex:^(.*)$',
            Entity::TYPE                 => 'required|string|regex:^(.*)$',
            Entity::CURRENCY             => 'required|string|regex:^(.*)$',
            Entity::ERROR_DESCRIPTION    => 'required|string',
            Entity::ERROR_CODE           => 'required|string',
            Entity::TRANSACTION_TYPE     => 'required|string|regex:^(.*)$',
            Entity::RRN                  => 'required|string|regex:^(.*)$',
            Entity::CREATED_AT           => 'required|string',
            Entity::COMPLETED_AT         => 'required|string',
            Entity::EXPIRE_AT            => 'required|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH   => [],
        AuthType::PROXY_AUTH     => [],
        AuthType::PRIVILEGE_AUTH => [],
        AuthType::ADMIN_AUTH     => [],
    ];
}
