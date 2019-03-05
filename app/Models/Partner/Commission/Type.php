<?php

namespace RZP\Models\Partner\Commission;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    // types of commission
    const IMPLICIT = 'implicit';
    const EXPLICIT = 'explicit';

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    /**
     * @param $type
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Invalid type: ' . $type);
        }
    }
}
