<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                           => 'sometimes|filled|unsigned_id',
            Entity::MERCHANT_ID                  => 'sometimes|filled|unsigned_id',
            Entity::BALANCE_ID                   => 'sometimes|filled',
            Entity::TYPE                         => 'sometimes|filled',
            Entity::STATUS                       => 'sometimes|filled',
            Entity::IS_ACTIVE                    => 'sometimes|filled',
            Entity::PAYER_IFSC                   => 'sometimes|string|size:11',
            Entity::PAYER_ACCOUNT_NUMBER         => 'sometimes|filled',
            Entity::TRIMMED_PAYER_ACCOUNT_NUMBER => 'sometimes|filled'
        ]
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::TYPE,
            Entity::STATUS,
            Entity::IS_ACTIVE,
            Entity::PAYER_IFSC,
            Entity::PAYER_ACCOUNT_NUMBER,
            Entity::TRIMMED_PAYER_ACCOUNT_NUMBER,
            self::EXPAND_EACH,
        ]
    ];
}
