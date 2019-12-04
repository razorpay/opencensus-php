<?php

namespace RZP\Models\Payout;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Mode
{
    const RTGS  = 'RTGS';
    const IMPS  = 'IMPS';
    const NEFT  = 'NEFT';
    const IFT   = 'IFT';
    const UPI   = 'UPI';

    protected static $allSupportedModes = [
        self::RTGS,
        self::IMPS,
        self::NEFT,
        self::IFT,
        self::UPI,
    ];

    public static function validateMode(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_MODE,
                null,
                [
                    'mode' => $mode,
                ]);
        }
    }

    protected static function isValid(string $mode): bool
    {
        return (in_array($mode, self::$allSupportedModes, true) === true);
    }
}
