<?php

namespace Rzp\Models\P2p\BankAccount\Bank;

use RZP\Exception;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\P2p\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::IFSC             => 'required|string|regex:^[\w]{13}$',
            Entity::NAME             => 'required|string',
            Entity::UPI_IIN          => 'required|string|regex:^[\d]{6}$',
            Entity::UPI_FORMAT       => 'required|string',
            Entity::REFRESHED_AT     => 'required|string',
            Entity::SPOC             => 'required|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH   => [],
        AuthType::PROXY_AUTH     => [],
        AuthType::PRIVILEGE_AUTH => [],
        AuthType::ADMIN_AUTH     => [],
    ];
}
