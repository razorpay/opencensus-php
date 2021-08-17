<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            self::DELETED        => 'sometimes|string|in:0,1',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            self::COUNT,
            self::SKIP,
            self::DELETED,
        ],
    ];
}
