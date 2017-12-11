<?php

namespace RZP\Models\State\Reason;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    const INVALID_REASON_TYPE_MESSAGE = 'Invalid reason type';

    protected static $createRules = [
        Entity::REASON_TYPE     => 'required|string|max:255|custom',
        Entity::REASON_CATEGORY => 'required|string|max:255',
        Entity::REASON_CODE     => 'required|string|max:255',
    ];

    public function validateReasonType(string $attribute, string $reasonType)
    {
        if (in_array($reasonType, ReasonType::ALLOWED_REASON_TYPES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_TYPE_MESSAGE);
        }
    }
}
