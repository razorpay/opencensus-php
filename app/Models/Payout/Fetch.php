<?php

namespace RZP\Models\Payout;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Contact;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID              => 'sometimes|public_id|size:19',
            Entity::CONTACT_TYPE    => 'sometimes|string',
            Entity::MERCHANT_ID       => 'sometimes|unsigned_id',
            Entity::CUSTOMER_ID       => 'sometimes|public_id|size:19',
            Entity::DESTINATION       => 'sometimes|public_id|max:20',
            Entity::METHOD            => 'sometimes|string|custom',
            Entity::TRANSACTION_ID    => 'sometimes|public_id',
            Entity::UTR               => 'sometimes|string|max:255',
            Entity::CONTACT_NAME      => 'sometimes|string|max:50',
            Entity::CONTACT_PHONE     => 'sometimes|contact_syntax',
            Entity::CONTACT_ID        => 'sometimes|public_id|size:19',
            Entity::CONTACT_EMAIL     => 'sometimes|email|max:50',
            Entity::FUND_ACCOUNT_ID   => 'sometimes|public_id|size:17',
            Entity::STATUS          => 'sometimes|string|in:created,processed,reversed,processing,initiated'
            self::EXPAND_EACH         => 'filled|string|in:user',
            EsRepository::QUERY       => 'sometimes|string|min:1|max:50',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::TRANSACTION_ID,
            Entity::UTR,
            Entity::CONTACT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE,
            Entity::CONTACT_TYPE,
            Entity::CONTACT_EMAIL,
            Entity::FUND_ACCOUNT_ID,
            Entity::STATUS,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
        ],
        AuthType::PROXY_AUTH     => [
            self::EXPAND_EACH,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::DESTINATION,
            Entity::METHOD,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
        Entity::TRANSACTION_ID,
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];

    const ES_FIELDS = [
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        EsRepository::QUERY,
        EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
    ];


    protected function validateMethod(string $attribute, string $value)
    {
        Method::validateMethod($value);
    }
}
