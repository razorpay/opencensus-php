<?php

namespace RZP\Models\Feature;

use RZP\Base;
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

	public static function validateFeatures(array $input)
    {
        $name = $input[Entity::NAME];

        if (in_array($name, Constants::$allFeatures) === false)
        {
        	throw new Exception\BadRequestValidationFailureException(
        		"Invalid beta feature: $name",
        		Entity::NAME);
        }
   }
}
