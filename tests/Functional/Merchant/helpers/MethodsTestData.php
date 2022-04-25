<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
//                    'paytm' => false,
                ],
                'cod' => false,
            ],
        ],
    ],

    'testGetPaymentMethodsRouteWithNetbankingFalse' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [],
                'wallet' => [
//                    'paytm' => false,
                ],
            ],
        ],
    ],

    'testBulkMethodUpdate' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'debit_card' => true,
                    'credit_card' => true,
                    'netbanking' => true,
                    'card_networks' => [
                        'DICL' => '0',
                        'MAES' => '1',
                        'RUPAY'=> '0'
                    ],
                    'cod' => true,
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateCreditEmiEnable' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'credit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success' => 1,
            ],
        ],
    ],

    'testBulkMethodUpdateCreditEmiEnableInvalidCategory' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'credit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success'=> 0,
                'failed'=> 1,
                'failedIds'=> [
                    '10000000000000'
                ]
            ],
        ],
    ],

    'testBulkMethodUpdateDebitEmiEnableInvalidCategory' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000'],
                'methods' => [
                    'emi' => [
                        'debit' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total' => 1,
                'success'=> 0,
                'failed'=> 1,
                'failedIds'=> [
                    '10000000000000'
                ]
            ],
        ],
    ],

    'testBulkMethodUpdateEnableBanks' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'enabled_banks' => ['HDFC']
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateDisableBanks' => [
        'request' => [
            'url' => '/methods/bulkupdate',
            'method' => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods' => [
                    'disabled_banks' => ['ICIC']
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBulkMethodUpdateInvalidMerchantId' => [
        'request'  => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'merchants' => ['10000000000000', '1000000000000x'],
                'methods'   => [
                    'debit_card'  => true,
                    'credit_card' => true,
                    'netbanking'  => true
                ],
            ],
        ],
        'response' => [
            'content' => [
                'total'     => 2,
                'failed'    => 1,
                'success'   => 1,
                'failedIds' => ['1000000000000x']
            ],
        ],
    ],

    'testBulkMethodUpdateInvalidMethodsInput' => [
        'request'   => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'merchants' => ['10000000000000', '10000000000000'],
                'methods'   => [
                    'debit_card' => true,
                    'credit_card',
                    'netbanking' => true
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => '0 is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testBulkMethodUpdateMissingInput' => [
        'request'   => [
            'url'     => '/methods/bulkupdate',
            'method'  => 'put',
            'content' => [
                'methods' => [
                    'debit_card'  => true,
                    'credit_card' => true,
                    'netbanking'  => true
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchants field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRecurringCards' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                    'mobikwik' => true,
                ],
                'recurring' => [
                    'card' => [
                        'credit' => [
                            'MasterCard',
                            'Visa',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRecurringNetbankingOnChargeAtWill' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                ],
                'recurring' => [
                    'card' => [
                        'credit' => [
                            'MasterCard',
                            'Visa',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchMethods' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => false
            ],
        ],
    ],

    'testOnecardMerchantMethods' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => true,
                'emi_plans' => [
                    'onecard' => [
                        'min_amount' =>300000,
                        'plans' => [
                            '3' => 12,
                        ],
                    ],
                ],
                'emi_options' => [
                    'onecard' => [
                        [
                            'duration'   => 3,
                            'interest'   => 12,
                            'min_amount' => 300000,
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFetchGooglePayForCardsMethod' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchGooglePayMethod' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testEnableCreditEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                ]
            ],
        ],
    ],

    'testEnableCreditEmiInvalidCategory' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                ],
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'EMI cannot be enabled for this MCC: 5944',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnableDebitEmiInvalidCategory' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit' => '1',
                ],
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'EMI cannot be enabled for this MCC: 5944',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEnableCardnetworksAmexForBlacklistedMccs' => [
            'request' => [
                'url' => '/merchants/10000000000000/methods',
                'method' => 'put',
                'content'   => [
                    'card_networks' => [
                        'AMEX' => '1',
                    ]
                ]
            ],
            'response'  => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'AMEX card network cannot be enabled for this MCC: 4411',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnablePaylaterForBlacklistedMccs' => [
            'request' => [
                'url' => '/merchants/10000000000000/methods',
                'method' => 'put',
                'content'   => [
                        'paylater' => '1',
                ]
            ],
            'response'  => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Paylater cannot be enabled for this MCC: 5960',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
    ],

    'testEnableDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'debit',
                ]
            ],
        ],
    ],

    'testEnableCreditAndDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '1',
                    'debit' => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                    'debit',
                ],
            ],
        ],
    ],

    'testDisableEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '0',
                    'debit'  => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [],
            ],
        ],
    ],

    'testDisableCreditEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'credit' => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'debit',
                ],
            ],
        ],
    ],

    'testDisableDebitEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => [
                    'debit'  => '0',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'emi' => [
                    'credit',
                ],
            ],
        ],
    ],

    'testBulkEnableHdfcDebitEmiProvider' => [
        'request' => [
            'url' => '/methods/hdfc_debit_emi',
            'method' => 'post',
            'content'   => [
                'count'  => 10,
            ]
        ],
        'response' => [
            'content' => [
                'count' => "10",
                'success' => 2,
                'failure'=> 0,
                'total' => 2
            ],
        ],
    ],

    'testMerchantPaybackInEmiOptions' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'emi' => true
            ],
        ],
    ],

    'testEnableRazorpaywallet' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'PUT',
            'content' => [
                'razorpaywallet' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'razorpaywallet' => true,
            ],
        ],
    ],

    'testEnableOfflineMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content'   => [
                'offline' => 1
            ]
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
        ],
    ],
];
