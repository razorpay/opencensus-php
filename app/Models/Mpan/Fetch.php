<?php


namespace RZP\Models\Mpan;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;


class Fetch extends  BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::NETWORK       => 'required|in:Visa,RuPay,MasterCard',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::NETWORK,
        ],
    ];
}
