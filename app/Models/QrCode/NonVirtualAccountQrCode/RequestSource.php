<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Exception\BadRequestValidationFailureException;

class RequestSource
{
    const DASHBOARD = 'dashboard';
    const CHECKOUT  = 'checkout';
    const API       = 'api';
    const FALLBACK  = 'fallback';

    public static function isRequestSourceValid(string $requestSource): bool
    {
        $key = __CLASS__ . '::' . strtoupper($requestSource);

        return ((defined($key) === true) and (constant($key) === $requestSource));
    }

    public static function checkRequestSource(string $requestSource)
    {
        if (self::isRequestSourceValid($requestSource) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid source: ' . $requestSource);
        }
    }
}
