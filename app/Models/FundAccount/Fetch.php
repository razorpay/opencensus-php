<?php

namespace RZP\Models\FundAccount;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\FundAccount
 */
class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::SOURCE_ID    => 'sometimes|string',
            Entity::CUSTOMER_ID  => 'sometimes|string',
            Entity::CONTACT_ID   => 'sometimes|string',
            Entity::ACCOUNT_TYPE => 'sometimes|string',
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH    => 'filled|string|in:contact',
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
        Entity::CONTACT_ID,
    ];

    const ACCESSES = [
        self::DEFAULTS           => [
            Entity::ACCOUNT_TYPE,
        ],
        AuthType::PRIVATE_AUTH   => [
            Entity::CONTACT_ID,
            Entity::CUSTOMER_ID,
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::SOURCE_ID,
        ],
    ];

    const ES_FIELDS = [
        //
    ];

    const COMMON_FIELDS = [
        //
    ];
}
