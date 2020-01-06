<?php

namespace RZP\Models\PayoutLink;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS         => [
            Entity::ID => 'sometimes|public_id|size:19',
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::ID                   => 'sometimes|public_id|size:19',
            Entity::CONTACT_ID           => 'sometimes|public_id|size:19',
            Entity::CONTACT_NAME         => 'sometimes|string|max:50',
            Entity::CONTACT_PHONE_NUMBER => 'sometimes|contact_syntax',
            Entity::FUND_ACCOUNT_ID      => 'sometimes|string|public_id',
            Entity::PURPOSE              => 'sometimes|string|max:30|alpha_dash_space',
            Entity::STATUS               => 'sometimes|string|custom',
            Entity::RECEIPT              => 'sometimes|string|max:40',
            Entity::SHORT_URL            => 'sometimes|string',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::CONTACT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE_NUMBER,
            Entity::FUND_ACCOUNT_ID,
            Entity::PURPOSE,
            Entity::STATUS,
            Entity::RECEIPT,
            Entity::SHORT_URL
        ]
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];

    const ES_FIELDS = [
    ];

    const COMMON_FIELDS = [
        Entity::ID,
    ];

    protected function validateStatus(string $attribute, string $value)
    {
        Status::validate($value);
    }
}
