<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\HdfcGateway\HdfcGatewayErrorCode;

//contain array of test cards
return [
    'cardTimeout' => [
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
                    'description' => PublicErrorDescription::GATEWAY_REQUEST_TIMEOUT,
                ],
            ],
            'status_code' => 200,
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayTimeoutException',
            'code' => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
            'gateway_error_code'  => HdfcGatewayErrorCode::RP00004,
        ],
        'type' => 'CC'
    ],

    'creditCardSuccess' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001038443335',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'auth',
            ],
        ],
        'type' => 'CC',
    ],

    'creditCardAuthNotAvailable1' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
        ],
        'type' => 'CC'
    ],
    'creditCardAuthNotAvailable2' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
        ],
        'type' => 'CC'
    ],

    'signatureFailure1' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => HdfcGatewayErrorCode::GV00007,
        ],
        'type' => 'DC'
    ],
    'signatureFailure2' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_SIGNATURE_VALIDATION_FAILED,
            'gateway_error_code'  => HdfcGatewayErrorCode::GV00008,
        ],
        'type' => 'DC'
    ],
    'debitCardSuccess1' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037141112',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'auth',
            ],
        ],
        'type' => 'DC'
    ],

    'debitCardSuccess2' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037141112',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'auth',
            ],
        ],
        'type' => 'DC'
    ],

    'debitCardSuccess3' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037167778',
                ],
            ],
        ],
        'response' => [
            'status' => 'auth',
        ],
        'type' => 'DC'
    ],
    'debitCardSuccess4' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037490014',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'auth',
            ],
        ],
        'type' => 'DC'
    ],
    'debitCardSuccess5' => [
        'request' => [
            'content' => [
                'card' => [
                    'number' => '4012001037141112',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'auth',
            ],
        ],
        'type' => 'DC'
    ],
    'paresNotSuccess' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_PARES_NOT_SUCCESFUL,
            'gateway_error_code'  => HdfcGatewayErrorCode::GV00004,
        ],
        'type' => 'DC'
    ],
    'debitCardAuthNotAvailable1' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
        ],
        'type' => 'DC'
    ],
    'debitCardAuthNotAvailable2' => [
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
        ],
        'exception' => [
            'class' => 'EE\Exception\GatewayErrorException',
            'code' => ErrorCode::GATEWAY_ERROR_AUTHENTICATION_NOT_AVAILABLE,
            'gateway_error_code'  => HdfcGatewayErrorCode::FSS0001,
        ],
        'type' => 'DC'
    ],
];
