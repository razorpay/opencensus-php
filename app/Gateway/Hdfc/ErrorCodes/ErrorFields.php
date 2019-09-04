<?php

namespace RZP\Gateway\Hdfc\ErrorCodes;

class ErrorFields
{
    const AUTH_RESP_CODE   = 'authRespCode';
    const DUMMY_ERROR_FIELD   = 'dummyAuthRespCode';
    const ERROR_CODE = 'code';

    public static $errorCodeMap = [
        self::DUMMY_ERROR_FIELD  => 'errorCodeMap',
        self::AUTH_RESP_CODE     => 'authRespCodeErrorMap',
        self::ERROR_CODE         => 'hdfcErrorCodeMap',
    ];

    public static $errorDescriptionMap = [
        self::DUMMY_ERROR_FIELD  => 'errorDescriptionMap',
        self::AUTH_RESP_CODE     => 'authRespCodeErrorMessages',
        self::ERROR_CODE         => 'errorCodeDescMap'
    ];

    public static function getErrorCodeFields()
    {
        return [
            self::DUMMY_ERROR_FIELD,
            self::AUTH_RESP_CODE,
            self::ERROR_CODE,
        ];
    }
}
