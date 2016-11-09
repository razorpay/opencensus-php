<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGatewayCreateAbsence' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason',
                'method' => 'netbanking'
            ]
        ]
    ],
    'testGatewayCreateAbsencePartial' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'partial' => true,
                'method' => 'netbanking',
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason',
                'partial' => true,
                'method' => 'netbanking'
            ]
        ]
    ],
    'testCreateAbsenceWithBank' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'issuer' => 'HDFC',
                'method' => 'netbanking'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason' => 'Test Reason',
                'issuer'   => 'HDFC'
            ]
        ]
    ],
    'testCreateAbsenceInvalidGateway' => [
        'request' => [
            'content' => [
                'gateway' => 'UNKNOWN_GATEWAY',
                'reason'  => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC'
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
    'testCreateAbsenceInvalidBank' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'issuer' => 'SOME BANK',
                'method' => 'netbanking',
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bank: SOME BANK is not a valid Bank Name',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testCreateAbsenceNonSupportedBank' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason'  => 'Test Reason',
                'issuer' => 'ICIC',
                'method' => 'netbanking',
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bank: ICIC is not supported for Gateway: netbanking_hdfc',
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
                'reason'  => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
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
                'reason'  => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC'
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