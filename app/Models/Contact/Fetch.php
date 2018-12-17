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
            Entity::EMAIL             => 'sometimes|email',
            Entity::NAME              => 'sometimes|string|max:50',
            Entity::CONTACT           => 'sometimes|contact_syntax',
            Entity::FUND_ACCOUNT_ID   => 'sometimes|string|min:14|max:19',
            Entity::ACCOUNT_NUMBER    => 'sometimes|alpha_num|between:5,22',
            EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::NAME,
            Entity::EMAIL,
            Entity::CONTACT,
            Entity::FUND_ACCOUNT_ID,
            Entity::ACCOUNT_NUMBER,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
        ],
    ];

    const ES_FIELDS = [
        Entity::NAME,
        Entity::EMAIL,
        EsRepository::QUERY,
        EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::NAME,
        Entity::EMAIL,
    ];

    const SIGNED_IDS = [
        Entity::FUND_ACCOUNT_ID,
    ];
}
