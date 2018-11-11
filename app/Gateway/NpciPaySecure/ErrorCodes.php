<?php

namespace RZP\Gateway\NpciPaySecure;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    // Check BIN request
    const ERROR_CODE_01 = '01';
    const ERROR_CODE_02 = '02';
    const ERROR_CODE_400 = '400';
    const ERROR_CODE_401 = '401';
    const ERROR_CODE_402 = '402';
    const ERROR_CODE_406 = '406';
    const ERROR_CODE_407 = '407';
    const ERROR_CODE_408 = '408';
    const ERROR_CODE_410 = '410';

    protected $descriptionMappings = [
        self::ERROR_CODE_01 => 'Missing Parameter',
        self::ERROR_CODE_02 => 'Invalid Command',
        self::ERROR_CODE_400 => 'General Error',
        self::ERROR_CODE_401 => 'Command is Null or Empty',
        self::ERROR_CODE_402 => 'XML is Null or Empty',
        self::ERROR_CODE_406 => 'Not Authenticated',
        self::ERROR_CODE_407 => 'Not Authorized',
        self::ERROR_CODE_408 => 'XML Data Error',
        self::ERROR_CODE_410 => 'Invalid BIN',
    ];

    // todo: Add correct mappings for these
    protected static $errorCodeMappings = [
        self::ERROR_CODE_01 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_02 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_400 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_401 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_402 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_406 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_407 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_408 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_410 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public static function getErrorCodeMapped($errorCode)
    {
        return self::$errorCodeMappings[$errorCode];
    }
}
