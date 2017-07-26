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

    protected static $codes = [
        self::ERR122                                                            => GenericErrorCode::GATEWAY_ERROR_SYSTEM_BUSY,
        self::ERR_REF009                                                        => GenericErrorCode::GATEWAY_ERROR_REQUEST_ERROR,# TODO Verify
        self::ERR_REF010                                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        self::ERR_REF013                                                        => GenericErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::NA => [
            'ACCOUNT IS INOPERATIVE. PL CONTACT BRANCH'                         => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED,
            'Account closed. Please contact your Branch.'                       => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_DISABLED,
            'Account has hold. No free balance available for this transaction.' => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,
            'Account stopped. Please contact your Branch.'                      => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,# TODO Verify
            'Account/Transaction Failure'                                       => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'Cancelled'                                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
            'Clear Balance Funds not available.'                                => GenericErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE,
            'Currently, inter branch credits are not allowed in Core.'          => GenericErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
            'EXCESS DRAWING NOT ALLOWED IN STAFF ACCOUNTS'                      => GenericErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
            'Error occurred while fetching customer data'                       => GenericErrorCode::BAD_REQUEST_USER_NOT_FOUND,
            'Failure. Transaction not attempted'                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'Failure'                                                           => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,
            'Insufficient funds.'                                               => GenericErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE,
            'Insufficient-funds'                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE,
            'Merchant transaction is cancelled by user'                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
            'No Valid accounts found'                                           => GenericErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED,
            'PAN number is now mandatory in your account for txn. Pl contact your branch for PAN updation'
                                                                                => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,# TODO Verify
            'Payment not authorized'                                            => GenericErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED,
            'Repayment Schedule is Invalid. Please contact your Branch.'        => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,# TODO Verify
            'Sorry, unable to process your request. Please try later.'          => GenericErrorCode::BAD_REQUEST_PAYMENT_FAILED,# TODO Verify
            'TRANSACTION TERMINATED BY USER'                                    => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
            'Transaction canceled by customer'                                  => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
            'Transaction failed at Bank'                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
            'Transaction not authorize'                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED,
            'Transaction not authorized'                                        => GenericErrorCode::BAD_REQUEST_PAYMENT_NOT_AUTHORIZED,
            'Transaction was Cancelled'                                         => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
            'Transaction-cancelled'                                             => GenericErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
            'We are experiencing network delays. We can let you know in one hour if the transaction was put through successfully. Apologise for the inconvenience.'
                                                                                => GenericErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,# TODO Verify
            'We were unable to put through this transaction owing to network errors.'
                                                                                => GenericErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,# TODO Verify
        ],
    ];

    public static function getMappedCode($code, $msg)
    {
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        if (empty(self::$codes[$code]) === false)
        {
            if (is_array(self::$codes[$code]) === true)
            {
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
