<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGatewayCreateAbsence' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason'
            ]
        ]
    ],
    'testCreateAbsenceWithBank' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'bank' => 'Some Bank'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason',
                'bank'   => 'Some Bank'
            ]
        ]
    ],
    'testCreateAbsenceInvalidGateway' => [
        'request' => [
            'content' => [
                'gateway' => 'UNKNOWN_GATEWAY',
                'reason'  => 'Test Reason'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Gateway [UNKNOWN_GATEWAY] does not exist',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayInvalidTo' =>[
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    //'description' => 'Gateway [UNKNOWN_GATEWAY] does not exist',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testGatewayAbsenceDelete' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'message' => 'Gateway Absence successfully deleted'
            ]
        ]
    ],
    'testGatewayCreateNullTo' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason',
            ]
        ]
    ]
];