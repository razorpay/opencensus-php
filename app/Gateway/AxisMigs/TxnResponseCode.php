<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Error;
use RZP\Gateway\AxisMigs;

class TxnResponseCode
{
    public static $messages = [
        '0' => 'Transaction Successful',
        '1' => 'Unknown Error',
        '2' => 'Bank Declined Transaction',
        '3' => 'No Reply from Bank',
        '4' => 'Expired Card',
        '5' => 'Insufficient Funds',
        '6' => 'Error Communicating with Bank',
        '7' => [
            'E5000'   => 'Username and/or password for merchant is invalid.',
            'E5159'   => 'Invalid Card Type',
            'E5408'   => 'Not an auth transaction',
            'E5414'   => [
                'no payments identified' => 'No payments identified',
                'requested capture amount exceeds outstanding authorized amount' => 'Requested capture amount exceeds outstanding authorized amount',
            ],
            'E5415'   => 'Excessive refund attempted',
            'I5154'   => 'Invalid Card Number : Card number is best match for card range in card brand MS and not expected card brand MC',
            'I5166'   => 'Invalid credit card: incorrect secure code number length : Invalid Card Security Code length',
            'I5426'   => 'Invalid Permission : advanceMA',
            'W9520'   => 'Server is unable to process the request at the moment - please try later',
            'default' => 'Payment Server System Error',
        ],
        '8' => 'Transaction Type Not Supported',
        '9' => 'Bank declined transaction (Do not contact Bank)',
        'A' => 'Transaction Aborted',
        'B' => 'Transaction was blocked by the Payment Server because it did not pass all risk checks.',
        'C' => 'Transaction Cancelled',
        'D' => 'Deferred transaction has been received and is awaiting processing',
        'F' => '3D Secure Authentication failed',
        'I' => 'Card Security Code verification failed',
        'L' => 'Shopping Transaction Locked (Please try the transaction again later)',
        'N' => 'Cardholder is not enrolled in 3DSecure Authentication Scheme',
        'P' => 'Transaction has been received by the Payment Adaptor and is being processed',
        'R' => 'Transaction was not processed - Reached limit of retry attempts allowed',
        'S' => 'Duplicate SessionID (OrderInfo)',
        'T' => 'Address Verification Failed',
        'U' => 'Card Security Code Failed',
        'V' => 'Address Verification and Card Security Code Failed',
        '?' => 'Transaction status is unknown',

        'Aborted' => 'Transaction Aborted',
    ];

//    'default' => 'Unable to be determined',
    public static $map = [
        '1' => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '2' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        '3' => Error\ErrorCode::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK,
        '4' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        '5' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        '6' => Error\ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        '7' => [
            'E5000'   => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
            'E5159'   => Error\ErrorCode::GATEWAY_ERROR_UNSUPPORTED_CARD_NETWORK,
            'E5408'   => Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
            'E5414'   => [
                'no payments identified' => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED,
                'requested capture amount exceeds outstanding authorized amount' => Error\ErrorCode::GATEWAY_ERROR_CAPTURE_GREATER_THAN_AUTH,
            ],
            'E5415'   => Error\ErrorCode::GATEWAY_ERROR_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
            'I5154'   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
            'I5166'   => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
            'I5426'   => Error\ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
            'W9520'   => Error\ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
            'default' => Error\ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
        '8' => Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
        '9' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        'A' => Error\ErrorCode::SERVER_ERROR_PAYMENT_ABORTED,
        'B' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        'C' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'E' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'F' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'I' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'L' => Error\ErrorCode::SERVER_ERROR,
        'N' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE,
        // 'P' => '',
        // 'R' => '',
        // 'S' => '',
        // 'T' => '',
        'U' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'V' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        '?' => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        'Aborted' => Error\ErrorCode::BAD_REQUEST_PAYMENT_ABORTED,
    ];

    public static function isErrorCodeMapped($code)
    {
        return (isset(self::$map[$code]) === true);
    }

    public static function getErrorCodeMapped($code, $msg = null)
    {
        if (is_array(self::$map[$code]) === true)
        {
            $subCode = explode('-', explode(':', $msg)[0])[0];

            if (isset(self::$map[$code][$subCode]) === true)
            {
                if (is_array(self::$map[$code][$subCode]) === true)
                {
                    $msgCode = strtolower(last(explode('reason: ', $msg)));

                    if (isset(self::$map[$code][$subCode][$msgCode]) === true)
                    {
                        return self::$map[$code][$subCode][$msgCode];
                    }

                    $subCode = 'default';
                }
            }
            else
            {
                $subCode = 'default';
            }

            return self::$map[$code][$subCode];
        }

        return self::$map[$code];
    }
}
