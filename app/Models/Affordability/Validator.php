<?php

namespace RZP\Models\Affordability;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Key\Entity as KeyEntity;

class Validator extends Base\Validator
{
    protected static $fetchRules = [
        'key'        => 'required|size:23|custom',
        'components' => 'required|array|custom'
    ];

    protected static $validComponents = [
        'cardless_emi',
        'emi',
        'offers',
        'options',
        'paylater',
    ];

    /**
     * Validates each value of input components against valid components.
     *
     * @param string $attribute
     * @param array  $components
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateComponents(string $attribute, array $components): void
    {
        if (array_intersect($components, self::$validComponents) !== $components)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_INVALID_AFFORDABILITY_COMPONENT,
                $attribute,
                $components
            );
        }
    }

    /**
     * Validates if the key is a valid public key.
     *
     * @param string $attribute
     * @param string $keyId
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateKey(string $attribute, string $keyId): void
    {
        try {
            app('repo')->key->findOrFailByPublicIdWithParams(
                $keyId,
                [
                    KeyEntity::EXPIRED_AT => null,
                ]
            );
        } catch (BadRequestException $exception) {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY,
                $attribute,
                $keyId
            );
        }
    }
}
