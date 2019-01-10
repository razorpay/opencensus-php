<?php

namespace RZP\Gateway\Netbanking\Pnb;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $errorCodeMap = [
        '1000' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Transaction failed',
        '1001' => ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY, //'The api key field is incorrect',
        '1002' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'The live mode access is not allowed',
        '1003' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //'The order id field should be unique',
        '1004' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //'The order id field is not found',
        '1005' => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR, //'Invalid authentication at bank',
        '1006' => ErrorCode::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK, //'Waiting for the response from bank',
        '1007' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //'Invalid input in the request message',
        '1008' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD, //'Your transaction is successful, but we suspect malicious activity in your account. Please contact the support team',
        '1009' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK, //'Bank Declined Transaction',
        '1010' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //'Amount cannot be less than 1',
        '1011' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Authorization refused',
        '1016' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_AMOUNT_LIMIT_REACHED, //'Total Amount limit set for the terminal for transactions has been crossed',
        '1017' => ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_TRANSACTION_LIMIT_REACHED, //'Total transaction limit set for the terminal has been crossed',
        '1023' => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED, //'Hash Mismatch',
        '1024' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS, //'Invalid parameters',
        '9999' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Unknown error occurred',
        '1025' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE, //'Invalid bank code',
        '1026' => ErrorCode::GATEWAY_ERROR_MERCHANT_NOT_ENABLED, //'Merchant is not active',
        '1027' => ErrorCode::GATEWAY_ERROR_GENERIC_TRANSACTION_ERROR, //'Transaction is invalid',
        '1028' => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND, //'Transaction not found',
        '1029' => ErrorCode::GATEWAY_ERROR_GENERIC_TRANSACTION_ERROR, //'Transaction terminated',
        '1030' => ErrorCode::GATEWAY_ERROR_GENERIC_TRANSACTION_ERROR, //'Transaction incomplete',
        '1031' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR, //'Transaction auto refunded',
        '1032' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUNDED, //'Transaction refunded',
        '1033' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT, //'The amount provided is less than transaction lower limit',
        '1034' => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_TOO_HIGH, //'The amount provided is more than transaction upper limit',
        '1035' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //'The daily transaction limit is exceeded for the merchant',
        '1036' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //'The monthly transaction limit is exceeded for the merchant',
        '1037' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //'The daily transaction number is exceeded for the merchant',
        '1038' => ErrorCode::BAD_REQUEST_TRANSACTIONS_LIMIT_REACHED, //'The monthly transaction number is exceeded for the merchant',
        '1039' => ErrorCode::GATEWAY_ERROR_REFUND_AMOUNT_GREATER_THAN_CAPTURED, //'The refund amount is greater than transaction amount',
        '1042' => ErrorCode::BAD_REQUEST_PAYMENT_NO_RESPONSE_RECEIVED_FROM_BANK, //'Transaction failed as there was no response from bank',
        '1043' => ErrorCode::GATEWAY_ERROR_GENERIC_TRANSACTION_ERROR, //'Transaction cancelled'
    ];

    public static function getErrorCodeMap($errorCode)
    {
        if (isset(self::$errorCodeMap[$errorCode]) === true)
        {
            return self::$errorCodeMap[$errorCode];
        }

        return ErrorCode::GATEWAY_ERROR_FATAL_ERROR;
    }
}
