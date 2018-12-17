<?php

namespace RZP\Models\Contact;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Type
 *
 * @package RZP\Models\Contact
 */
final class Type
{
    const CUSTOMER = 'customer';
    const EMPLOYEE = 'employee';
    const VENDOR   = 'vendor';
    const SELF     = 'self';

    public static function getAll(): array
    {
        return [
            self::CUSTOMER,
            self::EMPLOYEE,
            self::VENDOR,
            self::SELF,
        ];
    }

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validateType(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid contact type: ' . $type);
        }
    }
}
