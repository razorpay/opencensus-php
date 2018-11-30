<?php

namespace RZP\Models\Beneficiary;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Beneficiary
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
