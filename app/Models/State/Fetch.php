<?php

namespace RZP\Models\State;

use RZP\Base\Fetch as BaseFetch;
use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const VALID_ENTITY_TYPE = [
        E::DISPUTE,
        E::WORKFLOW_ACTION,
    ];

    const RULES = [
        self::DEFAULTS => [
            Entity::ADMIN_ID    => 'filled|string|size:14',
            Entity::MERCHANT_ID => 'filled|string|size:14',
            Entity::ENTITY_ID   => 'filled|string|size:14',
            Entity::ENTITY_TYPE => 'filled|string|max:100|custom',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::ADMIN_ID,
            Entity::MERCHANT_ID,
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
        ],
    ];

    protected function validateEntityType($attribute, $value)
    {
        if (in_array($value, self::VALID_ENTITY_TYPE, true) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MORPHED_ENTITY_INVALID,
                Entity::ENTITY_TYPE,
                [
                    Entity::ENTITY_TYPE => $value
                ]);
        }
    }
}
