<?php

namespace RZP\Models\PaymentLink\Template;

use RZP\Exception\BadRequestValidationFailureException;

class Pattern
{
    const PAN           = 'pan';
    const URL           = 'url';
    const DATE          = 'date';
    const EMAIL         = 'email';
    const PHONE         = 'phone';
    const AMOUNT        = 'amount';
    const NUMBER        = 'number';
    const ALPHABETS     = 'alphabets';
    const ALPHANUMERIC  = 'alphanumeric';

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
            throw new BadRequestValidationFailureException("Not a valid UDF schema pattern: " . $value);
        }
    }
}
