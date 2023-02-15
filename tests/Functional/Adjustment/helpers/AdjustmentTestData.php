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

    'testAddPrimaryBalanceForMalaysianCurrency' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  500000,
                'type'          =>  'primary',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'MYR',
                'description'   =>  'primary balance add'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
                'currency'      => 'MYR',
                'description'   => 'primary balance add',
            ],
        ]
    ],

    'testAddAdjustmentOnCapitalBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  500000,
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'manual adjustment',
                'balance_id'    => '',
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => 500000,
                'currency'      => 'INR',
                'description'   => 'manual adjustment',
            ],
        ]
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
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
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

    'testTransactionCreatedWebhookAndLedgerSnsAndMailOnAdjustmentCreateForBankingBalance' => [
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

    'testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'adjustment',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookAndLedgerSnsAndMailOnNegativeAdjustmentCreateForBankingBalance' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  -250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'Account: ABC123, Bank: ICICI'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => -250000,
                'currency'      => 'INR',
                'description'   => 'Account: ABC123, Bank: ICICI',
            ],
        ]
    ],

    'testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'adjustment',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceRazorxControl' => [
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

    'testTransactionCreatedWebhookFiringAndMailOnAdjustmentCreateForBankingBalanceRazorxControlData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'adjustment',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceRazorxControl' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  -250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '100abc000abc00',
                'currency'      =>  'INR',
                'description'   =>  'Account: ABC123, Bank: ICICI'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => -250000,
                'currency'      => 'INR',
                'description'   => 'Account: ABC123, Bank: ICICI',
            ],
        ]
    ],

    'testTransactionCreatedWebhookFiringAndMailOnNegativeAdjustmentCreateForBankingBalanceRazorxControlData' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'adjustment',
                    ],
                ],
            ],
        ],
    ],

    'testLedgerSnsForPositiveAdjustmentCreationOnLiveMode' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '10000000000000',
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

    'testLedgerSnsForNegativeAdjustmentCreationOnLiveMode' => [
        'request' => [
            'url' => '/adjustments',
            'method' => 'POST',
            'content' => [
                'amount'        =>  -250000,
                'type'          =>  'banking',
                'merchant_id'   =>  '10000000000000',
                'currency'      =>  'INR',
                'description'   =>  'Account: ABC123, Bank: ICICI'
            ]
        ],
        'response' => [
            'content' => [
                'entity'        => 'adjustment',
                'amount'        => -250000,
                'currency'      => 'INR',
                'description'   => 'Account: ABC123, Bank: ICICI',
            ],
        ]
    ],
];
