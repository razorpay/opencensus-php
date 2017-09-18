<?php

namespace RZP\Gateway\Hitachi;

use RZP\Error\ErrorCode;

class ResponseCode
{
    const DEFAULT_MESSAGE = 'No Response Message found in mapping';

    const SUCCESS_CODE = '00';

    const DEFAULT_ERROR_CODE = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

    protected static $map = [
        '00' => 'Successful approval/completion or that V.I.P. PIN verification is valid',
        '01' => 'Refer to card issuer',
        '02' => 'Refer to card issuer, special condition',
        '03' => 'Invalid merchant or service provider',
        '04' => 'Pickup card',
        '05' => 'Do not honour',
        '06' => 'Error',
        '07' => 'Pickup card, special condition (other than lost/stolen card)',
        '10' => 'Partial Approval',
        '11' => 'V.I.P. approval',
        '12' => 'Invalid transaction',
        '13' => 'Invalid amount (currency conversion field overflow) or amount exceeds maximum for card program',
        '14' => 'Invalid account number (no such number)',
        '15' => 'No such issuer',
        '17' => 'Customer cancellation',
        '19' => 'Re-enter transaction',
        '20' => 'Invalid response',
        '21' => 'No action taken (unable to back out prior transaction)',
        '22' => 'Suspected Malfunction',
        '25' => 'Unable to locate record in file, or account number is missing from the inquiry',
        '28' => 'File is temporarily unavailable',
        '30' => 'Format Error',
        '41' => 'Pickup card (lost card)',
        '43' => 'Pickup card (stolen card)',
        '51' => 'Insufficient funds',
        '52' => 'No checking account',
        '53' => 'No savings account',
        '54' => 'Expired card',
        '55' => 'Incorrect PIN',
        '57' => 'Transaction not permitted to cardholder',
        '58' => 'Transaction not allowed at terminal',
        '59' => 'Suspected fraud',
        '61' => 'Activity amount limit exceeded',
        '62' => 'Restricted card (for example, in Country Exclusion table)',
        '63' => 'Security violation',
        '65' => 'Activity count limit exceeded',
        '68' => 'Response received too late',
        '69' => 'Cardholder/Issuer Not Enrolled with 3D Secure',
        '70' => '3D Secure Authentication Failure',
        '75' => 'Allowable number of PIN-entry tries exceeded',
        '76' => 'Unable to locate previous message (no match on Retrieval Reference number)',
        '77' => 'Previous message located for a repeat or reversal, but repeat or reversal data are
        inconsistent with original message',
        '78' => '’Blocked, first used’—The transaction is from a new cardholder, and the card has not been
        properly unblocked.',
        '80' => 'Visa transactions: credit issuer unavailable. Private label and check acceptance: Invalid
        date',
        '81' => 'PIN cryptographic error found (error found by VIC security module during PIN decryption)',
        '82' => 'Negative CAM, dCVV, iCVV, or CVV results',
        '83' => 'Unable to verify PIN',
        '85' => 'No reason to decline a request for account number verification, address verification, CVV2
        verification, or a credit voucher or merchandise return',
        '91' => 'Issuer unavailable or switch inoperative',
        '92' => 'Destination cannot be found for routing',
        '93' => 'Transaction cannot be completed, violation of law',
        '94' => 'Duplicate Transmission',
        '95' => 'Reconcile error',
        '96' => 'System malfunction, System malfunction or certain field error conditions',
        'B1' => 'Surcharge amount not permitted on Visa cards (U.S. acquirers only)',
        'N0' => 'Force STIP',
        'N3' => 'Cash service not available',
        'N4' => 'Cashback request exceeds issuer limit',
        'N7' => 'Decline for CVV2 failure',
        'P2' => 'Invalid biller information',
        'P5' => 'PIN Change/Unblock request declined',
        'P6' => 'Unsafe PIN',
        'Q1' => 'Card Authentication failed',
        'R0' => 'Stop Payment Order',
        'R1' => 'Revocation of Authorization Order',
        'R3' => 'Revocation of All Authorizations Order',
        'XA' => 'Forward to issuer',
        'XD' => 'Forward to issuer',
        'Z3' => 'Unable to go online',
    ];

    protected static $responseCodeToErrorCodeMap = [
        '01' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        '02' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        '03' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '04' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '05' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        '06' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '07' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '10' => ErrorCode::BAD_REQUEST_PAYMENT_PARTIAL_AMOUNT_APPROVED,
        '11' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '12' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '13' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        '14' => ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
        '15' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
        '17' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        '19' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '20' => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
        '21' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '22' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '25' => ErrorCode::BAD_REQUEST_BANK_ACCOUNT_ID_MISSING,
        '28' => ErrorCode::BAD_REQUEST_FILE_NOT_FOUND,
        '30' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_FORMAT,
        '41' => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        '43' => ErrorCode::BAD_REQUEST_CARD_STOLEN_OR_LOST,
        '51' => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        '52' => ErrorCode::BAD_REQUEST_PAYMENT_TXN_REJECTED_FROM_NET_BANKING,
        '53' => ErrorCode::BAD_REQUEST_PAYMENT_TXN_REJECTED_FROM_NET_BANKING,
        '54' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_EXPIRED,
        '55' => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        '57' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        '58' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '59' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '61' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED,
        '62' => ErrorCode::BAD_REQUEST_INVALID_COUNTRY,
        '63' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '65' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_WITHDRAWAL_LIMITS_EXCEEDED,
        '68' => ErrorCode::BAD_REQUEST_PAYMENT_LATE_RESPONSE_RECEIVED_FROM_BANK,
        '69' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE,
        '70' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        '75' => ErrorCode::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
        '76' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        '77' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        '78' => ErrorCode::BAD_REQUEST_CARD_INACTIVE,
        '80' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_ISSUING_BANK_NOT_PERMITTING_PAYMENT,
        '81' => ErrorCode::GATEWAY_ERROR_PIN_CRYPTOGRAPHY_ERROR,
        '82' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        '83' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_PIN,
        '85' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '91' => ErrorCode::GATEWAY_ERROR_SWITCH_UNOPERATIVE,
        '92' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '93' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        '94' => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        '95' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '96' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        'B1' => ErrorCode::BAD_REQUEST_SURCHARGE_AMOUNT_NOT_PERMITTED,
        'N0' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'N3' => ErrorCode::BAD_REQUEST_CARD_ISSUING_BANK_UNAVAILABLE,
        'N4' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'N7' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_CVV,
        'P2' => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        'P5' => ErrorCode::GATEWAY_ERROR_PIN_CHANGE_FAILED,
        'P6' => ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD,
        'Q1' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        'R0' => ErrorCode::SERVER_ERROR_PAYMENT_ABORTED,
        'R1' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'R3' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'XA' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'XD' => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        'Z3' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
    ];

    public static function getErrorCode(string $code)
    {
        if (isset(self::$responseCodeToErrorCodeMap[$code]) === true)
        {
            return self::$responseCodeToErrorCodeMap[$code];
        }

        return self::DEFAULT_ERROR_CODE;
    }

    public static function getResponseMessage(string $code)
    {
        if (isset(self::$map[$code]) === true)
        {
            return self::$map[$code];
        }

        return self::DEFAULT_MESSAGE;
    }

    public static function getSuccessCode()
    {
        return self::SUCCESS_CODE;
    }
}
