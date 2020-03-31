<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
        ],
        AuthType::ADMIN_AUTH => [
            self::EXPAND_EACH    => 'filled|string|in:steps,steps.role,workflow',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            self::EXPAND_EACH
        ],
    ];
}
