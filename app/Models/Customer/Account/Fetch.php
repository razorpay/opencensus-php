<?php

namespace RZP\Models\Customer;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID       => 'sometimes|alpha_num',
            Entity::EMAIL             => 'sometimes|email',
            Entity::ACTIVE            => 'sometimes|in:0,1',
            Entity::CONTACT           => 'sometimes',
            EsRepository::QUERY       => 'sometimes|string|min:2|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
        ],
        AuthType::PROXY_AUTH => [
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
            Entity::CONTACT,
            Entity::EMAIL,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::ACTIVE,
        ],
    ];

    const ES_FIELDS = [
        EsRepository::QUERY,
        EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::EMAIL,
        Entity::ACTIVE,
        Entity::CONTACT,
        Entity::GSTIN,
    ];
}
