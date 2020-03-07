<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Source
 *
 * Contains list of valid file store source
 *
 * @package RZP\Models\Merchant\Document
 */
class Source
{
    const API = 'API';
    const UFH = 'UFH';

    public static function isSourceValid(string $source): bool
    {
        $key = __CLASS__ . '::' . strtoupper($source);

        return ((defined($key) === true) and (constant($key) === $source));
    }

    public static function validateSource(string $source)
    {
        if (self::isSourceValid($source) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid source: ' . $source);
        }
    }
}
