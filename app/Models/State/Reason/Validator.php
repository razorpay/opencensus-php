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
        $validReasonTypes = [
            Entity::REJECTION,
        ];

        if (in_array($reasonType, $validReasonTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REASON_TYPE_MESSAGE);
        }
    }
}
