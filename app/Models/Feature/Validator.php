<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
	protected static $createRules = array(
		Entity::ENTITY_ID		=> 'required|string|max:255',
		Entity::ENTITY_TYPE		=> 'required|string|max:255',
		Entity::NAME 			=> 'required|string|max:255'
	);

	protected static $createValidators = [
		'features'
	];

	public static function validateFeatures($input)
    {
        $name = $input[Entity::NAME];

        if (in_array($name, FeatureName::$allFeatures) === false)
        {
        	throw new Exception\BadRequestValidationFailureException(
        		"Invalid beta feature: $name",
        		Entity::NAME);
        }
   }
}
