<?php

namespace RZP\Models\UpiTransfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::PAYMENT_ID         => 'sometimes|string|min:14|max:18',
            Entity::VIRTUAL_ACCOUNT_ID => 'sometimes|string|min:14|max:17',
            Entity::NPCI_REFERENCE_ID  => 'sometimes|string',
            Entity::PAYER_VPA          => 'sometimes|string',
            Entity::PAYEE_VPA          => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::PAYMENT_ID,
            Entity::VIRTUAL_ACCOUNT_ID,
            Entity::NPCI_REFERENCE_ID,
            Entity::PAYER_VPA,
            Entity::PAYEE_VPA,
        ],
    ];

    const SIGNED_IDS = [
        Entity::PAYMENT_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
    ];
}
