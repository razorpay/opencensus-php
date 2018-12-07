<?php

namespace RZP\Models\Payout;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID     => 'sometimes|alpha_num',
            Entity::CUSTOMER_ID     => 'sometimes|string|max:19',
            Entity::DESTINATION     => 'sometimes|string|max:20',
            Entity::METHOD          => 'sometimes|string',
            Entity::FUND_ACCOUNT_ID => 'sometimes|string|max:17',
            self::EXPAND_EACH       => 'filled|string|in:user',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH     => [
            self::EXPAND_EACH,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::FUND_ACCOUNT_ID,
            Entity::DESTINATION,
            Entity::METHOD,
        ],
    ];
}
