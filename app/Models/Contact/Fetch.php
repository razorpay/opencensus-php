<?php

namespace RZP\Models\Contact;

use RZP\Base;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Contact
 */
class Fetch extends Base\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                => 'sometimes|public_id|size:19',
            Entity::EMAIL             => 'sometimes|email',
            Entity::NAME              => 'sometimes|string|max:50',
            Entity::CONTACT           => 'sometimes|contact_syntax',
            Entity::REFERENCE_ID      => 'sometimes|string|max:40',
            Entity::FUND_ACCOUNT_ID   => 'sometimes|string|public_id|size:17',
            Entity::BATCH_ID          => 'sometimes|string|public_id|size:20',
            Entity::ACCOUNT_NUMBER    => 'sometimes|alpha_num|between:5,22',
            Entity::ACTIVE            => 'sometimes|bool',
            Entity::TYPE              => 'sometimes|string',
            EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::BATCH_ID            => 'sometimes|string|min:14|max:20',
            Entity::FUND_ACCOUNT_ID     => 'sometimes|string|min:14|max:17',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::NAME,
            Entity::EMAIL,
            Entity::CONTACT,
            Entity::REFERENCE_ID,
            Entity::FUND_ACCOUNT_ID,
            Entity::ACTIVE,
            Entity::TYPE,
            Entity::ACCOUNT_NUMBER,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
        ],
        AuthType::PROXY_AUTH => [
            Entity::ID,
            Entity::BATCH_ID,
        ],
    ];

    const ES_FIELDS = [
        Entity::NAME,
        Entity::EMAIL,
        Entity::CONTACT,
        EsRepository::QUERY,
        EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::ACTIVE,
        Entity::TYPE,
    ];

    const SIGNED_IDS = [
        Entity::FUND_ACCOUNT_ID,
    ];
}
