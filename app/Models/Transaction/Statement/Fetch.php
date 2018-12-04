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
            Entity::BALANCE_ID   => 'sometimes|alpha_num',
            self::EXPAND_EACH    => 'filled|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::BALANCE_ID,
            self::EXPAND_EACH
        ],
         AuthType::PRIVATE_AUTH => [
            Entity::BALANCE_ID,
            self::EXPAND_EACH
        ],
    ];
}
