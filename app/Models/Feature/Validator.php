<?php

namespace RZP\Models\Feature;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ENTITY_ID   => 'required|string|max:255',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::NAME        => 'required|string|max:255|custom'
    );

    public static function validateName($attribute, $value)
    {
        if (in_array($value, Constants::$allFeatures) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid feature: $name",
                $attribute);
        }
   }
}
