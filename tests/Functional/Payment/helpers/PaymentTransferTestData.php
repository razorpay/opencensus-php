<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testCaptureAndTransferToInvalidCustomerId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_asd',
                        'amount'   => 200
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'asd is not a valid id'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testCaptureAndTransferToUnknownCustomerId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => 'cust_3030300000cust',
                        'amount'   => 200
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],
    'testTransferToExistingCustomerWithNoExistingWallet' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'transfer'
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testTransferAndVerifyCustomerBalance' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'transfer'
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
];