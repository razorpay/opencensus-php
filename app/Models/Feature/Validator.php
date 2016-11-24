<?php

namespace RZP\Models\Feature;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::NAME        => 'required|string|max:25|custom'
    );

    protected function validateName($attribute, $value)
    {
        if (in_array($value, Constants::$allFeatures) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid feature: $value",
                $attribute);
        }
   }
}
