<?php
namespace RZP\Models\Batch;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::TYPE        => 'sometimes|string|custom',
            Entity::MERCHANT_ID => 'sometimes|alpha_num',
            Entity::STATUS      => 'sometimes|in:created,processing,processed',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::TYPE,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
        ],
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
    }
}
