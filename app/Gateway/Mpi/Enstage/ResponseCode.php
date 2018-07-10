<?php

namespace RZP\Gateway\Mpi\Enstage;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $responseCodes = [
        '000' => 'SuccessFully Authenticated',
        '001' => 'WRONG OTP',
        '002' => 'MISSING PARAMETER',
        '003' => 'OTP SENT FAILED',
        '004' => 'FAILED TO FIND TRANSACTION',
        '005' => 'AMOUNT ERROR',
        '006' => 'ISSUER found PAN to be invalid',
        '007' => 'ISSUER reported card as blocked',
        '008' => 'ISSUER not supporting OTP authentication:',
        '009' => 'DECLINED (invalid encryption)',
        '010' => 'DECLINED (Message Hash Failed)',
        '011' => 'MISSING MANDATORY FIELDS',
        '012' => 'Technical issue at ACS',
        '013' => 'NO ROUTING AVAILABLE',
        '014' => 'SYSTEM UNAVAILABLE',
        '015' => 'INVALID REQUEST',
        '016' => 'CARD NOT PARTICITIPATING IN 3ds',
        '017' => 'OTP re-send time-up',
        '018' => 'DECLINED (Mobile number not present)',
    ];

    public static $responseCodeMap = [
        '001' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_HOLDER_AUTHENTICATION_FAILED,
        '002' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        '003' => ErrorCode::BAD_REQUEST_SMS_FAILED,
        '004' => ErrorCode::GATEWAY_ERROR_PAYMENT_TRANSACTION_NOT_FOUND,
        '005' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '006' => ErrorCode::GATEWAY_ERROR_CARD_NOT_ENROLLED,
        '007' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        '008' => ErrorCode::GATEWAY_ERROR_PAYMENT_AUTHENTICATION_ERROR,
        '009' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
        '010' => ErrorCode::SERVER_ERROR_INTEGRATION_ERROR,
        '011' => ErrorCode::SERVER_ERROR_INTEGRATION_ERROR,
        '012' => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        '013' => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        '014' => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        '015' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        '016' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENROLLED_FOR_3DSECURE,
        '017' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
        '018' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_DECLINED,
    ];

    public static function getDescription($code)
    {
        if (isset(self::$responseCodes[$code]))
        {
            return self::$responseCodes[$code];
        }

        return 'Authentication Failed';
    }

    public static function getMappedCode($code)
    {
        if (isset(self::$responseCodeMap[$code]))
        {
            return self::$responseCodeMap[$code];
        }

        return ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED;
    }
}
