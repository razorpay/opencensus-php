<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
	protected static $createRules = array(
		Entity::TOGGLEABLE_ID => 'required',
		Entity::TOGGLEABLE_TYPE => 'required',
		Entity::NAME => 'required'
	);

	protected static $createValidators = [
		'features'
	];

	public static function validateFeatures($input)
    {
        $name = $input[Entity::NAME];

        if (in_array($name, Core::$allFeatures) === false)
        {
        	throw new Exception\BadRequestValidationFailureException(
        		"Invalid beta feature: $name",
        		Entity::NAME);
        }
   }
}