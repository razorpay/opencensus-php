<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testFundAccountCreationOnEsOndemandAssigning' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['es_on_demand'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'es_on_demand',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant',
//                  'id'          => 'F2hSuJpBtY0J6B',
                ],
            ]
        ]
    ],

    'testFundAccountUpdationOnBankAccountEdit' => [
        'request'  => [
            'url'     => '/bank_accounts/{id}',
            'method'  => 'put',
            'content' => [
                'account_number' => '123456785',
            ]
        ],
        'response' => [
            'content' => [
//                'id'          => 'F2hZwdzOGSZIqJ',
                'merchant_id' =>  '10000000000000',
             ]
        ]
    ],

    'testBankingHourOndemandCreation' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 20030000,
                'narration' => 'Demo Narration - optional',
                'notes'     => [
                                '1' => 'note1',
                                '2' => 'note2'
                            ],
                'expand'    => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                  => 'sod_F2hdIcWTkePDcC',
                'amount'               => '20030000',
                'total_fees'           => 472708,
                'total_tax'            => 72108,
                'total_amount_pending' => 19557292,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
                'notes'                => [
                                            '1' => 'note1',
                                            '2' => 'note2'
                                        ],
//                'created_at'         => 1582000200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
                        'user_id'        => '20000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'amount'               => '20030000',
                'total_fees'           => 472708,
                'total_tax'            => 72108,
                'total_amount_pending' => 19557292,
                'max_balance'          => '0',
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
//                'created_at'         => 1582000200,
                'payouts' => [
                    [
//                        'id'             => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
                        'user_id'        => '20000000000000',
//                        'ondemand_id'    => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testBankingHourOndemandCreationWithReversal' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 110000,
                'max_balance' => 0,
                'narration'   => 'Demo Narration - optional',
                'expand'      => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                   => 'sod_F2hdIcWTkePDcC',
                'amount'               => '110000',
                'total_fees'           => 2596,
                'total_tax'            => 396,
                'total_amount_pending' => 107404,
                'max_balance'          => '0',
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
//                'created_at'           => 1582000200,
                'payouts' => [
                    [
 //                       'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'          => 2596,
                        'tax'           => 396,
                        'mode'          => 'NEFT',
                        'amount'        => '110000',
                        'status'        => 'created',
                    ],
                ]
             ]
        ]
    ],

    'testNonBankingHourOndemandCreation' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'      => 20030000,
                'max_balance' => 0,
                'narration'   => 'Demo Narration - optional',
                'expand'      => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',,
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 708,
                        'tax'           => 108,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithMockWebhook' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',,
                'amount'                => '20030000',
                'total_fees'            => 472708,
                'total_tax'             => 72108,
                'total_amount_pending'  => 19557292,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 708,
                        'tax'           => 108,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testNonBankingHourOndemandCreationWithPartialReversal' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20110000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',,
                'amount'                => '20110000',
                'total_fees'            => 474596,
                'total_tax'             => 72396,
                'total_amount_pending'  => 19635404,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 472000,
                        'tax'           => 72000,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 2596,
                        'tax'           => 396,
                        'mode'          => 'IMPS',
                        'amount'        => 110000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testCreateOndemandForMaxBalance' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'max_balance' => 1,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                  => 'sod_F2hdIcWTkePDcC',
                'amount'               => 20030000,
                'total_fees'           => 472708,
                'total_tax'            => 72108,
                'total_amount_pending' => 19557292,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
//                'created_at'         => 1582000200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
                        'user_id'        => '20000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => 20030000,
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],

    'testCreateOndemandOnLowBalance' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 2000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount requested for the ondemand settlement exceeds the settlement balance.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
        ],
    ],

    'testCreateOndemandGreaterThanMaxLimit' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 2000000500,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount requested is more than the max limit for ondemand settlement',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONDEMAND_SETTLEMENT_AMOUNT_MAX_LIMIT_EXCEEDED,
        ],
    ],

    'testCreateOndemandForFundsOnHoldMerchant' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20000,
                'max_balance' => 1,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation is not allowed. Please contact Razorpay support for details.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],

    'testOndemandCreationForFixedRatePricing' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount' => 20030000,
                'max_balance' => 0,
                'narration' => 'Demo Narration - optional',
                'expand' => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                    => 'sod_F2iLNDZn8cODKc',,
                'amount'                => '20030000',
                'total_fees'            => 1180,
                'total_tax'             => 180,
                'total_amount_pending'  => 20028820,
                'max_balance'           => '0',
                'currency'              => 'INR',
                'status'                => 'initiated',
                'narration'             => 'Demo Narration - optional',
//                'created_at'            => 1582036200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2iLNGiioWkjZm',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 590,
                        'tax'           => 90,
                        'mode'          => 'IMPS',
                        'amount'        => 20000000,
                        'status'        => 'created',
                    ],
                    [
//                        'id'            => 'sodp_F2iLNHUZcSfTAG',
                        'merchant_id'   => '10000000000000',
                        'user_id'       => '20000000000000',
//                        'ondemand_id'   => 'F2iLNDZn8cODKc',
                        'fees'          => 590,
                        'tax'           => 90,
                        'mode'          => 'IMPS',
                        'amount'        => 30000,
                        'status'        => 'created',
                    ]
                ]
            ]
        ]
    ],

    'testOndemandFees' => [
        'request'  => [
            'url'     => '/settlement/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 400600,
                        'percentage'      => NULL,
                        'pricing_rule_id' => '1GuENK6Hl2BWGg',
                        'pricing_rule' => [
                            'percent_rate' => 200,
                            'fixed_rate'   => 0,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 72108,
                        'percentage'      =>  1800,
                        'pricing_rule_id' => NULL,
                    ],
                ],
            ]
        ]
    ],

    'testOndemandFeesForFixedRate' => [
        'request'  => [
            'url'     => '/settlement/ondemand/fees',
            'method'  => 'get',
            'content' => [
                'amount' => 20030000
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'name'            => 'settlement_ondemand',
                        'amount'          => 1000,
                        'percentage'      => NULL,
                        'pricing_rule_id' => '1GuENK6Hl2BWGg',
                        'pricing_rule' => [
                            'percent_rate' => 0,
                            'fixed_rate'   => 500,
                        ],
                    ],
                    [
                        'name'            => 'tax',
                        'amount'          => 180,
                        'percentage'      =>  1800,
                        'pricing_rule_id' => NULL,
                    ],
                ],
            ]
        ]
    ],

    'testAdjustmentAditionToOndemandXMerchant' => [
        'request'  => [
            'url'     => '/settlement/ondemand',
            'method'  => 'post',
            'content' => [
                'amount'    => 20030000,
                'narration' => 'Demo Narration - optional',
                'notes'     => [
                                '1' => 'note1',
                                '2' => 'note2'
                            ],
                'expand'    => true
            ],
        ],
        'response' => [
            'content' => [
//                'id'                  => 'sod_F2hdIcWTkePDcC',
                'amount'               => '20030000',
                'total_fees'           => 472708,
                'total_tax'            => 72108,
                'total_amount_pending' => 19557292,
                'currency'             => 'INR',
                'status'               => 'initiated',
                'narration'            => 'Demo Narration - optional',
                'notes'                => [
                                            '1' => 'note1',
                                            '2' => 'note2'
                                        ],
//                'created_at'         => 1582000200,
                'payouts' => [
                    [
//                        'id'            => 'sodp_F2hdIfWGvMza0E',
                        'merchant_id'    => '10000000000000',
                        'user_id'        => '20000000000000',
//                        'ondemand_id'   => 'F2hdIcWTkePDcC',
                        'fees'           => 472708,
                        'tax'            => 72108,
                        'mode'           => 'NEFT',
                        'amount'         => '20030000',
                        'status'         => 'created',
                    ],
                ]
            ]
        ]
    ],
];
