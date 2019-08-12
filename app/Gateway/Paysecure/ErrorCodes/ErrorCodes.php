<?php

namespace RZP\Gateway\Paysecure\ErrorCodes;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Gateway\Paysecure\Fields;

class ErrorCodes extends Base\ErrorCodes\Cards\ErrorCodes
{
    // Check BIN responses
    const EC_01  = '01';
    const EC_02  = '02';
    const EC_400 = '400';
    const EC_401 = '401';
    const EC_402 = '402';
    const EC_404 = '404';
    const EC_406 = '406';
    const EC_407 = '407';
    const EC_408 = '408';
    const EC_410 = '410';
    const EC_412 = '412';

    // Callback responses
    const ACCU000 = 'ACCU000';
    const ACCU100 = 'ACCU100';
    const ACCU200 = 'ACCU200';
    const ACCU400 = 'ACCU400';
    const ACCU600 = 'ACCU600';
    const ACCU700 = 'ACCU700';
    const ACCU800 = 'ACCU800';
    const ACCU999 = 'ACCU999';

    // Authorize responses
    const EC_110 = '110';
    const EC_120 = '120';
    const EC_399 = '399';

    const EC_ED = 'ED';
    const EC_CA = 'CA';
    const EC_CI = 'CI';
    const EC_M6 = 'M6';

    const FAILURE    = 'failure';

    public static $errorCodeMappings = [
        // Check BIN error code mappings
        self::EC_01   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_02   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_400  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_401  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_402  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_404  => ErrorCode::GATEWAY_ERROR_SQL_ERROR,
        self::EC_406  => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        self::EC_407  => ErrorCode::BAD_REQUEST_UNAUTHORIZED,
        self::EC_408  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_410  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN,
        self::EC_412  => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,

        // Callback error code mappings
        self::ACCU100 => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        self::ACCU200 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::ACCU400 => ErrorCode::GATEWAY_ERROR_USER_INACTIVE,
        self::ACCU600 => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::ACCU700 => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        self::ACCU800 => ErrorCode::GATEWAY_ERROR_GENERIC_ERROR,

        self::EC_110  => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::EC_120  => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::EC_399  => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
        self::EC_ED   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_CA   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_CI   => ErrorCode::GATEWAY_ERROR_REMITTER_COMPLIANCE_VIOLATION,
        self::EC_M6   => ErrorCode::GATEWAY_ERROR_COMPLIANCE_ERROR,
    ];

    public static $fallBackMapping = [
        self::FAILURE => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
    ];

    // changes dummy field:error_code to actual field:errorcode
    public static function getErrorFieldName($fieldName)
    {
        if ($fieldName === ErrorFields::DUMMY_ERROR_CODE_FIELD)
        {
            return Fields::ERROR_CODE;
        }

        return $fieldName;
    }
}
