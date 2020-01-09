<?php

namespace RZP\Models\PayoutLink;

use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS         => [
            Entity::ID => 'sometimes|public_id|size:19',
            Entity::CONTACT_ID           => 'sometimes|public_id|size:19',
            Entity::FUND_ACCOUNT_ID      => 'sometimes|string|public_id',
            Entity::PURPOSE              => 'sometimes|string|max:30|alpha_dash_space',
            Entity::STATUS               => 'sometimes|string|custom',
            Entity::RECEIPT              => 'sometimes|string|max:40',
        ],
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
