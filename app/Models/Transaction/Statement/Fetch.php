<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Transaction;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Transaction\Statement
 */
class Fetch extends Transaction\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::BALANCE_ID => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::BALANCE_ID,
        ],
    ];
}
