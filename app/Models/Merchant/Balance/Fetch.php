<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TYPE                => 'sometimes|string|max:32',
            Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
            Entity::ACCOUNT_NUMBER      => 'sometimes|string',
            Entity::ACCOUNT_TYPE        => 'sometimes|array',
            Entity::ACCOUNT_TYPE . '.*' => 'required|string|in:corp_card,direct,shared',
            Entity::CACHED              => 'sometimes|string',
            Entity::ID                  => 'sometimes|string',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::ACCOUNT_TYPE   =>    'sometimes|string|custom',
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::TYPE           =>    'sometimes|string|max:32',
        ],
    ];
    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
            Entity::ACCOUNT_TYPE,
            Entity::ID,
            Entity::CACHED,
        ],
        AuthType::PRIVATE_AUTH => [
            Entity::TYPE,
        ],
        AuthType::ADMIN_AUTH =>  [
            Entity::MERCHANT_ID,
            Entity::ACCOUNT_NUMBER,
            Entity::ACCOUNT_TYPE,
        ]
    ];

    public function validateAccountType(string $attribute, string $value)
    {
        AccountType::validate($value);
    }
}
