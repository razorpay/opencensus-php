<?php

namespace RZP\Models\PaymentLink\Template;

use RZP\Exception\BadRequestValidationFailureException;

class OptionCmp
{
    const DATE      = 'date';
    const SELECT    = 'select';
    const TEXTAREA  = 'textarea';

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
            throw new BadRequestValidationFailureException("Not a valid UDF options type: " . $value);
        }
    }
}
