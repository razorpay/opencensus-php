<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TYPE         => 'required|string',
            Entity::TICKET_ID    => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
            Entity::TICKET_ID,
        ],
    ];
}
