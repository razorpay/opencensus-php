<?php


namespace RZP\Models\Store;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const PAYMENT      = 'payment';
    const SUBSCRIPTION = 'subscription';

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validateType(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid account type: ' . $type);
        }
    }
}
