<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                  => 'sometimes|string',
            Entity::MERCHANT_ID         => 'sometimes|string',
            Entity::CUSTOMER_ID         => 'sometimes|string',
            Entity::TYPE                => 'sometimes|string',
            Entity::STATUS              => 'sometimes|string',
            Entity::FLOW                => 'sometimes|string',
            Entity::RESPONSE            => 'sometimes|string|in:history,pending,full',
            self::EXPAND_EACH           => 'filled|string|in:payer,payee,upi,bank_account,concern',
            Entity::DEVICE_ID           => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::TYPE,
            Entity::STATUS,
            Entity::FLOW,
            Entity::RESPONSE,
            self::EXPAND_EACH,
            Entity::DEVICE_ID,
        ],
    ];
}
