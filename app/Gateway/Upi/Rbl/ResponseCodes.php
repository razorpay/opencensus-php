<?php

namespace RZP\Gateway\Upi\Rbl;

use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base\UpiErrorCodes;

class ResponseCodes
{
    protected static $responseCodes = [
        'E001'     => 'Invalid Auth token',
        'E003'     => 'Invalid MCC E003',
        'E004'     => 'No channel found or channel is not active E004',
        'E005'     => 'System Error E005',
        'E006'     => 'Aggregator Id, Merchant Id and hmac are Required E006',
        'E007'     => 'Invalid Search Request! E007',
        'E008'     => 'Invalid Payment Request! E008',
        'E009'     => 'Invalid Id Type! E009',
        'ERR00072' => 'Invalid ValidUpto Format ERR00072',
        'ERR00068' => 'Invalid VPA ERR00068',
        'ERR00094' => 'Invalid TxnId! ERR00094',
        'ERR010'   => 'Invalid RefId! ERR010',
        'MRER000'  => 'SYSTEM_ERROR MRER000',
        'MRER002'  => 'NULL_VALUE MRER002',
        'MRER003'  => 'EMPTY_STRING MRER003',
        'MRER004'  => 'INVALID_FORMAT MRER004',
        'MRER005'  => 'MIN_LENGTH_REQUIRED MRER005',
        'MRER006'  => 'MAX_LENGTH_EXCEED MRER006',
        'MRER007'  => 'MIN_VALUE_REQUIRED MRER007',
        'MRER008'  => 'MAX_VALUE_EXCEED MRER008',
        'MRER009'  => 'NOT_NUMERIC MRER009',
        'MRER010'  => 'INVALID_ID_NUMBER',
    ];

    protected static $responseCodeMap = [
        'E001'     => ErrorCode::GATEWAY_ERROR_TOKEN_VALIDATION_FAILED,
        'E003'     => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'E004'     => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        'E005'     => ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        'E006'     => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'E007'     => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        'E008'     => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        'E009'     => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'ERR00072' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'ERR00068' => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        'ERR00094' => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'ERR010'   => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER000'  => ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        'MRER002'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER003'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER004'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER005'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER006'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER007'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER008'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER009'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'MRER010'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];


    public static function getApiResponseCode($code)
    {
        return self::$responseCodeMap[$code] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public static function getResponseMessage($code)
    {
        return self::$responseCodes[$code] ?? 'Unknown Gateway Response Code';
    }
}
