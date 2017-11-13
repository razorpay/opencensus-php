<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Error;

class ErrorCode
{
    const TRANSFER_TERMINATED_BY_USER           = 'Funds transfer terminated by user';
    const ACCOUNT_CLOSED                        = '88-Account Is Closed';
    const ACCOUNT_MISMATCH                      = 'A/c no. mismatch';
    const ACCOUNT_BLOCKED                       = 'Account Is Blocked';
    const ACCOUNT_DORMANT                       = 'Account Is Dormant';
    const ACCOUNT_NO_DEBIT_ALLOWED              = 'Account Is No Dr Allowed';
    const INSUFFICINET_FUNDS                    = 'Insufficient Funds';
    const TXN_AMT_GREATER_THAN_REGISTERED_AMT   = 'Transaction amt. is greater than the registered amt.';
    const MONTHLY_DEBIT_EXCEEDED_FOR_TXN        = 'For Monthly frequency-Monthly debit limit exceeded the transaction .';

    protected static $errorMap = [
        self::TRANSFER_TERMINATED_BY_USER                                           => Error\ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,

        // EMandate errors
        self::ACCOUNT_CLOSED                                                        => Error\ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::ACCOUNT_MISMATCH                                                      => Error\ErrorCode::BAD_REQUEST_ACCOUNT_NUMBER_MISMATCH,
        self::ACCOUNT_BLOCKED                                                       => Error\ErrorCode::BAD_REQUEST_ACCOUNT_BLOCKED,
        self::ACCOUNT_DORMANT                                                       => Error\ErrorCode::BAD_REQUEST_ACCOUNT_DORMANT,
        self::ACCOUNT_NO_DEBIT_ALLOWED                                              => Error\ErrorCode::BAD_REQUEST_NO_DR_ALLOWED,
        self::INSUFFICINET_FUNDS                                                    => Error\ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::TXN_AMT_GREATER_THAN_REGISTERED_AMT                                   => Error\ErrorCode::BAD_REQUEST_TRANSACTION_AMOUT_GREATER_THAN_REGISTERED_AMOUNT,
        self::MONTHLY_DEBIT_EXCEEDED_FOR_TXN                                        => Error\ErrorCode::BAD_REQUEST_FREQUENCY_DEBIT_LIMIT_EXCEEDED,
    ];

    public static function getApiErrorCode(string $error): string
    {
        return (self::$errorMap[$error] ?? Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
    }
}
