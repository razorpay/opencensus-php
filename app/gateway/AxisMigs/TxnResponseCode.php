<?php

namespace Gateway\AxisMigs;

use EE\Error;
use Gateway\AxisMigs;

class TxnResponseCode
{
    public static $messages = array(
        '0' => 'Transaction Successful',
        '1' => 'Unknown Error',
        '2' => 'Bank Declined Transaction',
        '3' => 'No Reply from Bank',
        '4' => 'Expired Card',
        '5' => 'Insufficient Funds',
        '6' => 'Error Communicating with Bank',
        '7' => 'Payment Server System Error',
        '8' => 'Transaction Type Not Supported',
        '9' => 'Bank declined transaction (Do not contact Bank)',
        'A' => 'Transaction Aborted',
        'B' => 'Transaction was blocked by the Payment Server because it did not pass all risk checks.',
        'C' => 'Transaction Cancelled',
        'D' => 'Deferred transaction has been received and is awaiting processing',
        'F' => '3D Secure Authentication failed',
        'I' => 'Card Security Code verification failed',
        'L' => 'Shopping Transaction Locked (Please try the transaction again later)',
        'N' => 'Cardholder is not enrolled in Authentication Scheme',
        'P' => 'Transaction has been received by the Payment Adaptor and is being processed',
        'R' => 'Transaction was not processed - Reached limit of retry attempts allowed',
        'S' => 'Duplicate SessionID (OrderInfo)',
        'T' => 'Address Verification Failed',
        'U' => 'Card Security Code Failed',
        'V' => 'Address Verification and Card Security Code Failed',
        '?' => 'Transaction status is unknown',

        'Aborted' => 'Transaction Aborted',
    );

//    'default' => 'Unable to be determined',
    public static $map = array(
        '1' => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '2' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        '3' => Error\ErrorCode::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK,
        '4' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        '5' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        '6' => Error\ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        '7' => Error\ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        '8' => Error\ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
        '9' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK,
        'A' => Error\ErrorCode::SERVER_ERROR_PAYMENT_ABORTED,
        'B' => Error\ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_BY_BANK_DUE_TO_RISK_CHECKS_FAILURE,
        'C' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'E' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'F' => Error\ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'I' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'L' => Error\ErrorCode::SERVER_ERROR,
        // 'N' => '',
        // 'P' => '',
        // 'R' => '',
        // 'S' => '',
        // 'T' => '',
        'U' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'V' => Error\ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        '?' => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,

        'Aborted' => Error\ErrorCode::BAD_REQUEST_PAYMENT_ABORTED,
    );

}
