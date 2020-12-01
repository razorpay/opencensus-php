<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID           => 'sometimes|string',
            Entity::MERCHANT_ID  => 'sometimes|string',
            Entity::TYPE         => 'sometimes|string',
            Entity::TICKET_ID    => 'sometimes',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::ID,
            Entity::MERCHANT_ID,
            Entity::TYPE,
            Entity::TICKET_ID,
        ],
    ];
}
