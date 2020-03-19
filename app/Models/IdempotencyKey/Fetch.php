<?php

namespace RZP\Models\IdempotencyKey;

use RZP\Constants;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                  => 'sometimes|min:14|max:19',
            Entity::SOURCE_TYPE         => 'sometimes|custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::SOURCE_TYPE,
        ],
    ];

    protected function validateSourceType(string $attribute, string $value)
    {
        Constants\Entity::validateEntityOrFail($value);
    }
}
