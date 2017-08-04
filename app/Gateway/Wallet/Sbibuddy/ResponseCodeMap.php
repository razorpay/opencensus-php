<?php

namespace RZP\Gateway\Wallet\Sbibuddy;

use RZP\Error\ErrorCode;

class ResponseCodeMap
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

    public static $errorDescriptions = [
        self::GENERAL_ERROR             => 'General error. Check error description.',
        self::PIN_AUTH_FAIL             => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        self::ACCOUNT_LOCKED            => 'Account locked.',
        self::INSUFFICIENT_BALANCE      => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        self::GENERAL_TRANSACTION_ERROR => 'General transaction error. Check error description.',
        self::DUPLICATE_TRANSACTION     => 'Duplicate transaction.',
        self::PENDING_TRANSACTION       => 'Pending transaction.',
        self::WRONG_OTP                 => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        self::VELOCITY_EXCEEDED         => 'Velocity exceeded.',
        self::GENERAL_LOGIN_FAILURE     => 'General login failure.',
        self::WALLET_DISABLED           => 'Wallet disabled.',
        self::TEMPORARY_CREDENTIAL      => 'Temporary credential not accepted.',
        self::CANCELLED_TRANSACTION     => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        self::SESSION_EXPIRED           => 'Session expired',
        self::OPERATION_NOT_ALLOWED     => 'Operation not allowed',
    ];
}
