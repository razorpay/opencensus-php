<?php

namespace RZP\Gateway\Hitachi\ErrorCodes;

class ErrorFields
{
    const P_RESP_CODE = 'pRespCode';

    const BASE_CODE = 'pRespCodeBase';

    public static $errorCodeMap = [
        self::P_RESP_CODE  => 'authRespCodeMap',
        self::BASE_CODE => 'errorCodeMap',
    ];

    public static $errorDescriptionMap = [
        self::P_RESP_CODE => 'authRespDescriptionMap',
        self::BASE_CODE => 'errorDescriptionMap'
    ];

    public static function getErrorCodeFields()
    {
        // Here AUTH_RESP_CODE is the only error code.
        return [
            self::P_RESP_CODE,
            self::BASE_CODE,
        ];
    }
}
