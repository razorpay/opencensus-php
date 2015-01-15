<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testNetBankingPaymentAuthorize' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testNetBankingPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'amount' => 5000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'amount' => 5000,
                'status' => 'captured',
                'refund_status' => null,
            ]
        ],
    ],

    'testNBPaymentFailureAtBank' => [
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

    'testMockOnLiveMode' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'EE\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ]
];
