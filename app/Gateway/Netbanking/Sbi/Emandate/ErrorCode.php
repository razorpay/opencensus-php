<?php

namespace RZP\Gateway\Netbanking\Sbi\Emandate;

use RZP\Error\ErrorCode as APIErrorCode;

class ErrorCode
{
    protected static $registerErrorMap = [
        'Account has hold. No free balance available for this transaction.' => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'Clear Balance Funds not available.'                                => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'Sorry unable to process your request'                              => APIErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED
    ];

    protected static $debitErrorMap = [
        'CLEARED BAL/FUNDS/DP NOT AV'                                     => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'CLEARED BAL/FUNDS/DP NOT AVAILABLE.CARE! ACCT WILL BE OVERDRAWN' => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'DP NOT AVAILABLE. ACCT CANNOT BE OVERDRAWN'                      => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'ACCT HAS HOLD. INSUFFICIENT FREE BAL FOR TXN'                    => APIErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        'INCORRECT DEBIT ACCOUNT NUMBER; '                                => APIErrorCode::BAD_REQUEST_ACCOUNT_NUMBER_MISMATCH,

    ];

    public static function getDebitErrorCode(string $error): string
    {
        return (self::$debitErrorMap[$error] ?? APIErrorCode::BAD_REQUEST_PAYMENT_FAILED);
    }

    public static function getRegisterErrorCode(string $error): string
    {
        return (self::$registerErrorMap[$error] ?? APIErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED);
    }
}
