<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class Source
{
    const SELLER_APP = 'seller_app';
    const EXTENSION  = 'extension';

    public static function isSourceValid(string $source): bool
    {
        $key = __CLASS__ . '::' . strtoupper($source);

        return ((defined($key) === true) and (constant($key) === $source));
    }

    public static function checkSource(string $source)
    {
        if (self::isSourceValid($source) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid source: ' . $source);
        }
    }
}
