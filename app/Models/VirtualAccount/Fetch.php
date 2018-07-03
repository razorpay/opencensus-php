<?php

namespace RZP\Models\VirtualAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::STATUS      => 'sometimes|in:active,closed,paid',
            Entity::CUSTOMER_ID => 'sometimes|string|min:14|max:19',
            Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
            Entity::NOTES       => 'sometimes|notes_fetch',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::STATUS,
            Entity::NOTES,
            Entity::CUSTOMER_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
    ];

    const ES_FIELDS = [
        Entity::NOTES,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
    ];
}
