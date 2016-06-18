<?php

namespace Gateway\Cybersource;

use EE\Error;
use EE\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        '101' => ErrorCode::GATEWAY_ERROR_PAYMENT_MISSING_DATA,
        '102' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '150' => 'System failure. Wait a few minutes and resend the request',
        '151' => ErrorCode::SERVER_ERROR_TIMED_OUT,
        '152' => ErrorCode::SERVER_ERROR_TIMED_OUT,
        '234' => 'Problem with our merchant configuration',
        '475' => 'Enrolled. Authenticate before continuing the transaction',
        '476' => 'Cannot be authenticated',
    );
}