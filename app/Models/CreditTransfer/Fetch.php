<?php


namespace RZP\Models\CreditTransfer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID             => 'sometimes|unsigned_id',
            Entity::MERCHANT_ID    => 'sometimes|unsigned_id',
            Entity::BALANCE_ID     => 'sometimes|unsigned_id',
            Entity::ENTITY_ID      => 'sometimes|unsigned_id',
            Entity::UTR            => 'sometimes|unsigned_id',
            Entity::TRANSACTION_ID => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::BALANCE_ID,
            Entity::ENTITY_ID,
            Entity::UTR,
            Entity::TRANSACTION_ID
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::UTR,
        ],
    ];
}
