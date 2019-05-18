<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Models\P2p\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TRANSACTION_ID      => 'sometimes|string',
            self::EXPAND_EACH           => 'filled|string|in:transaction.payee,transaction.payer,transaction.upi',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::TRANSACTION_ID,
            self::EXPAND_EACH,
        ],
    ];
}
