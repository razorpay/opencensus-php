<?php

namespace RZP\Models\Contact;

use RZP\Base;
use RZP\Models\BankAccount;
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
            EsRepository::QUERY       => 'sometimes|string|min:1|max:100',
            EsRepository::SEARCH_HITS => 'sometimes|boolean',
            BankAccount\Entity::ACCOUNT_NUMBER => 'sometimes|alpha_num|between:5,22',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ID,
            Entity::NAME,
            Entity::EMAIL,
            Entity::CONTACT,
            EsRepository::QUERY,
            EsRepository::SEARCH_HITS,
            BankAccount\Entity::ACCOUNT_NUMBER,
        ],
    ];

    const ES_FIELDS = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
        EsRepository::QUERY,
        EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
    ];
}
