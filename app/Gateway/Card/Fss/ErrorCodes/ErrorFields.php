<?php

namespace RZP\Gateway\Card\Fss\ErrorCodes;

class ErrorFields
{
    const ERROR_CODE = 'code';

    public static $errorCodeMap = [
        self::ERROR_CODE => 'errorCodeMap'
    ];

    public static $errorDescriptionMap = [
        self::ERROR_CODE     => 'errorDescMap',
    ];

    public static function getErrorCodeFields()
    {
        return [
            self::ERROR_CODE
        ];
    }
}
