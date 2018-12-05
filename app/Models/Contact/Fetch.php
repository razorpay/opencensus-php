<?php

namespace RZP\Models\Contact;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Contact
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
