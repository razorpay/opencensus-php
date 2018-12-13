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
            Entity::EMAIL => 'sometimes|email',
            Entity::NAME    => 'sometimes|string|max:50',
            Entity::CONTACT => 'sometimes|contact_syntax',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ID,
            Entity::NAME,
            Entity::EMAIL,
            Entity::CONTACT,
    ];

    const ES_FIELDS = [
        Entity::ID,
        Entity::NAME,
        Entity::EMAIL,
        Entity::CONTACT,
    ];

    const COMMON_FIELDS = [
        //
    ];
}
