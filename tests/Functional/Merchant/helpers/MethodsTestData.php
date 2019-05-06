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
                        'dicl' => 1
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
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

    'testEnableEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'emi' => true
            ],
        ],
    ],

    'testDisableEmi' => [
        'request' => [
            'url' => '/merchant/methods',
            'method' => 'put',
            'content'   => [
                'emi'  => 0,
            ]
        ],
        'response' => [
            'content' => [
                'emi' => false
            ],
        ],
    ],
];
