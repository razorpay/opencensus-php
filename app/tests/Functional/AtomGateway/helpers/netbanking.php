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

    'testNetBankingPaymentRefund' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'amount' => 5000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 5000,
                'currency' => 'INR',
            ]
        ],
    ],

    'testCardPayment' => [
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
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
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
    ],

    'testNBPaymentOnSharedTerminal' => [
        'request' => [
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
