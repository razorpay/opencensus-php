<?php

namespace RZP\Models\Batch;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID          => 'sometimes|public_id|size:20',
            Entity::TYPE        => 'sometimes|string|custom',
            Entity::SUB_TYPE    => 'sometimes|string',
            Entity::GATEWAY     => 'sometimes|string|required_with:sub_type',
            Entity::TYPES       => 'sometimes|sequential_array|custom',
            Entity::MERCHANT_ID => 'sometimes|alpha_num',
            Entity::PROCESSING  => 'sometimes|in:0,1',
            Entity::STATUS      => 'sometimes|in:created,processing,processed,partially_processed,failed',
        ],
    ];

    const ACCESSES = [
        AuthType::PROXY_AUTH => [
            Entity::ID,
            Entity::TYPE,
            Entity::TYPES,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::PROCESSING,
        ],
        AuthType::ADMIN_AUTH => [
            Entity::SUB_TYPE,
            Entity::GATEWAY,
        ],
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
    }

    protected function validateTypes($attribute, $value)
    {
        Type::validateTypes($value);
    }
}
