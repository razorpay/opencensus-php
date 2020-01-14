<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE        => 'sometimes|string|max:32',
        ],
    ];
    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
        ],
    ];
}
