<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCardTimeout' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036275556',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayTimeoutException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
            'gateway_error_code'  => Hdfc\ErrorCode::RP00003,
        ],
    ],

    'testCreditCardSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001038443335',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'authorized',
            ],
        ],
    ],

    'testCreditCardAuthNotAvailable1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001038488884',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
    ],

    'testCreditCardAuthNotAvailable2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036298889',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
    ],

    'testSignatureFailure1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036853337',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => Hdfc\ErrorCode::GV00007,
        ],
    ],

    'testSignatureFailure2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001036983332',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => Hdfc\ErrorCode::GV00008,
        ],

    ],

    'testParesNotSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037461114',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESFUL,
            'gateway_error_code'  => Hdfc\ErrorCode::GV00004,
        ],
    ],

    'testDebitCardAuthNotAvailable1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037484447',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
    ],
    'testDebitCardAuthNotAvailable2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037490006',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => Hdfc\ErrorCode::FSS0001,
        ],
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

    'testJsonpPaymentReturnFields' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments/create/jsonp',
            'content' => [
                'card' => [
                    'number' => '4012001037167778'
                ],
                'callback' => 'abcdefghijkl',
                '_' => '',
            ]
        ],
        'response' => [
            'content' => [
            ]
        ],
        'jsonp' => true
    ],
];
