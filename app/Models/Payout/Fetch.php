<?php

namespace RZP\Models\Payout;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Contact;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID     => 'sometimes|alpha_num',
            Entity::CUSTOMER_ID     => 'sometimes|string|max:19',
            Entity::DESTINATION     => 'sometimes|string|max:20',
            Entity::METHOD          => 'sometimes|string',
            self::EXPAND_EACH       => 'filled|string|in:user',
            Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
            Entity::UTR             => 'sometimes|string',
            Entity::CONTACT_NAME    => 'sometimes|string',
            Entity::CONTACT_PHONE   => 'sometimes|contact_syntax',
            Entity::CONTACT_ID      => 'sometimes|alpha_num',
            Entity::CONTACT_EMAIL   => 'sometimes|email',
            Entity::FUND_ACCOUNT_ID => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH     => [
            self::EXPAND_EACH,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::FUND_ACCOUNT_ID,
            Entity::DESTINATION,
            Entity::METHOD,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::TRANSACTION_ID,
            Entity::UTR,
            Entity::CONTACT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE,
            Entity::CONTACT_EMAIL,
            Entity::FUND_ACCOUNT_ID,
        ],
    ];
}
