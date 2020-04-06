<?php

namespace RZP\Models\FeeRecovery;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                  => 'sometimes|filled|unsigned_id',
            Entity::ENTITY_ID           => 'sometimes|filled|unsigned_id',
            Entity::ENTITY_TYPE         => 'sometimes|filled',
            Entity::RECOVERY_PAYOUT_ID  => 'sometimes|filled|unsigned_id',
            Entity::TYPE                => 'sometimes|filled',
            Entity::STATUS              => 'sometimes|filled',
            Entity::ATTEMPT_NUMBER      => 'sometimes|filled|integer',
            Entity::REFERENCE_NUMBER    => 'sometimes|filled',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::ENTITY_ID,
            Entity::RECOVERY_PAYOUT_ID,
            Entity::TYPE,
            Entity::STATUS,
            Entity::ATTEMPT_NUMBER,
            Entity::REFERENCE_NUMBER,
            self::EXPAND_EACH,
        ]
    ];

    protected function validateType(string $attribute, string $value)
    {
        Type::validateType();
    }

    protected function validateStatus(string $attribute, string $value)
    {
        Status::validateStatus();
    }
}
