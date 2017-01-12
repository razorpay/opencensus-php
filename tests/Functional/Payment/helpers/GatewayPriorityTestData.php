<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSaveGatewayPriority' => [
        'request' => [
            'content' => [
                'hdfc'        => '50',
                'axis_migs'   => '40',
                'amex'        => '30',
                'cybersource' => '20',
                'first_data'  => '10'
            ],
            'url' => '/gateway/priority/card',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'card' => [
                    'hdfc'        => '50',
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ]
            ]
        ]
    ],
    'testSaveGatewayPriorityWithException' => [
        'request' => [
            'content' => [
                'hdfc'        => '50',
                'axis_migs'   => '40',
                'amex'        => '30',
                'cybersource' => '20',
                'first_data'  => '10'
            ],
            'url' => '/gateway/priority/card',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR
                ]
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_REDIS_EXCEPTION
        ]
    ],
    'testFetchGatewayPriority' => [
        'request' => [
            'url' => '/gateway/priority',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'card' => [
                    'hdfc'        => '50',
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ],
                'netbanking' => [
                    'ebs' => '50',
                    'billdesk' => '40'
                ]
            ]
        ]
    ],
    'testFetchGatewayPriorityWithException' => [
        'request' => [
            'url' => '/gateway/priority',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'card' => [
                    'hdfc'        => '50',
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ],
                'netbanking' => null
            ]
        ]
    ],
    'testRemoveGatewayPriority' => [
        'request' => [
            'content' => ['hdfc'],
            'url' => '/gateway/priority/card',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'card' => [
                    'axis_migs'   => '40',
                    'amex'        => '30',
                    'cybersource' => '20',
                    'first_data'  => '10'
                ]
            ]
        ]
    ],
    'testRemoveGatewayPriorityWithException' => [
        'request' => [
            'content' => ['hdfc'],
            'url' => '/gateway/priority/card',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR
                ]
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_REDIS_EXCEPTION
        ]
    ],
    'testUnsupportedPaymentMethod' => [
        'request' => [
            'content' => [
                'hdfc'        => '50',
                'axis_migs'   => '40',
                'amex'        => '30',
                'cybersource' => '20',
                'first_data'  => '10'
            ],
            'url' => '/gateway/priority/wallet',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PAYMENT_METHOD
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD
        ]
    ],
    'testInvalidGatewayForMethod' => [
        'request' => [
            'content' => [
                'hdfc'        => '50',
                'axis_migs'   => '40',
                'amex'        => '30',
                'cybersource' => '20',
                'first_data'  => '10'
            ],
            'url' => '/gateway/priority/netbanking',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_GATEWAY_FOR_METHOD
        ]
    ]
];
