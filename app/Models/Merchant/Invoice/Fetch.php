<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [],
        AuthType::PROXY_AUTH => [
            Entity::YEAR        => 'sometimes|digits:4',
            Entity::TYPE        => 'sometimes',
            Entity::MONTH       => 'sometimes|integer|min:1|max:12',
            Entity::BALANCE_ID  => 'sometimes|unsigned_id',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH     => [
            Entity::YEAR,
            Entity::TYPE,
            Entity::MONTH,
            Entity::BALANCE_ID,
        ],
    ];

}
