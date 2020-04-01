<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends Base\Fetch
{
    const RULES = [
       self::DEFAULTS => [
           Entity::PAYMENT_ID => 'sometimes|string',
       ]
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::PAYMENT_ID,
        ],
    ];
}
