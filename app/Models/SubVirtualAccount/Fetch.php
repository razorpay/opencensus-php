<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MASTER_MERCHANT_ID    => 'sometimes|unsigned_id',
            Entity::ACTIVE                => 'sometimes|bool',
        ],

        AuthType::ADMIN_AUTH => [
            Entity::MASTER_MERCHANT_ID    => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::MASTER_MERCHANT_ID,
            Entity::ACTIVE,
        ],
    ];
}
