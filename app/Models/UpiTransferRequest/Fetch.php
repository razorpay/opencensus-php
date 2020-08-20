<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::IS_CREATED          => 'sometimes|boolean',
            Entity::NPCI_REFERENCE_ID   => 'sometimes|string|max:40',
            Entity::PAYEE_VPA           => 'sometimes|string|max:40',
            Entity::GATEWAY             => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::IS_CREATED,
            Entity::NPCI_REFERENCE_ID,
            Entity::PAYEE_VPA,
            Entity::GATEWAY,
        ],
    ];
}
