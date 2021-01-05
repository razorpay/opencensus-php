<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAmountTampering' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentFailure' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::GATEWAY_ERROR,
                    'description'       => PublicErrorDescription::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'       => ErrorCode::GATEWAY_ERROR_ISSUER_ACS_SYSTEM_FAILURE,
        ],
    ],
];
