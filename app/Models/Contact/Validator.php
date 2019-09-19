<?php

namespace RZP\Models\Contact;

use RZP\Base;
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
     * - Can have anything from a-z/A-Z/0-9/'/&/–/./_/(/)/\/space in between
     */
    const NAME_REGEX = '/(^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+[a-zA-Z0-9.)]$)/';

    const MAX_TYPES_ALLOWED = 100;

    /**
     * Rate limit on items sending for bulk contact create.
     */
    const MAX_BULK_CONTACTS_LIMIT = 15;

    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50|nullable|custom',
        Entity::CONTACT                 => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL                   => 'sometimes|nullable|email',
        Entity::TYPE                    => 'sometimes|nullable|max:40|alpha_dash_space',
        Entity::REFERENCE_ID            => 'sometimes|string|max:40',
        Entity::NOTES                   => 'sometimes|notes',
        Entity::IDEMPOTENCY_KEY         => 'sometimes|nullable|string',
    ];

    protected static $editRules = [
        Entity::NAME         => 'sometimes|string|max:50|custom',
        Entity::CONTACT      => 'sometimes|nullable|contact_syntax',
        Entity::EMAIL        => 'sometimes|nullable|email',
        Entity::TYPE         => 'sometimes|nullable|max:40|alpha_dash_space',
        Entity::REFERENCE_ID => 'sometimes|nullable|string|max:40',
        Entity::ACTIVE       => 'sometimes|boolean',
        Entity::NOTES        => 'sometimes|notes',
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

    /**
     * @param array $input
     * Rate limit on number of contact creation in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkContactCount(array $input)
    {
        if (count($input) > self::MAX_BULK_CONTACTS_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of Bulk Contact is ' . self::MAX_BULK_CONTACTS_LIMIT,
                null,
                null
            );
        }
    }
}
