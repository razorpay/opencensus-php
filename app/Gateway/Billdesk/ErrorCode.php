<?php

namespace RZP\Gateway\Billdesk;

use RZP\Error\ErrorCode as GenericErrorCode;

class ErrorCode
{
    /*
     * ErrorStatus : ERR122
     * ErrorDescription : Sorry.  We were unable to process your transaction.
     * We apologise for the inconvenience and request you to try again later.
     */
    const ERR122 = 'ERR122';
    const ERR_REF009 = 'ERR_REF009';

    /*
     * ErrorCode : ERR_REF010
     * ErrorReason : Refund amount greater than transaction amount
     */
    const ERR_REF010 = 'ERR_REF010';

    /*
     * ErrorCode : ERR_REF013
     * ErrorReason : Cannot process request right now. Duplicate request
     */
    const ERR_REF013 = 'ERR_REF013';


    const NA = 'NA';

    // @codingStandardsIgnoreStart
    protected static $codes = [
        self::ERR122                                                            => GenericErrorCode::GATEWAY_ERROR_SYSTEM_BUSY,
        self::ERR_REF009                                                        => GenericErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERR_REF010                                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        self::ERR_REF013                                                        => GenericErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::NA => [
            'account is inoperative. pl contact branch'                         => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED,
            'account closed. please contact your branch.'                       => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED,
            'account has hold. no free balance available for this transaction.' => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,
            'account stopped. please contact your branch.'                      => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,
            'account/transaction failure'                                       => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'cancelled'                                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
            'clear balance funds not available.'                                => GenericErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
            'currently, inter branch credits are not allowed in core.'          => GenericErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
            'excess drawing not allowed in staff accounts'                      => GenericErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
            'error occurred while fetching customer data'                       => GenericErrorCode::BAD_REQUEST_USER_NOT_FOUND,
            'failure. transaction not attempted'                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'failure'                                                           => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'insufficient funds.'                                               => GenericErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
            'insufficient-funds'                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
            'merchant transaction is cancelled by user'                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
            'no valid accounts found'                                           => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,
            'pan number is now mandatory in your account for txn. pl contact your branch for pan updation'
                                                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'payment not authorized'                                            => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'repayment schedule is invalid. please contact your branch.'        => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'sorry, unable to process your request. please try later.'          => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'transaction terminated by user'                                    => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
            'transaction canceled by customer'                                  => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
            'transaction failed at bank'                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'transaction not authorize'                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'transaction not authorized'                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'transaction was cancelled'                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
            'transaction-cancelled'                                             => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_NETBANKING_PAYMENT_PAGE,
            'we are experiencing network delays. we can let you know in one hour if the transaction was put through successfully. apologise for the inconvenience.'
                                                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'we were unable to put through this transaction owing to network errors.'
                                                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Billdesk return errors classified with error codes but in most case code comes as NA.
     * In that case, map errors with error message. Check if code is mapped to GenericErrorCode,
     * Otherwise check if error message is properly mapped
     *
     * @param $code
     * @param $msg
     * @return Mapped Error Code
     */
    public static function getMappedCode($code, $msg)
    {
        $errorCode = GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        if (empty(self::$codes[$code]) === false)
        {
            if (is_array(self::$codes[$code]) === true)
            {
                $msg = strtolower($msg);

                if (empty(self::$codes[$code][$msg]) === false)
                {
                    $errorCode = self::$codes[$code][$msg];
                }
            }
            else
            {
                $errorCode = self::$codes[$code];
            }
        }

        return $errorCode;
    }
}
