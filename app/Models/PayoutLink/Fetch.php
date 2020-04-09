<?php

namespace RZP\Models\PayoutLink;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS         => [
            Entity::ID              => 'sometimes|unsigned_id',
            Entity::CONTACT_ID      => 'sometimes|public_id|size:19',
            Entity::FUND_ACCOUNT_ID => 'sometimes|public_id',
            Entity::PURPOSE         => 'sometimes|string|max:30|alpha_dash_space',
            Entity::STATUS          => 'sometimes|string|custom',
            Entity::RECEIPT         => 'sometimes|string|max:40',
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH => 'filled|string|in:payouts,payouts.fund_account,user',
        ]
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            Entity::ID,
            Entity::CONTACT_ID,
            Entity::FUND_ACCOUNT_ID,
            Entity::PURPOSE,
            Entity::STATUS,
            Entity::RECEIPT,
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH,
        ]
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];

    protected function validateStatus(string $attribute, string $value)
    {
        Status::validate($value);
    }
}
