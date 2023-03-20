<?php


namespace RZP\Models\CreditTransfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                => 'sometimes|unsigned_id',
            Entity::MERCHANT_ID       => 'sometimes|unsigned_id',
            Entity::BALANCE_ID        => 'sometimes|unsigned_id',
            Entity::ENTITY_ID         => 'sometimes|unsigned_id',
            Entity::UTR               => 'sometimes|unsigned_id',
            Entity::TRANSACTION_ID    => 'sometimes|unsigned_id',
            Entity::PAYER_MERCHANT_ID => 'sometimes|unsigned_id',
            self::EXPAND_EACH         => 'sometimes|in:payer_user,payer_merchant,merchant'
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::PAYER_MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::ENTITY_ID,
            Entity::UTR,
            Entity::TRANSACTION_ID
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::ENTITY_ID,
            Entity::UTR,
            Entity::TRANSACTION_ID
        ],

        AuthType::PROXY_AUTH => [
            Entity::MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::PAYER_MERCHANT_ID,
            self::EXPAND_EACH,
        ]
    ];
}
