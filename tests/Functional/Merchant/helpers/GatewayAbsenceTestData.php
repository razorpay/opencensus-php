<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGatewayCreateAbsenceNetbanking' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'comment' => 'Test Reason',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'reason_code' => 'LOW_SUCCESS_RATE'
            ]
        ]
    ],
    'testGatewayCreateAbsenceNetbankingPartial' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'partial' => true,
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'partial' => true,
                'method' => 'netbanking'
            ]
        ]
    ],
    'testCreateAbsenceNBEmptyIssuer' =>[
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testCreateAbsenceInvalidGateway' => [
        'request' => [
            'content' => [
                'gateway' => 'UNKNOWN_GATEWAY',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
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
    'testCreateAbsenceNBInvalidIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'SOME BANK',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    //'description' => 'Bank: SOME BANK is not a valid Bank Name',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testCreateAbsenceNBNonSupportedIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'ICIC',
                'method' => 'netbanking',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    //'description' => 'Bank: ICIC is not supported for Gateway: netbanking_hdfc',
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
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
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
    'testGatewayInvalidReasonCode' =>[
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'SOME CODE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'source' => 'other'
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
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'comment' => 'Test Reason'
            ]
        ]
    ],
    'testGatewayAbsenceForCard' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC'
            ]
        ]
    ],
    'testGatewayAbsenceForCardWithoutIssuer' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
            ]
        ]
    ],
    'testGatewayAbsenceForCardUnsupportedNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'DICL',
                'source' => 'other'
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
    'testGatewayAbsenceForCardInvalidNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'XYZ',
                'source' => 'other'
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
    'testGatewayAbsenceForCardInvalidCardType' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'network' => 'MC',
                'card_type' => 'xyz',
                'source' => 'other'
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

    'testGatewayAbsenceCardWithTypeIssuerNetwork' => [
        'request' => [
            'content' => [
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'issuer' => 'HDFC',
                'card_type' => 'credit',
                'network' => 'VISA',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer' => 'HDFC',
                'card_type' => 'credit',
                'network' => 'VISA'
            ]
        ]
    ],
    'testGatewayAbsenceWithWallet' => [
        'request' => [
            'content' => [
                'gateway' => 'wallet_olamoney',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'wallet',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ],
        'response' => [
            'content' => [
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'gateway' => 'wallet_olamoney'
            ]
        ]
    ],
    'testGatewayAbsenceWithInvalidWallet' => [
        'request' => [
            'content' => [
                'gateway' => 'wallet_dummywallet',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'wallet',
                'source' => 'other'
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
];