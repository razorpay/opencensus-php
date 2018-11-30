<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            //
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            //
        ],
        AuthType::PROXY_AUTH   => [
            //
        ],
    ];

    const ES_FIELDS = [
        //
    ];

    const COMMON_FIELDS = [
        //
    ];
}
