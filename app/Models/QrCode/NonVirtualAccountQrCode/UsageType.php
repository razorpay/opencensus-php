<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Exception\BadRequestValidationFailureException;

class UsageType
{
    const SINGLE_USE   = 'single_use';
    const MULTIPLE_USE = 'multiple_use';

    public static function isUsageTypeValid(string $usageType): bool
    {
        $key = __CLASS__ . '::' . strtoupper($usageType);

        return ((defined($key) === true) and (constant($key) === $usageType));
    }

    public static function checkUsageType(string $usageType)
    {
        if (self::isUsageTypeValid($usageType) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid usage type: ' . $usageType);
        }
    }
}
