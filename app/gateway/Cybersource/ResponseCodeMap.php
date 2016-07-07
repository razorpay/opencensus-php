<?php

namespace RZP\Gateway\Cybersource;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        101 => 'The request is missing one or more required fields.'.
                 ' See the reply fields missingField_0...N for the missing fields',
        102 => 'One or more fields in the request contains invalid data.'.
                 'See the reply fields invalidField_0...N for the invalid fields.',
        150 => 'General system failure. Wait a few minutes and resend the request',
        151 => 'The request was received, but a server time-out occurred.'.
                 ' This error does not include time-outs between the client and the server',
        152 => 'The request was received, but a service time-out occurred.',
        234 => 'A problem exists with your CyberSource merchant configuration.',
        475 => 'The customer is enrolled in Payer Authentication.'.
                 ' Authenticate the cardholder before continuing with the transaction.',
        476 => 'The customer cannot be authenticated. Review the customer’s order.',
    );

    public static $map = array(
        101 => ErrorCode::BAD_REQUEST_PAYMENT_MISSING_DATA,
        102 => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        150 => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        151 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        152 => ErrorCode::GATEWAY_ERROR_TIMED_OUT,
        234 => ErrorCode::BAD_REQUEST_ERROR,
        475 => 'Enrolled. Authenticate before continuing the transaction',
        476 => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED,
    );
}