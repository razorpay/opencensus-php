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
        'key'        => 'sometimes',
        'components' => 'required|array|custom',
        'merchantId' => 'sometimes|string',
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
}
