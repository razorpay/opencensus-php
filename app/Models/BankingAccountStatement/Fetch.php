<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID         => 'sometimes|unsigned_id',
            Entity::TRANSACTION_ID      => 'sometimes|unsigned_id',
            Entity::ACCOUNT_NUMBER      => 'sometimes|alpha_num|max:40',
            Entity::BANK_TRANSACTION_ID => 'sometimes|string',
            Entity::UTR                 => 'sometimes|string',
            Entity::ENTITY_ID           => 'sometimes|string',
            Entity::ENTITY_TYPE         => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::TRANSACTION_ID,
            Entity::ACCOUNT_NUMBER,
            Entity::BANK_TRANSACTION_ID,
            Entity::UTR,
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
        ],
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::TRANSACTION_ID,
        Entity::ACCOUNT_NUMBER,
        Entity::BANK_TRANSACTION_ID,
    ];
}
