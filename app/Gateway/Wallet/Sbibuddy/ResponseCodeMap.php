<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base;

class ResponseCodeMap extends Base\ResponseCodeMap
{
    const SUCCESS_CODE              = '1';
    const GENERAL_ERROR             = '2';
    const PIN_AUTH_FAIL             = '3';
    const ACCOUNT_LOCKED            = '5';
    const INSUFFICIENT_BALANCE      = '6';
    const GENERAL_TRANSACTION_ERROR = '18';
    const DUPLICATE_TRANSACTION     = '19';
    const PENDING_TRANSACTION       = '21';
    const WRONG_OTP                 = '28';
    const VELOCITY_EXCEEDED         = '30';
    const GENERAL_LOGIN_FAILURE     = '32';
    const WALLET_DISABLED           = '33';
    const TEMPORARY_CREDENTIAL      = '35';
    const CANCELLED_TRANSACTION     = '38';
    const TRANSACTION_REFUNDED      = '39';
    const SESSION_EXPIRED           = '40';
    const OPERATION_NOT_ALLOWED     = '41';

    public static $successCodes = [
        self::SUCCESS_CODE,
        self::TRANSACTION_REFUNDED
    ];

    public static $codes = [
        self::GENERAL_ERROR             => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::PIN_AUTH_FAIL             => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        self::ACCOUNT_LOCKED            => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER,
        self::INSUFFICIENT_BALANCE      => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        self::GENERAL_TRANSACTION_ERROR => ErrorCode::BAD_REQUEST_ERROR,
        self::DUPLICATE_TRANSACTION     => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::PENDING_TRANSACTION       => ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
        self::WRONG_OTP                 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        self::VELOCITY_EXCEEDED         => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_MAX_WRONG_ATTEMPT_LIMIT_CROSSED,
        self::GENERAL_LOGIN_FAILURE     => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        self::WALLET_DISABLED           => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INACTIVE,
        self::TEMPORARY_CREDENTIAL      => ErrorCode::GATEWAY_ERROR_FALSE_AUTHORIZE,
        self::CANCELLED_TRANSACTION     => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        self::SESSION_EXPIRED           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED,
        self::OPERATION_NOT_ALLOWED     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
    ];
}
