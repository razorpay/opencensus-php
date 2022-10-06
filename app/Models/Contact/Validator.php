<?php

namespace RZP\Models\Contact;

use RZP\Base;
use Lib\Gstin;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\Contact
 */
class Validator extends Base\Validator
{
    /**
     * Regular expression for valid names:
     * - Must start with a-z/A-Z/0-9
     * - Must end with a-z/A-Z/0-9/./)
     * - Can have anything from a-z/A-Z/0-9/'/’/,/-/&/–/./_/(/)/\/space in between
     */
    const NAME_REGEX = '/(^[a-zA-Z0-9][\w\-&\'’,.()\s–\/]+[a-zA-Z0-9.\/)]$)/';

    const MAX_TYPES_ALLOWED = 100;

    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50|nullable|custom',
        Entity::CONTACT                 => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL                   => 'sometimes|nullable|email',
        Entity::TYPE                    => 'sometimes|nullable|max:40|alpha_dash_space',
        Entity::REFERENCE_ID            => 'sometimes|string|max:40',
        Entity::NOTES                   => 'sometimes|notes',
        Entity::IDEMPOTENCY_KEY         => 'sometimes|nullable|string',
        Entity::PAYMENT_TERMS           => 'sometimes|numeric|integer|min:0',
        Entity::TDS_CATEGORY            => 'sometimes|numeric|integer|min:0',
        Entity::PAN                     => 'sometimes|string|min:0|max:40',
        Entity::EXPENSE_ID              => 'sometimes|string|max:40',
        Entity::GST_IN                  => 'sometimes|string|max:40|custom',
        Entity::BATCH_ID                => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::NAME          => 'sometimes|string|max:50|custom',
        Entity::CONTACT       => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL         => 'sometimes|nullable|email',
        Entity::TYPE          => 'sometimes|nullable|max:40|alpha_dash_space',
        Entity::REFERENCE_ID  => 'sometimes|nullable|string|max:40',
        Entity::ACTIVE        => 'sometimes|boolean',
        Entity::NOTES         => 'sometimes|notes',
        Entity::PAYMENT_TERMS => 'sometimes|numeric|integer|min:0',
        Entity::TDS_CATEGORY  => 'sometimes|numeric|integer|min:0',
        Entity::EXPENSE_ID    => 'sometimes|string|max:40',
        Entity::GST_IN        => 'sometimes|string|max:40|custom',
        Entity::PAN           => 'sometimes|string|min:0|max:40',
    ];

    protected static $createTypeRules = [
        Entity::TYPE => 'required|filled|max:40|alpha_dash_space',
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

    protected function validateGstin($attribute, $value)
    {
        $isValidGstin = Gstin::isValid($value);

        if ($isValidGstin === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The gstin field is invalid.',
                Entity::GST_IN);
        }
    }
}
