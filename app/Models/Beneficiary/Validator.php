<?php

namespace RZP\Models\Beneficiary;

use RZP\Base;
use RZP\Exception;

/**
 * Class Validator
 *
 * @package RZP\Models\Beneficiary
 */
class Validator extends Base\Validator
{
    /**
     * Regular expression for valid names:
     * - Must start with a-z/A-Z/0-9
     * - Must end with a-z/A-Z/0-9/./)
     * - Can have anything from a-z/A-Z/0-9/'/-/–/./_/(/)/space in between
     */
    const NAME_REGEX = '/(^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–]+[a-zA-Z0-9.)]$)/';

    protected static $createRules = [
        Entity::NAME    => 'required|string|max:50|nullable|custom',
        Entity::CONTACT => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL   => 'sometimes|nullable|email',
        Entity::NOTES   => 'sometimes|notes',
    ];

    protected static $editRules = [
        Entity::NAME    => 'sometimes|string|max:50|custom',
        Entity::CONTACT => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL   => 'sometimes|nullable|email',
        Entity::ACTIVE  => 'sometimes|boolean',
        Entity::NOTES   => 'sometimes|notes',
    ];

    protected static $createValidators = [
        //
    ];

    protected function validateName($attribute, $value)
    {
        $match = preg_match(self::NAME_REGEX, trim($value));

        if ($match !== 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The name field is invalid.',
                Entity::NAME);
        }
    }
}
