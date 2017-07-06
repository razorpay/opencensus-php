<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = [
        '901'    => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '902'    => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '905'    => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        '909'    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        '910'    => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND,
        '912'    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '913'    => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '920'    => ErrorCode::GATEWAY_ERROR_INVALID_DATE_FORMAT,
        '923'    => ErrorCode::GATEWAY_ERROR_PAYMENT_CREDIT_LESS_THAN_DEBIT,
        '930'    => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        '931'    => ErrorCode::GATEWAY_ERROR_INVALID_DATE_FORMAT,
        '999'    => [
            'Any other Airtel Money failure'                                                                                            => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
            'Transaction could not be processed as airtel money account has been blocked. Please reset mPIN or call 400 for assistance' => ErrorCode::BAD_REQUEST_AIRTEL_MONEY_ACCOUNT_BLOCKED,
            'Transaction cancelled by user.'                                                                                            => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
            'You have not changed your mPIN. Please call 121 to reset mPIN.'                                                            => ErrorCode::BAD_REQUEST_AIRTEL_MONEY_RESET_MPIN,
            'Internal configuration error. Please contact our call center for support.'                                                 => ErrorCode::GATEWAY_ERROR_INVALID_CONFIGURATION,
            'Sorry! The actor details you entered are not valid. Kindly try again with correct details.'                                => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_CREDENTIALS,
            'Your request could not be processed as the actor status in not active'                                                     => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INACTIVE,
            'Request timeout. Please try again later'                                                                                   => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_WALLET_PAYMENT_PAGE,
        ],
        '13365'  => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        '14236'  => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        '35007'  => ErrorCode::BAD_REQUEST_CONTACT_AIRTEL_MONEY_CUSTOMER_CARE_FOR_REFUND,
        '54074'  => ErrorCode::GATEWAY_ERROR_REFUND_NOT_ALLOWED_ON_THE_WALLET,
        '55550'  => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND,
        '999999' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR
    ];

    public static function getApiErrorCode($code, string $msg = null)
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
