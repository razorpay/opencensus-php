<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testNetBankingTransactionSuccess' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'amount' => 5000,
                'status' => 'authorized',
                'refund_status' => null,
            ]
        ],
    ],

    'testNBTransactionFailureAtBank' => [
        'request' => [
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PROCESSING_DECLINED
        ],
        'success' => false,
    ],
];
