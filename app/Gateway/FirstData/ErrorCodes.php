<?php

namespace RZP\Gateway\FirstData;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    protected static $reasonCodes = [
        'N:'              => 'Unknown error has occurred.',
        'N:-10501'        => 'PostAuth already performed',
        'N:-10503'        => 'Invalid amount or currency',
        'N:-10601'        => 'Total amount passed is more than the Return/Void amount.',
        'N:-12000'        => 'Card security code is mandatory',
        'N:-2303'         => 'Invalid credit card number',
        'N:-30031'        => 'No terminal found',
        'N:-30050'        => 'Communication Error',
        'N:-30051'        => 'Communication Error',
        'N:-30052'        => 'Transaction timed out',
        'N:-30053'        => 'Transaction timed out',
        'N:-30057'        => 'Communication Error',
        'N:-30059'        => 'Communication Error',
        'N:-30060'        => 'Internal Error',
        'N:-30081'        => 'Internal Error',
        'N:-30095'        => 'System too busy, please retry',
        'N:-30100'        => 'Internal error',
        'N:-42325'        => 'Zero amount not supported',
        'N:-42920'        => 'Invalid request parameter',
        'N:-5002'         => 'Brand/Recurring payments not supported',
        'N:-5003'         => 'The order already exists in the database.',
        'N:-5004'         => 'No authorized preauth found',
        'N:-5005'         => 'FRAUD - Card blocked/duplicate lockout/limit exceeded',
        'N:-5008'         => 'Order does not exist.',
        'N:-5009'         => 'No transaction to return found',
        'N:-5014'         => 'Validation problem',
        'N:-50653'        => 'Sent invalid currency or no currencies were setup for this store.',
        'N:-5100'         => 'Invalid 3D Secure values',
        'N:-5101'         => '3D Secure authentication failed',
        'N:-5102'         => 'ECI 7',
        'N:-5103'         => 'Cardholder did not return from ACS',
        'N:-5110'         => 'Cardholder did not return from Rupay',
        'N:-5111'         => 'ECI 1 and ECI6',
        'N:-5993'         => 'Cancelled by user',
        'N:-5996'         => 'Invalid card type',
        'N:-5997'         => 'The maximum number of transactions per order has been exceeded',
        'N:-63096'        => 'Setup Error',
        'N:-70103'        => 'VOID is currently not supported for RETURN for this endpoint',
        'N:-7777'         => 'System too busy, please retry',
        'N:-7778'         => 'Transaction timed out, please retry',
        'N:0'             => 'OCEAN',
        'N:04'            => 'Pick-up',
        'N:12'            => 'Invalid transaction',
        'N:13'            => 'Invalid amount',
        'N:14'            => 'Invalid card number (no such number)',
        'N:2'             => 'This transaction is already in process or already processed',
        'N:200'           => 'Transaction Cancelled',
        'N:2006'          => 'Hash Data is invalid',
        'N:2010'          => 'Transaction not found with provided details',
        'N:2012'          => 'Transaction was not posted to Net Banking',
        'N:2013'          => 'Transaction rejected from Net Banking Due to cancellation/failed verification',
        'N:2015'          => 'Merchant ID is not supported. Please contact Administrator',
        'N:3'             => 'Invalid merchant',
        'N:30'            => 'Format error',
        'N:33'            => 'Expired card',
        'N:400'           => 'User Inactive',
        'N:408'           => 'terminal_state_code exceeded. Usually means Rupay not enabled for this terminal.',
        'N:41'            => 'Lost card',
        'N:410'           => 'Failed Initiate CheckBin - Error with Bin Check',
        'N:412'           => 'Issuer Authentication Server failure',
        'N:43'            => 'Stolen card',
        'N:5'             => 'Do not honour',
        'N:51'            => 'Not sufficient funds',
        'N:54'            => 'Expired card',
        'N:55'            => 'Incorrect Personal Identification Number',
        'N:57'            => 'Transaction not permitted to cardholder',
        'N:59'            => 'Suspected fraud',
        'N:61'            => 'Exceeds withdrawal amount limit',
        'N:62'            => 'Restricted card',
        'N:65'            => 'Exceeds withdrawal frequency limit',
        'N:75'            => 'Allowable number of PIN tries exceeded',
        'N:800'           => 'General Error',
        'N:8400'          => 'Communication Error with NPCI',
        'N:87'            => 'Bad Track Data',
        'N:89'            => 'Invalid route service',
        'N:9'             => 'Data is not  present in database',
        'N:91'            => 'Issuer or switch is inoperative',
        'N:94'            => 'Duplicate transmission',
        'N:96'            => 'Session expired for this transaction',
        'N:CI'            => 'Compliance error code for issuer',
        'N:N0'            => 'Unable to authorize',
        'N:P9'            => 'Enter lesser amount',
        'N:Q1'            => 'Invalid expiration date',
        'N:T2'            => 'Invalid transaction date',
        'N:T5'            => 'CAF status = 0 or 9',
        'N:T8'            => 'Invalid account',

        // Not part of originally provided list of error codes
        // Added by us to handle unexpected behaviour

        'N:100'           => 'Internal Error on FirstData side, contact pghelpdesk with payment id',
        'N:5003'          => 'The order already exists in the database.',
        'N:-100'          => 'Internal error',
        'N:-43232'        => 'Card function not supported',
        'N:02'            => 'This transaction is already in process or already processed',
        'N:03'            => 'Invalid merchant',
        'N:05'            => 'Do not honour',
        'N:39'            => 'No credit account',
        'N:42'            => 'No universal account',
        'N:68'            => 'Acquirer time-out',
        'N:15'            => 'No such issuer',
        'N:999'           => 'Transaction Cancelled',
        'N:9993'          => 'Cardholder not return from Rupay',
        'N:tmout'         => 'Gateway timed out',
        '?:waiting RUPAY' => 'Waiting for Rupay',
        'N:-30084'        => 'Cannot return chargeback transaction',

        // Refund related
        'N:-5995'         => 'order too old to be referenced',
    ];

    protected static $errorCodeMap = [
        'N:'              => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:-10501'        => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
        'N:-10503'        => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        'N:-10601'        => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_REFUNDED,
        'N:-12000'        => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'N:-2303'         => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        'N:-30031'        => ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED,
        'N:-30050'        => ErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,
        'N:-30051'        => ErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,
        'N:-30052'        => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        'N:-30053'        => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        'N:-30057'        => ErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,
        'N:-30059'        => ErrorCode::GATEWAY_ERROR_COMMUNICATION_ERROR,
        'N:-30060'        => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:-30081'        => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:-30095'        => ErrorCode::GATEWAY_ERROR_SYSTEM_BUSY,
        'N:-30100'        => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:-42325'        => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
        'N:-42920'        => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        'N:-5002'         => ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
        'N:-5003'         => ErrorCode::GATEWAY_ERROR_ORDER_EXISTS,
        'N:-5004'         => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_CAPTURE,
        'N:-5005'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_GATEWAY_DUE_TO_RISK,
        'N:-5008'         => ErrorCode::BAD_REQUEST_ORDER_DOES_NOT_EXIST,
        'N:-5009'         => ErrorCode::GATEWAY_ERROR_NO_RECORDS_FOUND,
        'N:-5014'         => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        'N:-50653'        => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        'N:-5100'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5101'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5102'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5103'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5110'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5111'         => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:-5993'         => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        'N:-5996'         => ErrorCode::BAD_REQUEST_PAYMENT_CARD_TYPE_INVALID,
        'N:-5997'         => ErrorCode::BAD_REQUEST_PAYMENT_MAX_TRANSACTIONS_PER_ORDER_EXCEEDED,
        'N:-63096'        => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'N:-70103'        => ErrorCode::BAD_REQUEST_PAYMENT_VOID_NOT_SUPPORTED,
        'N:-7777'         => ErrorCode::GATEWAY_ERROR_SYSTEM_BUSY,
        'N:-7778'         => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        'N:0'             => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:04'            => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        'N:12'            => ErrorCode::GATEWAY_ERROR_NO_RECORDS_FOUND,
        'N:13'            => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        'N:14'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NUMBER_NOT_LEGITIMATE,
        'N:2'             => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        'N:200'           => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'N:2006'          => ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_HASH,
        'N:2010'          => ErrorCode::GATEWAY_ERROR_NO_RECORDS_FOUND,
        'N:2012'          => ErrorCode::BAD_REQUEST_PAYMENT_TXN_NOT_PUSHED_TO_NET_BANKING,
        'N:2013'          => ErrorCode::BAD_REQUEST_PAYMENT_TXN_REJECTED_FROM_NET_BANKING,
        'N:2015'          => ErrorCode::BAD_REQUEST_UNABLE_TO_AUTHORIZE_PAYMENT,
        'N:3'             => ErrorCode::BAD_REQUEST_MERCHANT_INVALID,
        'N:30'            => ErrorCode::GATEWAY_ERROR_INVALID_FORMAT,
        'N:33'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        'N:400'           => ErrorCode::GATEWAY_ERROR_USER_INACTIVE,
        'N:408'           => ErrorCode::GATEWAY_ERROR_CARD_RUPAY_MAESTRO_NOT_ENABLED,
        'N:41'            => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        'N:410'           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN,
        'N:412'           => ErrorCode::BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE,
        'N:43'            => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        'N:5'             => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'N:51'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        'N:54'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        'N:55'            => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        'N:57'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN,
        'N:59'            => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        'N:61'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED,
        'N:62'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        'N:65'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED,
        'N:75'            => ErrorCode::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
        'N:800'           => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'N:8400'          => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        'N:87'            => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'N:89'            => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        'N:9'             => ErrorCode::BAD_REQUEST_PAYMENT_MISSING_DATA,
        'N:91'            => ErrorCode::BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE,
        'N:94'            => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        'N:96'            => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BECAUSE_SESSION_EXPIRED,
        'N:CI'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT,
        'N:N0'            => ErrorCode::BAD_REQUEST_UNABLE_TO_AUTHORIZE_PAYMENT,
        'N:P9'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        'N:Q1'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE,
        'N:T2'            => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_TRANSACTION_DATE,
        'N:T5'            => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        'N:T8'            => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,

        // Not part of originally provided list of error codes
        // Added by us to handle unexpected behaviour

        'N:100'           => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:5003'          => ErrorCode::GATEWAY_ERROR_ORDER_EXISTS,
        'N:02'            => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        'N:03'            => ErrorCode::BAD_REQUEST_MERCHANT_INVALID,
        'N:05'            => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'N:15'            => ErrorCode::GATEWAY_ERROR_PAYMENT_BIN_CHECK_FAILED,
        'N:39'            => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
        'N:42'            => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
        'N:68'            => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        'N:-100'          => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'N:-43232'        => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_NOT_PERMITTED_TXN,
        'N:999'           => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'N:9993'          => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        'N:tmout'         => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,
        '?:waiting RUPAY' => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT_AT_GATEWAY,

        // Refund Related
        'N:-5995'         => ErrorCode::BAD_REQUEST_REFUND_PAYMENT_OLDER_THAN_SIX_MONTHS,

        //Chargeback Related
        'N:-30084'        => ErrorCode::GATEWAY_CHARGEBACK_REFUND_FAILURE,
    ];

    protected static $specialCases = [
        // Internal Error
        // FirstData is down, and should be notified with the order id
        'N:100',
        'N:-100',

        // Invalid StoreId
        // For some reason, FirstData is not recognising the MID they provided
        'N:03',
        'N:3',

        // This is the error received when we try to do preauth for a card
        // that doesn't allow it, eg. ICICI debit on FirstData
        'N:57',

        // terminal_state_code error
        // Usually happens when Rupay/Maestro are not enabled for the terminal
        // Notify pghelpdesk, with the gateway_merchant_id
        'N:408',
    ];

    public static function getMappedCode($code = null)
    {
        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }

    public static function getErrorDesc($code = null)
    {
        if (isset(self::$reasonCodes[$code]))
        {
            return self::$reasonCodes[$code];
        }

        return 'General Error';
    }

    public static function isSpecialCase($code)
    {
         return in_array($code, self::$specialCases, true);
    }

    public static function getTimeoutCode()
    {
        return 'N:tmout';
    }
}
