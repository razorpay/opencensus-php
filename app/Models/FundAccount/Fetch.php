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
            Entity::CONTACT_ID   => 'sometimes|string',
            Entity::ACCOUNT_TYPE => 'sometimes|string',
        ],
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
    ];

    const ACCESSES = [
        self::DEFAULTS         => [
            Entity::CONTACT_ID,
            Entity::ACCOUNT_TYPE,
        ],
        AuthType::PRIVATE_AUTH => [
            //
        ],
        AuthType::PROXY_AUTH   => [
        ],
    ];

    const ES_FIELDS = [
        //
    ];

    const COMMON_FIELDS = [
        //
    ];
}
