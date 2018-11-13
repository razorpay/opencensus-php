<?php

namespace RZP\Gateway\Paysecure;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    // Check BIN responses
    const ERROR_CODE_01 = '01';
    const ERROR_CODE_02 = '02';
    const ERROR_CODE_400 = '400';
    const ERROR_CODE_401 = '401';
    const ERROR_CODE_402 = '402';
    const ERROR_CODE_406 = '406';
    const ERROR_CODE_407 = '407';
    const ERROR_CODE_408 = '408';
    const ERROR_CODE_410 = '410';

    // Callback responses
    const ERROR_ACCU000 = 'ACCU000';
    const ERROR_ACCU200 = 'ACCU200';
    const ERROR_ACCU400 = 'ACCU400';
    const ERROR_ACCU600 = 'ACCU600';
    const ERROR_ACCU700 = 'ACCU700';
    const ERROR_ACCU800 = 'ACCU800';
    const ERROR_ACCU999 = 'ACCU999';

    // Authorize responses
    const ERROR_CODE_13 = '13';
    const ERROR_CODE_41 = '41';
    const ERROR_CODE_42 = '42';
    const ERROR_CODE_43 = '43';
    const ERROR_CODE_51 = '51';
    const ERROR_CODE_54 = '54';
    const ERROR_CODE_55 = '55';
    const ERROR_CODE_57 = '57';
    const ERROR_CODE_58 = '58';
    const ERROR_CODE_59 = '59';
    const ERROR_CODE_60 = '60';
    const ERROR_CODE_61 = '61';
    const ERROR_CODE_62 = '62';
    const ERROR_CODE_65 = '65';
    const ERROR_CODE_91 = '91';
    const ERROR_CODE_92 = '92';
    const ERROR_CODE_96 = '96';
    const ERROR_CODE_110 = '110';
    const ERROR_CODE_120 = '120';
    const ERROR_CODE_399 = '399';

    protected $descriptionMappings = [
        // Check BIN error codes
        self::ERROR_CODE_01 => 'Missing Parameter',
        self::ERROR_CODE_02 => 'Invalid Command',
        self::ERROR_CODE_400 => 'General Error',
        self::ERROR_CODE_401 => 'Command is Null or Empty',
        self::ERROR_CODE_402 => 'XML is Null or Empty',
        self::ERROR_CODE_406 => 'Not Authenticated',
        self::ERROR_CODE_407 => 'Not Authorized',
        self::ERROR_CODE_408 => 'XML Data Error',
        self::ERROR_CODE_410 => 'Invalid BIN',
        // Callback error codes
        self::ERROR_ACCU200 => 'User pressed cancel button',
        self::ERROR_ACCU400 => 'User was inactive',
        self::ERROR_ACCU600 => 'Invalida data posted to Paysecure',
        self::ERROR_ACCU700 => 'Card issuer error',
        self::ERROR_ACCU800 => 'General error',
        self::ERROR_ACCU999 => 'Modal popup was opened successfully',
        // Authorize error codes
        self::ERROR_CODE_13 => 'Amount Error',
        self::ERROR_CODE_41 => 'DECLINED (lost card)',
        self::ERROR_CODE_42 => 'DECLINED (no account)',
        self::ERROR_CODE_43 => 'DECLINED (stolen)',
        self::ERROR_CODE_51 => 'NON SUFFICIENT FUNDS',
        self::ERROR_CODE_54 => 'EXPIRED CARD',
        self::ERROR_CODE_55 => 'WRONG PIN',
        self::ERROR_CODE_57 => 'DECLINED (cardholder not allowed)',
        self::ERROR_CODE_58 => 'DECLINED (terminal not allowed)',
        self::ERROR_CODE_59 => 'DECLINED (fraud)',
        self::ERROR_CODE_60 => 'DECLINED (contact acquirer)',
        self::ERROR_CODE_61 => 'DECLINED (exceeds with)',
        self::ERROR_CODE_62 => 'DECLINED (restricted card)',
        self::ERROR_CODE_65 => 'DECLINED (exceeds frequency)',
        self::ERROR_CODE_91 => 'ERROR',
        self::ERROR_CODE_92 => 'NO ROUTING AVAILABLE',
        self::ERROR_CODE_96 => 'SYSTEM ERROR',
        self::ERROR_CODE_110 => 'NO ACCT',
        self::ERROR_CODE_120 => 'ACCT CLOSED',
        self::ERROR_CODE_399 => 'SYSTEM UNAVAILABLE',
    ];

    // todo: Add correct mappings for these
    protected static $errorCodeMappings = [
        // Check BIN error code mappings
        self::ERROR_CODE_01 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_02 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_400 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_401 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_402 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_406 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_407 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_408 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_CODE_410 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        // Callback error code mappings
        self::ERROR_ACCU200 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_ACCU400 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_ACCU600 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_ACCU700 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_ACCU800 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_ACCU999 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        // Authorize error code mappings
        self::ERROR_CODE_13 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_41 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_42 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_43 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_51 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_54 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_55 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_57 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_58 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_59 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_60 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_61 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_62 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_65 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_91 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_92 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_96 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_110 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_120 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ERROR_CODE_399 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
    ];

    public static function getErrorCodeMapped($errorCode)
    {
        return self::$errorCodeMappings[$errorCode] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
