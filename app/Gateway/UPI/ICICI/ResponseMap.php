<?php

namespace RZP\Gateway\UPI\ICICI;

use RZP\Error\ErrorCode;

class ResponseMap
{
    const CODES = array(
        '0'     =>  'Transaction Successful',
        '92'    =>  'Transaction Authorized',
        '5000'  =>  ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '5001'  =>  'Invalid Merchant Id',
        '5002'  =>  'Transaction Id Reused',
        '5003'  =>  'Invalid Transaction Id',
        // Defined as "Invalid Packet"
        '5004'  =>  ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '5005'  =>  'Invalid Collection Date',
        '5006'  =>  'No such transaction',
        '9999'  =>  'No response from Bank'
    );

    public static function getResponseMessage($code)
    {
        if (array_key_exists($code, self::CODES))
        {
            return CODES[$code];
        }

        // All other codes are considered this
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    /**
     * Has payment been initiated successfully at
     * the bank's end or not
     * @param  string  $status Status code from response
     * @return boolean Is payment initiated
     */
    public static function isInitiated($status)
    {
        return (intval($status) === 92);
    }

    public static function getApiErrorCode($code)
    {
        $errorCodeClass = 'RZP\Error\ErrorCode::';

        $msg = self::getResponseMessage($code);

        if (defined($errorCodeClass . $msg))
        {
            return $msg;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
