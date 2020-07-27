<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BANK_TRANSFER_ID        => 'sometimes|string|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::BANK_TRANSFER_ID,
        ],
    ];
}
