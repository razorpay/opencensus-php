<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                        [
                            'payment_method'    => 'netbanking',
                            'percent_rate'      => 1000,
                            'payment_network'   => 'SIBL',
                            'type'              => 'pricing',
                        ],
                        [
                            'feature'           => 'fund_account_validation',
                            'payment_method'    => 'bank_account',
                            'fixed_rate'        => 1000,
                            'type'              => 'pricing',
                        ],
                        [
                            'payment_method'        => 'card',
                            'payment_method_type'   => 'credit',
                            'payment_network'       => 'DICL',
                            'payment_issuer'        => 'HDFC',
                            'percent_rate'          => 1000,
                            'amount_range_active'   => false,
                            'amount_range_min'      => null,
                            'amount_range_max'      => null,
                            'min_fee'               => 10,
                            'max_fee'               => 10000,
                        ],
                        [
                            'payment_method'        => 'wallet',
                            'payment_network'       => 'paytm',
                            'percent_rate'          => 1000
                        ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'TestUploadPlan2',
                'entity'    => 'pricing',
                'count'     => 4,
                'rules'     => [
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'wallet',
                        'payment_method_type'   => null,
                        'payment_network'       => 'paytm',
                        'payment_issuer'        => null,
                        'percent_rate'          => 1000,
                        'type'                  => 'pricing',
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'netbanking',
                        'percent_rate'          => 1000,
                        'payment_network'       => 'SIBL',
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        'min_fee'               => 10,
                        'max_fee'               => 10000,
                    ],
                    [
                        'plan_name'             => 'TestUploadPlan2',
                        'feature'               => 'fund_account_validation',
                        'payment_method'        => 'bank_account',
                        'fixed_rate'            => 1000,
                    ],
                ],
            ],
        ],
    ],

    'testStringifiedPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestStringifiedPlan2',
                'rules'     => "[{\r\n\"payment_method\": \"wallet\",\r\n\"payment_network\": \"paytm\",\r\n\"percent_rate\": 1000\r\n }]",
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'TestStringifiedPlan2',
                'entity'    => 'pricing',
                'count'     => 1,
                'rules'     => [
                    [
                        'plan_name'             => 'TestStringifiedPlan2',
                        'payment_method'        => 'wallet',
                        'payment_method_type'   => null,
                        'payment_network'       => 'paytm',
                        'payment_issuer'        => null,
                        'percent_rate'          => 1000,
                        'type'                  => 'pricing',
                    ]
                ],
            ],
        ],
    ],

    'testEmptyPlanWithNoRules' => [
        'request' => [
            'content' => [
                'plan_name' => 'testEmptyPlanWithNoRules'
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAddBulkPlanRules' => [
        'request'  => [
            'content' => [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'netbanking',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '0'
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_aZtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '1'
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfa',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '1'
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'upi',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfn',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esondemand',
                    'payment_method'        => 'netbanking',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'emandate',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdqaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcddef',
                    'amount_range_active'   => true,
                    'amount_range_min'      => 900,
                    'amount_range_max'      => 1000,
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdcde',
                    'amount_range_active'   => true,
                    'amount_range_min'      => 1000,
                    'amount_range_max'      => 2000,
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdbcd',
                    'amount_range_active'   => true,
                    'amount_range_min'      => 900,
                    'amount_range_max'      => 1100,
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdabc',
                    'amount_range_active'   => false,
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> 'invalidsubtype',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXdcdcdr',
                    'amount_range_active'   => true,
                    'amount_range_min'      => 900,
                    'amount_range_max'      => 19000,
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> 'business',
                    'payment_network'       => 'BAJAJ',
                    'percent_rate'          => '11',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '400',
                    'amount_range_max'      => '600',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> 'consumer',
                    'payment_network'       => 'BAJAJ',
                    'percent_rate'          => '12',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfe',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '400',
                    'amount_range_max'      => '600',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> 'invalidsubtype',
                    'payment_network'       => 'BAJAJ',
                    'percent_rate'          => '12',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfh',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '400',
                    'amount_range_max'      => '600',
                    'update'                => ''
                ]
            ],
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
        ],
        'response' => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 16,
                'items'     => [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM'
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The new rule matches with an active existing rule',
                            'code'          => 'BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_aZtFGiJXmcdLfM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The new rule matches with an active existing rule',
                            'code'          => 'BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLaM',
                        'success'           => true
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLfa',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The new rule is same as the previous rule',
                            'code'          => 'BAD_REQUEST_SAME_PRICING_RULE_ALREADY_EXISTS'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfn',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The payment method type field may be sent only ' .
                                'when payment method is card/emi/emandate/fund_transfer/nach',
                            'code'          => 'BAD_REQUEST_VALIDATION_FAILURE'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXdcdLaM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'Not a valid Pricing feature: esondemand',
                            'code'          => 'SERVER_ERROR_INVALID_ARGUMENT'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXdcdqaM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'Not a valid Payment method for early settlement: emandate',
                            'code'          => 'BAD_REQUEST_VALIDATION_FAILURE'
                        ]
                    ],
                    [
                        'plan_id'           =>  "1ycviEdCgurrFI",
                        'success'           =>  true,
                        'idempotency_key'   =>  "batch_DZtFGiJXdcddef"
                    ],
                    [
                        'plan_id'           =>  "1ycviEdCgurrFI",
                        'success'           =>  true,
                        'idempotency_key'   =>  "batch_DZtFGiJXdcdcde"
                    ],
                    [
                        'idempotency_key'   =>  "batch_DZtFGiJXdcdbcd",
                        'success'           =>  false,
                        'error'  =>  [
                            'description'   => "Pricing rule amount range collides with another existing rule's amount range.",
                            'code'          => "BAD_REQUEST_VALIDATION_FAILURE"
                        ]
                    ],
                    [
                        'idempotency_key'   =>  "batch_DZtFGiJXdcdabc",
                        'success'           =>  false,
                        'error' =>  [
                            'description'   =>  "Pricing rule amount range collides with another existing rule's amount range.",
                            'code'          =>  "BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP"
                        ]
                    ],
                    [
                        'idempotency_key'   =>  "batch_DZtFGiJXdcdcdr",
                        'success'           =>  false,
                        'error' =>  [
                            'description'   =>  "The payment method subtype field may be sent only when payment method is card",
                            'code'          =>  "BAD_REQUEST_VALIDATION_FAILURE"
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLfM',
                        'success'           => true
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLfe',
                        'success'           => true
                    ],
                    [
                        'idempotency_key'   =>  "batch_DxtFGiJXmcdLfh",
                        'success'           =>  false,
                        'error' =>  [
                            'description'   =>  "Not a valid sub_type: invalidsubtype",
                            'code'          =>  "BAD_REQUEST_VALIDATION_FAILURE"
                        ]
                    ],
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testCalculateBuyPricingCost' => [
        'request'  => [
            'url'     => '/buy_pricing/terminal_cost',
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'terminals' => [
                    [
                        'terminal_id' => 'fourteenDigits',
                        'gateway'     => 'hdfc',
                        'cost'        => 50,
                        'success'     => true,
                    ],
                    [
                        'terminal_id' => 'fourteenDigits',
                        'gateway'     => 'fulcrum',
                        'cost'        => 0,
                        'success'     => false,
                    ]
                ],
            ],
        ],
    ],

    'testAddBulkBuyPlanRules' => [
        'request'  => [
            'content' => [
                [
                    'plan_name'             => 'testPlan',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> 'business',
                    'gateway'               => 'hdfc',
                    'payment_issuer'        => 'hdfc',
                    'payment_network'       => 'Visa,MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'amount_range_min'      => '500',
                    'amount_range_max'      => '0',
                ],
                [
                    'plan_name'             => 'testPlan',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> 'business',
                    'gateway'               => 'hdfc',
                    'payment_issuer'        => 'hdfc',
                    'payment_network'       => 'Visa,MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_min'      => '0',
                    'amount_range_max'      => '500',
                ],
                [
                    'plan_name'             => 'testPlan',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'debit',
                    'payment_method_subtype'=> 'business',
                    'gateway'               => 'hdfc',
                    'payment_issuer'        => 'hdfc',
                    'payment_network'       => 'Visa,MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_aZtFGiJXmcdLfM',
                    'amount_range_min'      => '0',
                    'amount_range_max'      => '500',
                ],
                [
                    'plan_name'             => 'testPlan',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'debit',
                    'payment_method_subtype'=> 'business',
                    'gateway'               => 'hdfc',
                    'payment_issuer'        => 'hdfc',
                    'payment_network'       => 'Visa,MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLaM',
                    'amount_range_min'      => '500',
                    'amount_range_max'      => '700',
                ],
                [
                    'plan_name'             => 'testPlan',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'debit',
                    'payment_method_subtype'=> 'business',
                    'gateway'               => 'hdfc',
                    'payment_issuer'        => 'hdfc',
                    'payment_network'       => 'Visa,MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfa',
                    'amount_range_min'      => '700',
                    'amount_range_max'      => '0',
                ],
                [
                    'plan_name'             => 'testName2',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'prepaid',
                    'payment_method_subtype'=> '',
                    'receiver_type'         => '',
                    'gateway'               => 'hitachi',
                    'payment_issuer'        => '',
                    'payment_network'       => 'MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'emi_duration'          => '',
                    'amount_range_min'      => '0',
                    'amount_range_max'      => '',
                    'fixed_rate'            => '5',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaQ',
                ],
                [
                    'plan_name'             => 'testName3',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'prepaid',
                    'payment_method_subtype'=> '',
                    'receiver_type'         => '',
                    'gateway'               => 'hitachi',
                    'payment_issuer'        => '',
                    'payment_network'       => 'MC',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'emi_duration'          => '',
                    'amount_range_min'      => '0',
                    'amount_range_max'      => '',
                    'fixed_rate'            => '5',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaZ',
                ],
                [
                    'plan_name'             => 'testName4',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'prepaid',
                    'payment_method_subtype'=> '',
                    'receiver_type'         => '',
                    'gateway'               => 'hitachi',
                    'payment_issuer'        => '',
                    'payment_network'       => 'MasterCard',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'emi_duration'          => '',
                    'amount_range_min'      => '0',
                    'amount_range_max'      => '',
                    'fixed_rate'            => '5',
                    'min_fee'               => '0',
                    'max_fee'               => '10',
                    'procurer'              => 'razorpay',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaX',
                ],
            ],
            'url'       => '/buy_pricing/rules/bulk',
            'method'    => 'POST',
        ],
        'response' => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 8,
                'items'     => [
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_aZtFGiJXmcdLfM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLaM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLfa',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaQ',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaZ',
                        'success'           => false,
                        'error'             => [
                            'description'   => 'Payment Network sent is wrong. Please check the case (lower/upper) of the payment network you are sending.'.
                            ' If you are sending UNKNOWN explicitly then its not a valid network',
                            'code'  => 'BAD_REQUEST_VALIDATION_FAILURE'
                        ]
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaX',
                        'success'           => true,
                    ],
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testValidateBulkBuyPlanRulesBatchSuccess' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'buy_pricing_rule',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
            ],
        ],
    ],

    'testValidateBulkBuyPlanRulesBatchFailure' => [
        'request'  => [
            'url'     => '/admin/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'buy_pricing_rule',
            ],
        ],
        'response' => [
            'content' => [],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testAddBulkPlanRulesReplicatePlan' => [
        'request'  => [
            'content' => [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'netbanking',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'upi',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DxtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '1'
                ]
            ],
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
        ],
        'response' => [
            'content'     => [
                'entity'    => 'collection',
                'count'     => 4,
                'items'=> [
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM'
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM'
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The new rule matches with an active existing rule',
                            'code'          => 'BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED'
                        ]
                    ],
                    [
                        'success'           => true,
                        'idempotency_key'   => 'batch_DxtFGiJXmcdLfM'
                    ]
                ]
            ],
            'status_code'   => 200,
        ]
    ],

    'testAddBulkPlanRulesNoReplication' => [
        'request'  => [
            'content' => [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '1000',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '1'
                ]
            ],
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
        ],
        'response' => [
            'content'     => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'=> [
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM',
                        'success'           => false,
                        'error' => [
                            'description'   => 'The new rule is same as the previous rule',
                            'code'          => 'BAD_REQUEST_SAME_PRICING_RULE_ALREADY_EXISTS'
                        ]
                    ],
                ]
            ],
            'status_code'   => 200,
        ]
    ],

    'testAddBulkEsPlanRulesForCustomerFeeBearerMerchant' => [
        'request'  => [
            'content' => [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'netbanking',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => ''
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'card',
                    'payment_method_type'   => 'credit',
                    'payment_method_subtype'=> '',
                    'payment_network'       => 'DICL',
                    'percent_rate'          => '10',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_active'   => '',
                    'amount_range_min'      => '',
                    'amount_range_max'      => '',
                    'update'                => '1'
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'esautomatic',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_method_subtype'=> '',
                    'payment_network'       => '',
                    'percent_rate'          => '',
                    'international'         => '0',
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLfM',
                    'amount_range_active'   => true,
                    'amount_range_min'      => 900,
                    'amount_range_max'      => 1000,
                    'update'                => ''
                ],
            ],
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
        ],
        'response' => [
            'content'     => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'=> [
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => true
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLfM',
                        'success'           => true
                    ],
                ]
            ],
            'status_code'   => 200,
        ]
    ],

    'testBulkPricingPlanOfMultipleTypes' => [
        'request'  => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                    [
                        'payment_method'    => 'netbanking',
                        'percent_rate'      => 1000,
                        'payment_network'   => 'SIBL',
                    ],
                    [
                        'feature'           => 'fund_account_validation',
                        'payment_method'    => 'bank_account',
                        'fixed_rate'        => 1000,
                        'type'              => 'commission',
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
        ],
    ],

    'testEmptyBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [],
            ],
            'url'           => '/pricing',
            'method'        => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testDuplicateBulkPricingPlan' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestUploadPlan2',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'percent_rate'          => 1000,
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'international'         => '0',
                        'amount_range_active'   => '0',
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                    ],
                    [
                        'payment_method'        => 'card',
                        'percent_rate'          => 1000,
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'international'         => '0',
                        'amount_range_active'   => '0',
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ]
    ],
    'testCreatePricingPlanWithMinAndMaxFee' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                        [
                            'payment_method'        => 'card',
                            'payment_method_type'   => 'credit',
                            'payment_network'       => 'DICL',
                            'payment_issuer'        => 'HDFC',
                            'percent_rate'          => 1000,
                            'international'         => '0',
                            'amount_range_active'   => '0',
                            'amount_range_min'      => null,
                            'amount_range_max'      => null,
                            'min_fee'               => null,
                            'max_fee'               => null,
                        ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'international'         => false,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        //Defaults to 0
                        'min_fee'               => 0,
                        'max_fee'               => null,
                    ],
                ],
            ],
        ],
    ],
    'testCreatePricingPlanWithInvalidMinAndMaxFee' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'amount_range_active'   => false,
                        'amount_range_min'      => null,
                        'amount_range_max'      => null,
                        'min_fee'               => 10000,
                        'max_fee'               => 10,
                    ],
                ],
            ],
            'url' => '/pricing/',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Min fee chargeable for a rule needs to be greater than Max fee',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePricingPlanByRZPAdmin' => [
        'request'  => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                        'min_fee'             => null,
                        'max_fee'             => null,
                    ],
                ],
            ],
            'url'     => '/pricing/',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'name'   => 'TestPlan1',
                'entity' => 'pricing',
                'count'  => 1,
                'rules'  => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                        //Defaults to 0
                        'min_fee'             => 0,
                        'max_fee'             => null,
                    ],
                ],
            ],
        ],
    ],

    'testCreateBuyPricingPlan' => [
        'request'  => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => ['DICL'],
                        'gateway'             => 'hdfc',
                        'payment_issuer'      => ['hdfc'],
                        'percent_rate'        => 1000,
                        'amount_range_active' => 1,
                        'amount_range_min'    => 0,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
            'url'     => '/buy_pricing/',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'name'   => 'TestPlan1',
                'entity' => 'pricing',
                'count'  => 1,
                'rules'  => [
                    [
                        'type'                => 'buy_pricing',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'gateway'             => 'hdfc',
                        'payment_issuer'      => 'hdfc',
                        'percent_rate'        => 1000,
                        'international'       => false,
                        'amount_range_active' => true,
                        'amount_range_min'    => 0,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testAddPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'card',
                'payment_method_type'  => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => 0,
                'amount_range_active' => '0',
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'payment_method' => 'card',
                'payment_method_type' => 'credit',
                'payment_network' => 'MAES',
                'payment_issuer' => 'HDFC',
                'percent_rate' => 1000,
                'international' => false,
                'amount_range_active' => false,
                'amount_range_min' => null,
                'amount_range_max' => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleByRZPAdmin' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddBuyPricingPlanRule' => [
        'request'  => [
            'content' => [
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => ['MAES'],
                        'gateway'             => 'hdfc',
                        'payment_issuer'      => ['hdfc'],
                        'percent_rate'        => 1000,
                        'international'       => '0',
                        'amount_range_active' => '1',
                        'amount_range_min'    => 0,
                        'amount_range_max'    => 500,
                    ],
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => ['MAES'],
                        'gateway'             => 'hdfc',
                        'payment_issuer'      => ['hdfc'],
                        'percent_rate'        => 1000,
                        'international'       => '0',
                        'amount_range_active' => '1',
                        'amount_range_min'    => 500,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'           => 'testPlan',
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'MAES',
                    'gateway'             => 'hdfc',
                    'payment_issuer'      => 'hdfc',
                    'percent_rate'        => 1000,
                    'international'       => false,
                    'amount_range_active' => true,
                    'amount_range_min'    => 0,
                    'amount_range_max'    => 500,
                ],
                [
                    'plan_name'           => 'testPlan',
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'MAES',
                    'gateway'             => 'hdfc',
                    'payment_issuer'      => 'hdfc',
                    'percent_rate'        => 1000,
                    'international'       => false,
                    'amount_range_active' => true,
                    'amount_range_min'    => 500,
                    'amount_range_max'    => null,
                ],
            ],
        ],
    ],

    'testAddPricingPlanRuleFeeBearerValidation' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          =>'customer',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleFeeBearerValidationFailure' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'fee_bearer'          =>'customer',
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Unable to add rule to plan TestPlan1. Rule has fee_bearer customer. A merchant on this plan has fee_bearer platform',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAddPricingPlanRuleBySBIAdmin' => [
        'request'   => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testOrgIdPricing' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithDebitPinFeature' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'auth_type'           => 'pin',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'auth_type'           => 'pin',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleForCoDMethod' => [
        'request' => [
            'content' => [
                'payment_method'      => 'cod',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'cod',
            ],
        ],
    ],

    'testAddPricingPlanRuleForOfflineMethod' => [
        'request' => [
            'content' => [
                'payment_method'      => 'offline',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'offline',
            ],
        ],
    ],

    'testAddPricingPlanRuleForOfflineMethodWithNetworkSpecified' => [
        'request' => [
            'content' => [
                'payment_method'      => 'offline',
                'payment_network'     => 'DICL'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Offline method cannot have a network associated with it',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleWithReceiver' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithProcurer' => [
        'request' => [
            'content' => [
                'procurer'            => 'razorpay',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'procurer'            => 'razorpay',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithProcurerMerchantAndMethodNull' => [
        'request' => [
            'content' => [
                'procurer'            => 'merchant',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'procurer'            => 'merchant',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithProcurerRazorpayAndMethodNull' => [
        'request' => [
            'content' => [
                'procurer'            => 'razorpay',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method field is required for feature payment if procurer is not merchant.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleWithFeatureOptimizerAndMethodNull' => [
        'request' => [
            'content' => [
                'procurer'            => null,
                'feature'             => 'optimizer',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'feature'             => 'optimizer',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleWithFeatureOptimizerAndProcurerNotNull' => [
        'request' => [
            'content' => [
                'procurer'            => 'razorpay',
                'feature'             => 'optimizer',
                'payment_method'      => null,
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'procurer is not required when feature is optimizer.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDuplicateReceiverRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'receiver_type'       => 'qr_code',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ]
    ],

    'testAddPricingPlanBankTransferRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'max_fee'             => 1000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'max_fee'             => 1000,
                'type'                => 'pricing',
            ],
        ],
    ],
    'testUpdatePricingSubType' => [
        'request' => [
            'url'=> '/merchants/pricing/bulk/update',
            'content' => [
                'count'      => 10,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success'           => 1,
                'total'      => 1,
                'failed'        => 0,

            ],
        ],
    ],

    'testUpdatePricingSubTypeWithCorporateRuleAlreadyPresent' => [
        'request' => [
            'url'=> '/merchants/pricing/bulk/update',
            'content' => [
                'count'      => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success'           => 0,
                'total'      => 0,
                'failed'        => 0,

            ],
        ],
    ],

    'testAddPricingPlanBankTransferRuleWithoutMaxFee' => [
        'request' => [
            'content' => [
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => 100,
                'fixed_rate'          => 1000,
                'max_fee'             => '0',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bank transfer pricing should include percent rate and max fee',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanBankTransferRuleWithoutPercentRate' => [
        'request' => [
            'content' => [
                'payment_method'      => 'bank_transfer',
                'percent_rate'        => '',
                'max_fee'             => 1000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bank transfer pricing should include percent rate and max fee',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanNBRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'BARB_R',
                'payment_issuer'      => null,
                'percent_rate'        => 1000,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'pricing',
            ],
        ],
    ],

    'testAddingPricingPlanNBRuleDifferentTypes' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_PLAN_CANNOT_HAVE_MULTIPLE_TYPES,
        ],
    ],

    'testAddCommissionPlanNBRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'BARB_R',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => 'BARB_R',
                'payment_issuer'      => null,
                'percent_rate'        => 1000,
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'type'                => 'commission',
            ],
        ],
    ],

    'testAddPricingPlanNBRuleWithReceiver' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'percent_rate'        => 1000,
                'payment_network'     => 'SIBL',
                'amount_range_active' => true,
                'amount_range_min'    => 0,
                'amount_range_max'    => 100000,
                'receiver_type'       => 'qr_code',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The receiver type field may be sent only when payment method is card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanNBNoNetworkRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'netbanking',
                'percent_rate'   => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1000
            ],
        ],
    ],


    'testAddPricingPlanWalletRule' => [
        'request' => [
            'content' => [
                'payment_method'  => 'wallet',
                'payment_network' => 'paytm',
                'percent_rate'    => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => 'paytm',
                'payment_issuer'      => null,
                'percent_rate'        => 1000
            ],
        ],
    ],

    'testAddPricingPlanCredRule' => [
        'request' => [
            'content' => [
                'payment_method'  => 'cred',
                'percent_rate'    => 1500
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'cred',
                'percent_rate'        => 1500
            ],
        ],
    ],

    'testAddPricingPlanEmi' => [
        'request' => [
            'content' => [
                'payment_method' => 'emi',
                'percent_rate'   => 1500,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'      => 'TestPlan1',
                'payment_method' => 'emi',
                'percent_rate'   => 1500,
            ],
        ],
    ],

    'testAddPricingPlanEmiDebit' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emi',
                'payment_method_type' => 'debit',
                'percent_rate'        => 1500,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emi',
                'payment_method_type' => 'debit',
                'percent_rate'        => 1500,
            ],
        ],
    ],

    'testAddPricingPlanFundAccountValidationRule' => [
        'request' => [
            'content' => [
                'feature'        => 'fund_account_validation',
                'payment_method' => 'bank_account',
                'fixed_rate'     => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'feature'             => 'fund_account_validation',
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'bank_account',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanEmandateRule' => [
        'request' => [
            'content' => [
                'payment_method' => 'emandate',
                'fixed_rate'     => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanEmandateRegistrationRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_issuer'      => 'initial',
                'fixed_rate'          => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'netbanking',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],

    'testAddPricingPlanNachRegistrationRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'nach',
                'payment_method_type' => 'physical',
                'payment_issuer'      => 'initial',
                'fixed_rate'          => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'nach',
                'payment_method_type' => 'physical',
                'payment_network'     => null,
                'payment_issuer'      => 'initial',
                'percent_rate'        => 0,
                'fixed_rate'          => 1000
            ],
        ],
    ],
    'testAddPricingPlanRuleForAffordabilityWidget' => [
        'request' => [
            'content' => [
                'product'             => 'primary',
                'feature'             => 'affordability_widget',
                'payment_method'      => null,
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'fixed_rate'          => 100000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan1',
                'product'      => 'primary',
                'feature'      => 'affordability_widget',
                'fixed_rate'   => 100000
            ],
        ],
    ],
    'testAddPricingRuleForWidgetMaxFixedRate' => [
        'request' => [
            'content' => [
                'product'             => 'primary',
                'feature'             => 'affordability_widget',
                'payment_method'      => null,
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'fixed_rate'          => 3000000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The fixed rate may not be greater than 2500000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddPricingPlanEmandateDebitAadhaarRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_issuer'      => 'auto',
                'fixed_rate'          => 2000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000
            ],
        ],
    ],

    'testAddPricingPlanNachDebitRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'nach',
                'payment_method_type' => 'physical',
                'payment_issuer'      => 'auto',
                'fixed_rate'          => 2000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'nach',
                'payment_method_type' => 'physical',
                'payment_network'     => null,
                'payment_issuer'      => 'auto',
                'percent_rate'        => 0,
                'fixed_rate'          => 2000
            ],
        ],
    ],

    'testAddPricingPlanEmandatePercentageRateRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'payment_method_type' => 'aadhaar',
                'payment_issuer'      => 'auto',
                'percent_rate'        => 1000
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Percentage rate pricing is not allowed for emandate',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanEmandatePercentageRateRuleForSubscription' => [
        'request' => [
            'content' => [
                'payment_method'      => 'emandate',
                'product'             => 'primary',
                'feature'             => 'payment',
                'payment_method_type' => 'netbanking',
                'payment_issuer'      => 'auto',
                'percent_rate'        => 250,
                'fixed_rate'          => 0
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'emandate',
                'payment_issuer'      => 'auto',
                'percent_rate'        => 250,
                'fixed_rate'          => 0
            ],
            'status_code' => 200,
        ],
    ],

    'testAddDuplicatePricingPlanRule' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'DICL',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleWithMaxFee' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'min_fee'             => null,
                'max_fee'             => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'min_fee'             => 0,
                'max_fee'             => null,
            ],
        ],
    ],

    'testAddPricingRuleWithFeeBearerMismatch' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'min_fee'             => null,
                'max_fee'             => null,
                'fee_bearer'          => 'platform'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error'   => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'fee_bearer'
                ]
            ],
            'status_code'   => 400
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAddPricingRuleEarlySalary' => [
        'request' => [
            'content' => [
                'payment_method'      => 'cardless_emi',
                'payment_issuer'      => 'earlysalary',
                'percent_rate'        => 1000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'cardless_emi',
                'payment_issuer'      => 'earlysalary',
                'percent_rate'        => 1000,
            ],
        ],
    ],

    'testAddPricingRuleWalnut369' => [
        'request' => [
            'content' => [
                'payment_method'      => 'cardless_emi',
                'payment_issuer'      => 'walnut369',
                'percent_rate'        => 1000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'cardless_emi',
                'payment_issuer'      => 'walnut369',
                'percent_rate'        => 1000,
            ],
        ],
    ],


    'testUpdatePricingPlanRule' => [
        'request' => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'procurer'     => 'merchant'
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'procurer'     => 'merchant'
            ],
        ],
    ],

    'testUpdateBuyPricingPlanRule' => [
        'request' => [
            'content' => [
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'testPlan',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
        ],
    ],

    'testUpdatePricingPlanRuleEmptyProcurer' => [
        'request' => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'procurer'     => ''

            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error'   => [
                    'code'  => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Procurer Value'
                ]
            ],
            'status_code'   => 400
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testUpdateCommissionRule' => [
        'request' => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
            ],
        ],
    ],

    'testUpdatePricingPlanFeeBearerMismatch' => [
        'request' => [
            'content' => [
                'percent_rate'  => 20,
                'fee_bearer'    => 'platform'
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testUpdatePricingPlanRuleByRZPAdmin' => [
        'request'  => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'plan_name'    => 'TestPlan2',
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'min_fee'      => 101,
                'max_fee'      => 10000,
            ],
        ],
    ],

    'testUpdatePricingPlanRuleChannelFailure' => [
        'request'  => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
                'channel'      => 'rbl',
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreateCommissionPlanBySBIOrg' => [
        'request' => [
            'content' => [
                'plan_name' => 'TestPlan1',
                'rules'     => [
                    [
                        'payment_method'        => 'card',
                        'payment_method_type'   => 'credit',
                        'payment_network'       => 'DICL',
                        'payment_issuer'        => 'HDFC',
                        'percent_rate'          => 1000,
                        'international'         => '0',
                        'type'                  => 'commission',
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_TYPE_COMMISSION_INVALID_FOR_NON_RZP_ORG,
        ],
    ],

    'testUpdatePricingPlanRuleBySBIAdmin' => [
        'request'   => [
            'content' => [
                'min_fee'      => 101,
                'max_fee'      => 10000,
                'percent_rate' => 450,
                'fixed_rate'   => 0,
            ],
            'method'  => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testGetPricingPlan' => [
        'response' => [
            'content' => [
                'name' => 'TestPlan2',
                'entity' => 'pricing',
                'count' => 4,
                'rules' => [
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'MC',
                        'payment_issuer'      => 'AXIS',
                        'percent_rate'        => 0,
                        'fixed_rate'          => 3000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'MAES',
                        'payment_issuer'      => 'PUNB',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'ICIC',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'gateway'             => null,
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'SBIN',
                        'percent_rate'        => 275,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetPricingPlanByRZPAdmin' => [
        'response' => [
            'content' => [
                'name'   => 'TestPlan2',
                'entity' => 'pricing',
                'count'  => 4,
                'rules'  => [
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'MC',
                        'payment_issuer'      => 'AXIS',
                        'percent_rate'        => 0,
                        'fixed_rate'          => 3000,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'MAES',
                        'payment_issuer'      => 'PUNB',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'ICIC',
                        'percent_rate'        => 250,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                    [
                        'plan_name'           => 'TestPlan2',
                        'gateway'             => null,
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'SBIN',
                        'percent_rate'        => 275,
                        'fixed_rate'          => 0,
                        'international'       => false,
                        'amount_range_active' => false,
                        'amount_range_min'    => null,
                        'amount_range_max'    => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetBuyPricingPlan' => [
        'response' => [
            'content' => [
                'name'   => 'testPlan',
                'entity' => 'pricing',
                'count'  => 10,
            ],
        ],
    ],

    'testGetPricingPlanBySBIAdmin' => [
        'response' => [
            'content' => []
        ],
    ],

    'testGetPricingPlans' => [
        'request' => [
            'url' => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count' => 12,
                'entity' => 'collection',
                'items' => [
                    [
                        'name' => 'Zero banking default plan',
                    ],
                    [
                        'name' => 'testDefaultVpaPlan',
                    ],
                    [
                        'name' => 'PP190AMEX290',
                    ],
                    [
                        'name' => 'CommDefaultPlan',
                    ],
                    [
                        'name' => 'DefaultSubMerchant',
                    ],
                    [
                        'name' => 'Banking default plan',
                    ],
                    [
                        'name' => 'testDefaultEmiPlan',
                        'entity' => 'pricing',
                        'count' => 1,
                        'rules' => [
                            [],
                        ],
                    ],
                    [
                        'name' => 'testDefaultQrPlan',
                        'entity' => 'pricing',
                        'count' => 2,
                        'rules' => [
                            [],
                        ],
                    ],
                    [
                        'name' => 'TestPlan2',
                        'entity' => 'pricing',
                        'count' => 4,
                        'rules' => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                    [
                        'name' => 'TestPlan1',
                        'entity' => 'pricing',
                        'count' => 1,
                        'rules' => [
                            [
                                'plan_name'           => 'TestPlan1',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'HDFC',
                                'percent_rate'        => 1000,
                                'international'       => false,
                                'fixed_rate'          => 0,
                                'expired_at'          => null
                            ]
                        ]
                    ],
                    [
                        'name' => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count' => 34,
                        'rules' => [
                            [],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansTypeFilter' => [
        'request' => [
            'url'     => '/pricing',
            'method'  => 'GET',
            'content' => [
                'type' => 'commission',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'rules' => [
                            [
                                'type' => 'commission',
                            ]
                        ]
                    ]
                ],
            ],
        ]
    ],

    'testGetPricingPlansByRZPAdmin' => [
        'request'  => [
            'url'    => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 12,
                'entity' => 'collection',
                'items'  => [
                    [
                        'name' => 'Zero banking default plan',
                    ],
                    [
                        'name' => 'testDefaultVpaPlan',
                    ],
                    [
                        'name' => 'PP190AMEX290',
                    ],
                    [
                        'name' => 'CommDefaultPlan',
                    ],
                    [
                        'name' => 'DefaultSubMerchant',
                    ],
                    [
                        'name' => 'Banking default plan',
                    ],
                    [
                        'name'   => 'testDefaultEmiPlan',
                        'entity' => 'pricing',
                        'count'  => 1,
                        'rules'  => [
                            [],
                        ],
                    ],
                    [
                        'name'   => 'testDefaultQrPlan',
                        'entity' => 'pricing',
                        'count'  => 2,
                        'rules'  => [
                            [],
                        ],
                    ],
                    [
                        'name'   => 'TestPlan2',
                        'entity' => 'pricing',
                        'count'  => 4,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                    [
                        'name'   => 'TestPlan1',
                        'entity' => 'pricing',
                        'count'  => 1,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan1',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'HDFC',
                                'percent_rate'        => 1000,
                                'international'       => false,
                                'fixed_rate'          => 0,
                                'expired_at'          => null
                            ]
                        ]
                    ],
                    [
                        'name'   => 'testDefaultPlan',
                        'entity' => 'pricing',
                        'count'  => 34,
                        'rules'  => [
                            [],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansBySBIAdmin' => [
        'request'  => [
            'url'    => '/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'count'  => 1,
                'entity' => 'collection',
                'items'  => [
                    [
                        'name'   => 'TestPlan2',
                        'entity' => 'pricing',
                        'count'  => 4,
                        'rules'  => [
                            [
                                'plan_name'           => 'TestPlan2',
                                'gateway'             => null,
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'MC',
                                'payment_issuer'      => 'AXIS',
                                'percent_rate'        => 0,
                                'fixed_rate'          => 3000,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'debit',
                                'payment_network'     => 'MAES',
                                'payment_issuer'      => 'PUNB',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'ICIC',
                                'percent_rate'        => 250,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                            [
                                'plan_name'           => 'TestPlan2',
                                'payment_method'      => 'card',
                                'payment_method_type' => 'credit',
                                'payment_network'     => 'DICL',
                                'payment_issuer'      => 'SBIN',
                                'percent_rate'        => 275,
                                'fixed_rate'          => 0,
                                'international'       => false,
                            ],
                        ]
                    ],
                ]
            ]
        ]
    ],

    'testGetPricingPlansGrouping' => [
        'request' => [
            'url' => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'Zero banking default plan',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultVpaPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'PP190AMEX290',
                    'rules_count' => 9,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'DefaultSubMerchant',
                    'rules_count' => 16,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'Banking default plan',
                    'rules_count' => 62,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultEmiPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultQrPlan',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan1',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultPlan',
                    'rules_count' => 34,
                    'type'        => 'pricing',
                ],
            ],
        ],
    ],

    'testGetBuyPricingPlansGrouping' => [
        'request' => [
            'url' => '/buy_pricing/terminals',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'       => 'testPlan',
                    'rules_count'     => 10,
                    'type'            => 'buy_pricing',
                    'terminals_count' => 10,
                ],
            ],
        ],
    ],

    'testGetMerchantPlansWithFilters' => [
        'request' => [
            'url' => '/pricing/merchants',
            'method' => 'GET',
            'content' => [
                'type' => 'commission'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'TestPlan9',
                    'rules_count' => 1,
                    'type'        => 'commission',
                ],
            ],
        ],
    ],

    'testGetPricingPlansGroupingByRZPAdmin' => [
        'request'  => [
            'url'    => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'Zero banking default plan',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultVpaPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'PP190AMEX290',
                    'rules_count' => 9,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'CommDefaultPlan',
                    'rules_count' => 2,
                    'type'        => 'commission',
                ],
                [
                    'plan_name'   => 'DefaultSubMerchant',
                    'rules_count' => 16,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'Banking default plan',
                    'rules_count' => 62,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultEmiPlan',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultQrPlan',
                    'rules_count' => 2,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'TestPlan1',
                    'rules_count' => 1,
                    'type'        => 'pricing',
                ],
                [
                    'plan_name'   => 'testDefaultPlan',
                    'rules_count' => 34,
                    'type'        => 'pricing',
                ],
            ],
        ],
    ],

    'testGetPricingPlansGroupingBySBIAdmin' => [
        'request'  => [
            'url'    => '/pricing/merchants',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                [
                    'plan_name'   => 'TestPlan2',
                    'rules_count' => 4,
                ],
            ],
        ],
    ],

    'testMerchantAssignPricingPlanDefault' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testMerchantAssignPricingPlanWithInternational' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAssignPricingPlanFeeBearerMismatch' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantWithAmexEnabled' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_FOR_AMEX_NOT_PRESENT,
        ]
    ],

    'testMerchantAssignPricingPlanMerchantDefault' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testMerchantAssignAndGetPricingPlan' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan1',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'credit',
                        'payment_network'     => 'DICL',
                        'payment_issuer'      => 'HDFC',
                        'percent_rate'        => 1000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testMerchantReplacePricingPlan' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name' => 'TestPlan2',
                'entity' => 'pricing',
                'count' => 4,
                'rules' => [
                    [],
                    [],
                    [],
                    []
                ],
            ],
        ]
    ],

    'testMerchantGetPricingPlanNoPlanAssigned' => [
        'request' => [
            'url' => '/merchants/10000000000000/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testMerchantGetPricingPlan' => [
        'request' => [
            'url' => '/merchants/1FcXNxsHt5dOPI/pricing',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'id' => '1ycviEdCgurrFI',
                'name' => 'testFixturePlan',
                'entity' => 'pricing',
                'count' => 1,
                'rules' => [
                    [
                        'payment_method'      => 'card',
                        'payment_method_type' => 'debit',
                        'payment_network'     => 'VISA',
                        'payment_issuer'      => 'hdfc',
                        'percent_rate'        => 1000,
                        'fixed_rate'          => 10000,
                        'international'       => false,
                    ],
                ],
            ],
        ]
    ],

    'testDeletePricingPlanRuleForce' => [
        'request' => [
            'url' => '/pricing/1hDYlICobzOCYt/rule/1zD0BXpeOyaqpB/force',
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Pricing successfully deleted',
            ],
        ],
    ],

    'testDeleteBuyPricingPlanRuleForce' => [
        'request' => [
            'method' => 'delete',
        ],
        'response' => [
            'content' => [
                'message' => 'Buy Pricing rule group successfully deleted',
            ],
        ],
    ],

    'testAddInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1100,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1100,
                'international'       => true,
            ],
        ],
    ],

    'testAddDuplicateInternationalPricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddInternationalPricingPlanRuleForNonCardMethod' => [
        'request' => [
            'content' => [
                'payment_method'      => 'netbanking',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'International pricing rule is only allowed for card method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddInternationalPricingPlanRuleWithExtraFields' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'For international pricing rule, attribute payment_method_type should not be set',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'wallet',
                'payment_method_type' => 'credit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1200,
                'international'       => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method type field may be sent only when payment method is card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateWalletPricingRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'wallet',
                'payment_method_type' => null,
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 300,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testGetPricingNetworks' => [
        'request' => [
            'url'       => '/pricing/networks',
            'method'    => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddAmountRangePricingPlanRule' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'international'       => false,
                'amount_range_active' => true,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
        ],
    ],

    'testAddAmountRangePricingPlanRuleOverlap' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 2500,
                'amount_range_max'    => 100000000,
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddAmountRangePricingPlanRuleDuplicate' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 1500,
                'fixed_rate'          => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_min'    => 100,
                'amount_range_max'    => 25000,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleWithFeature' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'recurring',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'recurring',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeatureESAutomatic' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'esautomatic',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'esautomatic',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeatureCred' => [
        'request' => [
            'content' => [
                'payment_method'      => 'app',
                'payment_network'     => 'cred',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'app',
                'payment_network'     => 'cred',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeaturePayoutCardModeBankingProduct' => [
        'request' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => 'card',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'payout',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => 'card',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'payout',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeaturePayoutInvalidModeBankingProduct' => [
        'request' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => 'xyz',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'payout',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout mode should be NEFT/IMPS/RTGS/IFT/card/amazonpay',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddPricingPlanRuleWithFeaturePayoutAmazonpayModeBankingProduct' => [
        'request' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => 'amazonpay',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'payout',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'payment_method'      => 'fund_transfer',
                'payment_method_type' => 'amazonpay',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'payout',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeatureRefund' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'IMPS',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'IMPS',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund',
            ],
        ],
    ],
    'testAddPricingPlanRuleWithFeatureRefundWithPercentRate' => [
        'request' => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'IMPS',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 100,
                'fixed_rate'          => 100,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Percentage rate pricing is not allowed for refund',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testAddPricingPlanRuleWithFeatureRefundAndPaymentMethodNullValid' => [
        'request' => [
            'content' => [
                'feature'             => 'refund',
                'payment_method'      => null,
                'payment_method_type' => 'IMPS',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => null,
                'payment_method_type' => 'IMPS',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund',
            ],
        ],
    ],

    'testAddPricingPlanRuleWithFeatureRefundAndPaymentMethodAbsentValid' => [
        'request' => [
            'content' => [
                'feature'             => 'refund',
                'payment_method_type' => 'IMPS',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => null,
                'payment_method_type' => 'IMPS',
                'payment_network'     => null,
                'payment_issuer'      => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund',
            ],
        ],
    ],

    'testAddPricingWithPaymentMethodNullInvalid' => [
        'request' => [
            'content' => [
                'feature'             => 'payment',
                'payment_method'      => null,
                'payment_method_type' => null,
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment method field is required unless feature is in refund, optimizer, payment.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingRefundModes' => [
        'request' => [
            'content' => [
                'payment_method'      => null,
                'payment_method_type' => 'IMPS',
                'percent_rate'        => 0,
                'fixed_rate'          => 100,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'feature'             => 'refund'
            ],
            'method' => 'POST'
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithoutAccountType' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => '0',
                'account_type'        => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account type field is required only when product is banking'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithoutChannel' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => '0',
                'account_type'        => 'direct',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The channel field is required when account type is direct.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithInvalidChannel' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => '0',
                'account_type'        => 'direct',
                'channel'             => 'z',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid channel: z'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleForPrimaryPayoutWithAccountType' => [
        'request' => [
            'content' => [
                'product'             => 'primary',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => '0',
                'account_type'        => 'shared',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account type field is required only when product is primary'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleWithVpaReceiver' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'upi',
                'feature'             => 'payment',
                'percent_rate'        => 100,
                'receiver_type'       => 'vpa',
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => 5000,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'      => 'TestPlan1',
                'payment_method' => 'upi',
                'feature'        => 'payment',
                'percent_rate'        => 100,
                'receiver_type'       => 'vpa',
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testCreatePaymentEmiMethodTypePricingDebit' => [
        'request'   => [
            'method'    => 'POST',
            'content'   => [
                'type'  => 'otp',
                'otp'   => '111111'
            ]
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testAddPricingPlanRulesForBankingProductWithBothSupportedAccountTypes' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'fund_transfer',
                'feature'             => 'payout',
                'product'             => 'banking',
                'account_type'        => 'shared',
                'percent_rate'        => 0,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'international'       => false,
                'amount_range_active' => true,
            ]
        ]
    ],

    'testUpdateFreePayoutRule' => [
        'request' => [
            'content' => [],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testAddDuplicatePricingPlanRulesForBankingProduct' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The new rule matches with an active existing rule',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddDuplicatePricingRuleWithDifferentAppName' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
                'app_name'            => 'xpayroll',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => false,
                'amount_range_active' => true,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
                'app_name'            => 'xpayroll',
            ]
        ]
    ],

    'testAddDuplicatePricingRulesWithSameAppName' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
                'app_name'            => 'xpayroll',
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The new rule matches with an active existing rule',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithCorrectAuth' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'account_type'        => 'shared',
                'auth_type'           => 'private',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'fund_transfer',
                'feature'             => 'payout',
                'product'             => 'banking',
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'percent_rate'        => 0,
                'international'       => false,
                'amount_range_active' => false,
            ]
        ]
    ],

    'testAddPricingPlanRuleForBankingPayoutWithIncorrectAuth' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'account_type'        => 'shared',
                'auth_type'           => 'xyz',
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected auth type is invalid'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithoutAuth' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'account_type'        => 'shared',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => false,
                'amount_range_active' => false,
                'account_type'        => 'shared',
            ],
        ],
    ],

    'testAddPricingPlanRuleForPaymentMethodTypeDebitWithoutAuth' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleForPaymentMethodTypeDebitWithIncorrectAuth' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'auth_type'           => 'private',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected auth type is invalid'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRuleForProductPrimaryAndPaymentMethodTypeCredit' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'credit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'auth_type'           => 'private',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The auth type field can be sent only when payment method type is debit or product is banking'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPricingPlanRulesForBankingPayoutWithBothSupportedAuths' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'shared',
                'auth_type'           => 'private',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'fund_transfer',
                'feature'             => 'payout',
                'product'             => 'banking',
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'percent_rate'        => 0,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'international'       => false,
                'amount_range_active' => true,
            ]
        ]
    ],

    'testAddDuplicatePricingPlanRulesForBankingProductWithAccountTypeChannelAndAuthType' => [
        'request'   => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 1,
                'amount_range_max'    => 1500,
                'amount_range_min'    => 0,
                'account_type'        => 'direct',
                'channel'             => 'rbl'
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The new rule matches with an active existing rule',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED,
        ],
    ],

    'testAddPricingPlanRuleForBankingPayoutWithPayoutsFilter' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'payouts_filter'      => 'xyz',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'fund_transfer',
                'feature'             => 'payout',
                'product'             => 'banking',
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'percent_rate'        => 0,
                'international'       => false,
                'amount_range_active' => false,
                'payouts_filter'      => 'xyz',
            ]
        ]
    ],

    'testAddPricingPlanRuleForBankingPayoutWithNullPayoutsFilter' => [
        'request' => [
            'content' => [
                'product'             => 'banking',
                'feature'             => 'payout',
                'payment_method'      => 'fund_transfer',
                'percent_rate'        => 0,
                'international'       => 0,
                'amount_range_active' => 0,
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'payouts_filter'      => null,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'fund_transfer',
                'feature'             => 'payout',
                'product'             => 'banking',
                'account_type'        => 'shared',
                'auth_type'           => 'private',
                'percent_rate'        => 0,
                'international'       => false,
                'amount_range_active' => false,
                'payouts_filter'      => null,
            ]
        ]
    ],

    'testAddPricingPlanRuleForNotBankingPayoutWithNullPayoutsFilter' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'payouts_filter'      => null,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'plan_name'           => 'TestPlan1',
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => false,
                'amount_range_active' => false,
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'payouts_filter'      => null,
            ],
        ],
    ],

    'testAddPricingPlanRuleForNotBankingPayoutWithPayoutsFilter' => [
        'request'  => [
            'content' => [
                'payment_method'      => 'card',
                'payment_method_type' => 'debit',
                'payment_network'     => 'MAES',
                'payment_issuer'      => 'HDFC',
                'percent_rate'        => 1000,
                'international'       => 0,
                'amount_range_active' => '0',
                'amount_range_min'    => null,
                'amount_range_max'    => null,
                'payouts_filter'      => 'xyz',
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payouts filter field may be sent only when product is banking'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateUpiOneTimePlanWithoutAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiOnetimePlan',
                'rules'     => [
                    [
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing'
                    ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiOnetimePlan',
                'entity'    => 'pricing',
                'count'     => 1,
                'rules'     => [
                    [
                        'plan_name'             => 'upiOnetimePlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment',
                        'amount_range_active'    => false,
                    ]
                ],
            ],
        ],
    ],

    'testCreateUpiOneTimePlanWithAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiOnetimePlan',
                'rules'     => [
                    [
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                    ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiOnetimePlan',
                'entity'    => 'pricing',
                'count'     => 1,
                'rules'     => [
                    [
                        'plan_name'             => 'upiOnetimePlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment',
                        'amount_range_active'    => true,
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                    ]
                ],
            ],
        ],
    ],

    'testCreateUpiAutopayPlanWithoutAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiAutopayPlan',
                'rules'     => [
                    [
                        'percent_rate'           => 100,
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'type'                   => 'pricing',
                        'payment_method_subtype' => 'initial'
                    ],
                    [
                        'percent_rate'           => 200,
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'type'                   => 'pricing',
                        'payment_method_subtype' => 'auto',
                        'fixed_rate'             => 200,
                    ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiAutopayPlan',
                'entity'    => 'pricing',
                'count'     => 2,
                'rules'     => [
                    [
                        'fixed_rate'            => 200,
                        'plan_name'             => 'upiAutopayPlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> 'auto',
                        'payment_issuer'        => null,
                        'percent_rate'          => 200,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment'
                    ],
                    [
                        'percent_rate'          => 100,
                        'plan_name'             => 'upiAutopayPlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> 'initial',
                        'payment_issuer'        => null,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment'
                    ]
                ],
            ],
        ],
    ],

    'testCreateUpiAutopayPlanWithAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiAutopayPlan',
                'rules'     => [
                    [
                        'payment_method_subtype' => 'initial',
                        'amount_range_max'       => 150000,
                        'amount_range_min'       => 0,
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                    ],
                    [
                        'payment_method_subtype' => 'auto',
                        'amount_range_max'       => 1500000,
                        'amount_range_min'       => 0,
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                    ],
                    [
                        'payment_method'         => 'card',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiAutopayPlan',
                'entity'    => 'pricing',
                'count'     => 3,
                'rules'     => [
                    [
                        'payment_method_subtype'=> 'auto',
                        'amount_range_max'       => 1500000,
                        'amount_range_min'       => 0,
                        'plan_name'             => 'upiAutopayPlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment',
                        'amount_range_active'    => true,
                    ],
                    [
                        'payment_method_subtype'=> 'initial',
                        'amount_range_max'       => 150000,
                        'amount_range_min'       => 0,
                        'plan_name'             => 'upiAutopayPlan',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment',
                        'amount_range_active'    => true,
                    ],
                    [
                        'payment_method'        => 'card',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                        'plan_name'             => 'upiAutopayPlan',
                        'payment_method_type'   => null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'payment',
                        'amount_range_active'    => true,
                    ],
                ],
            ],
        ],
    ],

    'testCreateUpiAutopayPlanWithInvalidSubtypeRule' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiAutopayPlan',
                'rules'     => [
                    [
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 90000,
                    ],
                    [
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'card',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                    ],
                    [
                        'product'                => 'primary',
                        'feature'                => 'payment',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'payment_method_subtype' => 'consumer',
                        'amount_range_active'    => '1',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 20000000,
                    ],
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only null (one-time payments) or initial or auto value is allowed for sub type field in UPI.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreateUpiInAppPlanWithoutAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiInAppWithoutAmountRange',
                'rules'     => [
                    [
                        'product'                => 'primary',
                        'feature'                => 'upi_inapp',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing'
                    ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiInAppWithoutAmountRange',
                'entity'    => 'pricing',
                'count'     => 1,
                'rules'     => [
                    [
                        'plan_name'             => 'upiInAppWithoutAmountRange',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'upi_inapp',
                        'amount_range_active'    => false,
                    ]
                ],
            ],
        ],
    ],
    'testCreateUpiInAppPlanWithAmountRange' => [
        'request' => [
            'content' => [
                'plan_name' => 'upiInAppWithAmountRange',
                'rules'     => [
                    [
                        'product'                => 'primary',
                        'feature'                => 'upi_inapp',
                        'payment_method'         => 'upi',
                        'percent_rate'           => 100,
                        'type'                   => 'pricing',
                        'amount_range_active'    => '1',
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                    ]
                ],
            ],
            'url' => '/pricing',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'name'      => 'upiInAppWithAmountRange',
                'entity'    => 'pricing',
                'count'     => 1,
                'rules'     => [
                    [
                        'plan_name'             => 'upiInAppWithAmountRange',
                        'payment_method'        => 'upi',
                        'payment_method_type'   => null,
                        'payment_method_subtype'=> null,
                        'payment_issuer'        => null,
                        'percent_rate'          => 100,
                        'type'                  => 'pricing',
                        'product'               => 'primary',
                        'feature'               => 'upi_inapp',
                        'amount_range_active'    => true,
                        'amount_range_min'       => 0,
                        'amount_range_max'       => 50000,
                    ]
                ],
            ],
        ],
    ],
];
