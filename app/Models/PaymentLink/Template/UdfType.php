<?php

namespace RZP\Models\PaymentLink\Template;

use RZP\Exception\BadRequestValidationFailureException;

class UdfType
{
    const NUMBER    = 'number';
    const STRING    = 'string';

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        $key = __CLASS__ . '::' . strtoupper($value);

        return ((defined($key) === true) and (constant($key) === $value));
    }

    /**
     * @param string $value
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public static function validate(string $value)
    {
        if (self::isValid($value) === false)
        {
            throw new BadRequestValidationFailureException("Not a valid UDF schema type: " . $value);
        }
    }
}
