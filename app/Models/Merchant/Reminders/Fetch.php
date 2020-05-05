<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID     => 'sometimes|string',
            Entity::REMINDER_ID     => 'sometimes|string',
            Entity::REMINDER_STATUS => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::MERCHANT_ID,
            Entity::REMINDER_ID,
            Entity::REMINDER_STATUS,
        ],
    ];
}
