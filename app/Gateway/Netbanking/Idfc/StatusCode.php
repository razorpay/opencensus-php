<?php

namespace RZP\Gateway\Netbanking\Idfc;

use RZP\Error\ErrorCode;

class StatusCode
{
    const SUCCESS_CODE = 'SUC000';

    const ACCOUNT_OR_TRANSACTION_FAILURE    = 'ACT001';
    const TXN_TYPE_NOT_MATCH                = 'TXN002';
    const MERCHANT_ID_NOT_MATCH             = 'MID003';
    const PAYMENT_ID_NOT_IN_REQUEST         = 'PID004';
    const AMOUNT_NOT_IN_FORMAT              = 'AMT005';
    const CURRENCY_NOT_IN_FORMAT            = 'CRN006';
    const RETURL_NOT_IN_REQUEST             = 'RET007';
    const CHECKSUM_FAILED                   = 'CHK008';
    const MERCHANT_CODE_INVALID             = 'MCC010';
    const CHANNEL_INVALID                   = 'CHN011';
    const NARATION_EXCEEDS_CHAR_LIMIT       = 'NAR012';
    const DUPLICATE_MERCHANT_TXN_ID         = 'DUPTX013';
    const MANDATORY_FIELD_VALIDATION_FAILED = 'MAN014';
    const INVALID_REQUEST                   = 'GEN015';
    const RETURL_INCORRECT                  = 'RET016';
    const BANK_REF_NUMBER_NOT_FOUND         = 'BID017';
    const TXN_CANCELLED_BY_CUST             = 'CAN018';

    const STATUS_CODE_TO_INTERNAL_CODE_MAP = [
        self::ACCOUNT_OR_TRANSACTION_FAILURE    => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::TXN_TYPE_NOT_MATCH                => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
        self::MERCHANT_ID_NOT_MATCH             => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::PAYMENT_ID_NOT_IN_REQUEST         => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        self::AMOUNT_NOT_IN_FORMAT              => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::CURRENCY_NOT_IN_FORMAT            => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::RETURL_NOT_IN_REQUEST             => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        self::CHECKSUM_FAILED                   => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        self::MERCHANT_CODE_INVALID             => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::CHANNEL_INVALID                   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::NARATION_EXCEEDS_CHAR_LIMIT       => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::DUPLICATE_MERCHANT_TXN_ID         => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::MANDATORY_FIELD_VALIDATION_FAILED => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::INVALID_REQUEST                   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::RETURL_INCORRECT                  => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        self::BANK_REF_NUMBER_NOT_FOUND         => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::TXN_CANCELLED_BY_CUST             => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
    ];

    public static function getInternalErrorCode(string $status)
    {
        return self::STATUS_CODE_TO_INTERNAL_CODE_MAP[$status] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
