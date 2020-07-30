<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::IS_CREATED      => 'sometimes|boolean',
            Entity::UTR             => 'sometimes|string|max:255',
            Entity::PAYEE_ACCOUNT   => 'sometimes|string|max:40',
            Entity::GATEWAY         => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::IS_CREATED,
            Entity::UTR,
            Entity::PAYEE_ACCOUNT,
            Entity::GATEWAY,
        ],
    ];
}
