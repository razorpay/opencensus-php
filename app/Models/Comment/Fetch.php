<?php

namespace RZP\Models\Comment;

use RZP\Error\ErrorCode;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Exception\BadRequestValidationFailureException;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ENTITY_TYPE => 'filled|string|max:100|custom',
            Entity::ENTITY_ID   => 'filled|string|min:14|max:25',
            Entity::ADMIN_ID    => 'filled|string|min:14|max:19',
            Entity::MERCHANT_ID => 'filled|string|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            Entity::ENTITY_TYPE,
            Entity::ENTITY_ID,
            Entity::ADMIN_ID,
            Entity::MERCHANT_ID,
        ],
    ];

    protected function validateEntityType($attribute, $value)
    {
        if (in_array($value, Repository::VALID_ENTITY_TYPE, true) === false)
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
