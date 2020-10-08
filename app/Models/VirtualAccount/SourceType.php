<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;

use RZP\Exception\BadRequestValidationFailureException;

class SourceType
{
    const PAYMENT_LINKS_V2 = 'payment_links_v2';

    public static function isTypeValid(string $sourceType): bool
    {
        $key = __CLASS__ . '::' . strtoupper($sourceType);

        return ((defined($key) === true) and (constant($key) === $sourceType));
    }

    public static function checkSourceType(string $sourceType)
    {
        if (self::isTypeValid($sourceType) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid source type: ' . $sourceType);
        }
    }
}
