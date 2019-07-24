<?php

namespace RZP\Gateway\Paysecure;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    // Check BIN responses
    const EC_01  = '01';
    const EC_02  = '02';
    const EC_400 = '400';
    const EC_401 = '401';
    const EC_402 = '402';
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
    const EC_05  = '05';
    const EC_12  = '12';
    const EC_13  = '13';
    const EC_39  = '39';
    const EC_41  = '41';
    const EC_42  = '42';
    const EC_43  = '43';
    const EC_51  = '51';
    const EC_54  = '54';
    const EC_55  = '55';
    const EC_57  = '57';
    const EC_58  = '58';
    const EC_59  = '59';
    const EC_60  = '60';
    const EC_61  = '61';
    const EC_62  = '62';
    const EC_65  = '65';
    const EC_91  = '91';
    const EC_92  = '92';
    const EC_96  = '96';
    const EC_110 = '110';
    const EC_120 = '120';
    const EC_399 = '399';

    const EC_ED = 'ED';
    const EC_CA = 'CA';

    protected static $descriptionMappings = [
        // Check BIN error codes
        self::EC_01   => 'Missing Parameter',
        self::EC_02   => 'Invalid Command',
        self::EC_400  => 'General Error',
        self::EC_401  => 'Command is Null or Empty',
        self::EC_402  => 'XML is Null or Empty',
        self::EC_406  => 'Not Authenticated',
        self::EC_407  => 'Not Authorized',
        self::EC_408  => 'XML Data Error',
        self::EC_410  => 'Invalid BIN',
        self::EC_412  => 'Issuer Authentication Failure',

        // Callback error codes
        self::ACCU100 => 'Authentication Failed',
        self::ACCU200 => 'User pressed cancel button',
        self::ACCU400 => 'User was inactive',
        self::ACCU600 => 'Invalid data posted to Paysecure',
        self::ACCU700 => 'Card issuer error',
        self::ACCU800 => 'General error',
        self::ACCU999 => 'Modal popup was opened successfully',

        // Authorize error codes
        // Error code '05' is not available from the integration guide
        // We got it while testing on prod
        self::EC_05   => 'Do not honor',
        self::EC_12   => 'Invalid Transaction',
        self::EC_13   => 'Amount Error',
        self::EC_39   => 'No credit account',
        self::EC_41   => 'DECLINED (lost card)',
        self::EC_42   => 'DECLINED (no account)',
        self::EC_43   => 'DECLINED (stolen)',
        self::EC_51   => 'NON SUFFICIENT FUNDS',
        self::EC_54   => 'EXPIRED CARD',
        self::EC_55   => 'WRONG PIN',
        self::EC_57   => 'DECLINED (cardholder not allowed)',
        self::EC_58   => 'DECLINED (terminal not allowed)',
        self::EC_59   => 'DECLINED (fraud)',
        self::EC_60   => 'DECLINED (contact acquirer)',
        self::EC_61   => 'DECLINED (exceeds with)',
        self::EC_62   => 'DECLINED (restricted card)',
        self::EC_65   => 'DECLINED (exceeds frequency)',
        self::EC_91   => 'Issuer or switch is inoperative',
        self::EC_92   => 'NO ROUTING AVAILABLE',
        self::EC_96   => 'SYSTEM ERROR',
        self::EC_110  => 'NO ACCT',
        self::EC_120  => 'ACCT CLOSED',
        self::EC_399  => 'SYSTEM UNAVAILABLE',
        self::EC_ED   => 'E-commerce decline',
        self::EC_CA   => 'Compliance error code for acquirer',
    ];

    // todo: Add correct mappings for these
    protected static $errorCodeMappings = [
        // Check BIN error code mappings
        self::EC_01   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_02   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_400  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_401  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_402  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_406  => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        self::EC_407  => ErrorCode::BAD_REQUEST_UNAUTHORIZED,
        self::EC_408  => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::EC_410  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN,
        self::EC_412  => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,

        // Callback error code mappings
        self::ACCU100 => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
        self::ACCU200 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::ACCU400 => ErrorCode::GATEWAY_ERROR_USER_INACTIVE,
        self::ACCU600 => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
        self::ACCU700 => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        self::ACCU800 => ErrorCode::GATEWAY_ERROR_GENERIC_ERROR,

        // Authorize error code mappings
        self::EC_05   => ErrorCode::GATEWAY_ERROR_DO_NOT_HONOUR_REMITTER,
        self::EC_12   => ErrorCode::GATEWAY_ERROR_INVALID_TRANSACTION,
        self::EC_13   => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        self::EC_39   => ErrorCode::GATEWAY_ERROR_NO_CREDIT_ACCOUNT,
        self::EC_41   => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        self::EC_42   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_43   => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        self::EC_51   => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::EC_54   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        self::EC_55   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_PIN,
        self::EC_57   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_58   => ErrorCode::GATEWAY_ERROR_PAYMENT_DECLINED_TERMINAL_NOT_ALLOWED,
        self::EC_59   => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        self::EC_60   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_61   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_62   => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_BLOCKED_CARD,
        self::EC_65   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_91   => ErrorCode::GATEWAY_ERROR_ISSUER_UNAVAILABLE,
        self::EC_92   => ErrorCode::GATEWAY_ERROR_ISSUER_UNAVAILABLE,
        self::EC_96   => ErrorCode::GATEWAY_ERROR_GENERIC_ERROR,
        self::EC_110  => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::EC_120  => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::EC_399  => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
        self::EC_ED   => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        self::EC_CA   => ErrorCode::SERVER_ERROR_INVALID_ARGUMENT,
    ];

    public static function getErrorCodeMapped($errorCode)
    {
        return self::$errorCodeMappings[$errorCode] ?? ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }

    public static function getErrorDescription($errorCode)
    {
        return self::$descriptionMappings[$errorCode] ?? '';
    }
}
