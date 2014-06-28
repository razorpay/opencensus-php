<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

//contain array of test cards
return [
    [
    'PAN' => '4012001036275556',
    'response' => 0,
    'type' => 'CC',
    'exception' => 'EE\Exception\GatewayTimeoutException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_REQUEST_TIMEOUT,
    'gateway_error_code'  => HdfcGatewayErrorCode::RP00004,
    ],
    [
    'PAN' => '4012001038443335',
    'response' => 1,
    'type' => 'CC'
    ],
    [
    'PAN' => '4012001038488884',
    'response' => 0,
    'type' => 'CC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
    ],
    [
    'PAN' => '4012001036298889',
    'response' => 0,
    'type' => 'CC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
    ],
    [
    'PAN' => '4012001036853337',
    'response' => 0,
    'type' => 'DC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::GV00007,
    ],
    [
    'PAN' => '4012001036983332',
    'response' => 0,
    'type' => 'DC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::GV00008,
    ],
    [
    'PAN' => '4012001037141112',
    'response' => 1,
    'type' => 'DC'
    ],
    [
    'PAN' => '4005559876540',
    'response' => 1,
    'type' => 'DC'
    ],
    [
    'PAN' => '4012001037167778',
    'response' => 1,
    'type' => 'DC'
    ],
    [
    'PAN' => '4012001037461114',
    'response' => 0,
    'type' => 'DC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESFUL,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::GV00004,
    ],
    [
    'PAN' => '4012001037484447',
    'response' => 0,
    'type' => 'DC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
    ],
    [
    'PAN' => '4012001037490006',
    'response' => 0,
    'type' => 'DC',
    'exception' => 'EE\Exception\GatewayErrorException',
    'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
    'public_error_code'   => PublicErrorCode::GATEWAY_ERROR,
    'public_error_desc'   => PublicErrorDescription::GATEWAY_ERROR,
    'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
    ],
    [
    'PAN' => '4012001037490014',
    'response' => 1,
    'type' => 'DC'
    ],
    [
    'PAN' => '4012001037141112',
    'response' => 1,
    'type' => 'DC'
    ]
];
