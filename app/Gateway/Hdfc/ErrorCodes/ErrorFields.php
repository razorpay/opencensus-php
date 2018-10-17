<?php

namespace RZP\Gateway\Hdfc\ErrorCodes;

class ErrorFields
{
    const AUTH_RESP_CODE   = 'authRespCode';
    const ERROR_CODE = 'code';

    public static $errorCodeMap = [
        self::AUTH_RESP_CODE     => 'authRespCodeErrorMap',
        self::ERROR_CODE         => 'errorCodeMap'
    ];

    public static $errorDescriptionMap = [
        self::AUTH_RESP_CODE     => 'authRespCodeErrorMessages',
        self::ERROR_CODE         => 'errorCodeDescMap'
    ];

    public static function getErrorCodeFields()
    {
        return [
            self::AUTH_RESP_CODE,
            self::ERROR_CODE,
        ];
    }
}
