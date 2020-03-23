<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Adjustment\Validator;

return [
    'testAddPrimaryBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  500000,
                'type'          =>  'primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
                'currency'      => 'INR',
                'description'   => 'primary balance add',
            ],
        ]
    ],

    'testCreateReserveBalanceInvalidAmount' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  6000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Reserve Balance Amount should be less than or equal to '
                        .Validator::MAX_RESERVE_BALANCE_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateReservePrimaryBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ],

    'testCreateReservePrimaryBalanceRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ],

    'testCreateReserveBankingBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_banking balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_banking balance add',
            ],
        ]
    ],

    'testCreateReserveBankingBalanceRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  5000000,
                'type'          =>  'reserve_banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_banking balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 5000000,
                'currency'      => 'INR',
                'description'   => 'reserve_banking balance add',
            ],
        ]
    ],

    'testSendYesbankLoadSuccessfulEmail' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'Account: ABC123, Bank: ICICI'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 250000,
                'currency'      => 'INR',
                'description'   => 'Account: ABC123, Bank: ICICI',
            ],
        ]
    ],

    'testSendYesbankLoadSuccessfulEmailRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'Account: ABC123, Bank: ICICI'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 250000,
                'currency'      => 'INR',
                'description'   => 'Account: ABC123, Bank: ICICI',
            ],
        ]
    ],

    'testAddReserveBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  500000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100xyz000xyz00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
                'currency'      => 'INR',
                'description'   => 'reserve_primary balance add',
            ],
        ]
    ],

    'testAddReserveBalanceInvalidMaxLimit' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  1000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100xyz000xyz00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Reserve Balance Amount should be less than or equal to '
                        .Validator::MAX_RESERVE_BALANCE_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddReserveBalanceInvalidMaxLimitRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  1000000,
                'type'          =>  'reserve_primary',
                'merchant_id'   =>  '100xyz000xyz00',
                'currency'      =>  'INR',
                'description'   =>  'reserve_primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Reserve Balance Amount should be less than or equal to '
                        .Validator::MAX_RESERVE_BALANCE_AMOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateNegativeAdjustmentWithLowBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  -5000,
                'type'          =>  'primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'loan payment reference id : some_id'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => -5000,
                'currency'      => 'INR',
                'description'   => 'loan payment reference id : some_id',
            ],
        ]
    ],

    'testCreateNegativeAdjustmentWithLowBalanceRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  -5000,
                'type'          =>  'primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'loan payment reference id : some_id'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testCreateAdjustmentFromBatchRoute' => [
        'request' => [
            'url' => '/adjustments/batch',
            'method' => 'POST',
            'content' => [
                [
                    'amount'            =>  -5000,
                    'type'              =>  ' ',
                    'merchant_id'       =>  '100abc000abc00',
                    'currency'          =>  'INR',
                    'description'       =>  'loan payment reference id : some_id',
                    'idempotency_key'   =>  'batch_100abc000abc01'
                ]

            ]
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_100abc000abc01',
                        'balance'           => 5000,
                    ],
                ],
            ],
        ]
    ],
];
