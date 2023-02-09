<?php

use RZP\Gateway\HdfcGateway\HdfcGatewayErrorCode;
use RZP\Error\ErrorCode;

return [
    'testAddEmiPlansWithoutMerchant' => [
        'request' => [
            'content' => [
                'bank'       => 'HDFC',
                'duration'   => 3,
                'rate'       => 1045,
                'methods'    => 'card',
                'min_amount' => 400000,
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testDuplicateAddEmiPlans' => [
        'request' => [
            'content' => [
                'bank'       => 'HDFC',
                'duration'   => 9,
                'rate'       => 1045,
                'methods'    => 'card',
                'min_amount' => 400000,
                'merchant_id'=> '100000Razorpay',
                'type'       => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EMI_PLAN_EXIST
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAddEmiPlansWithMerchant' => [
        'request' => [
            'content' => [
                'bank'        => 'HDFC',
                'duration'    => 3,
                'rate'        => 1045,
                'methods'     => 'card',
                'min_amount'  => 400000,
                'merchant_id' => '10000000000001',
                'type'        => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'bank'             => 'HDFC',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_payback' => 172,
                'merchant_id'      => '10000000000001',
            ],
        ],
    ],

    'testAddEmiPlansWithMerchantPayback' => [
        'request' => [
            'content' => [
                'bank'             => 'HDFC',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_id'      => '10000000000000',
                'merchant_payback' => 0,
                'type'             => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'bank'             => 'HDFC',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_payback' => 0,
                'merchant_id'      => '10000000000000',
            ],
        ],
    ],

    'testAddBOBEmiPlansWithMerchant' => [
        'request' => [
            'content' => [
                'bank'        => 'BARB',
                'duration'    => 3,
                'rate'        => 1045,
                'methods'     => 'card',
                'min_amount'  => 400000,
                'merchant_id' => '100000Razorpay',
                'type'        => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'bank'             => 'BARB',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_payback' => 172,
                'merchant_id'      => '100000Razorpay',
            ],
        ],
    ],

    'testAddBOBEmiPlanWithoutType' => [
        'request' => [
            'content' => [
                'network'     => 'BAJAJ',
                'duration'    => 3,
                'rate'        => 1045,
                'min_amount'  => 400000,
                'merchant_id' => '100000Razorpay',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'network'     => 'BAJAJ',
                'duration'    => 3,
                'rate'        => 1045,
                'min_amount'  => 400000,
                'merchant_id' => '100000Razorpay',
            ],
        ],
    ],

    'testEnableMerchantSubvention' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id'      => '10000000000000',
            ],
        ],
    ],

    'testFetchAllEmiPlansOnPublicAuth' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 500000,
                    'plans' => [
                        9 => 12,
                    ],
                ],
            ],
        ],
    ],

    'testFetchAllEmiPlansOnPublicAuthViaCps' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 500000,
                    'plans' => [
                        9 => 12,
                    ],
                ],
            ],
        ],
    ],

    'testFetchAllEmiPlansWithSbiOnPublicAuth' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'SBIN' => [
                    'min_amount' => 300000,
                    'plans' => [
                        9 => 14,
                    ],
                ],
                'HDFC' => [
                    'min_amount' => 25000,
                    'plans' => [
                        6 => 12.5,
                    ],
                ],
            ],
        ],
    ],

    'testFetchEmiPlanUsingPlanId' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi/10101010101010',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'bank'             => 'HDFC',
                'network'          => null,
                'rate'             => 1200,
                'duration'         => 9,
                'methods'          => 'card',
                'min_amount'       => 500000,
                'issuer_plan_id'   => null,
                'subvention'       => 'customer',
                'merchant_payback' => 518,
                'issuer'           => 'HDFC',
                'entity'           => 'emi_plan',
                'admin'            => true,
            ],
        ],
    ],

    'testFetchEmiPlanUsingPlanIdAndAssertIssuerNameForNetwork' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi/10101010101010',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'bank'             => null,
                'network'          => 'AMEX',
                'rate'             => 1200,
                'duration'         => 9,
                'methods'          => 'card',
                'min_amount'       => 500000,
                'issuer_plan_id'   => null,
                'subvention'       => 'customer',
                'merchant_payback' => 518,
                'issuer'           => null,
                'entity'           => 'emi_plan',
                'admin'            => true,
            ],
        ],
    ],

    'testDeleteEmiPlan' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/emi/10101010101010',
            'method' => 'delete'
        ],
        'response' => [
            'content' => [
                'bank'       => 'HDFC',
                'rate'       => 1200,
                'duration'   => 9,
                'methods'    => 'card',
                'min_amount' => 500000
            ],
        ],
    ],

    'testAddCobrandingPartnerEmiPlan' => [
        'request' => [
            'content' => [
                'cobranding_partner' => 'onecard',
                'duration'    => 3,
                'rate'        => 1045,
                'methods'     => 'card',
                'min_amount'  => 400000,
                'merchant_id' => '100000Razorpay',
                'type'        => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'cobranding_partner' => 'onecard',
                'duration'           => 3,
                'rate'               => 1045,
                'methods'            => 'card',
                'min_amount'         => 400000,
                'merchant_payback'   => 172,
                'merchant_id'        => '100000Razorpay',
            ],
        ],
    ],

    'testAddFDRLEmiPlansWithMerchant' => [
        'request' => [
            'content' => [
                'bank'        => 'FDRL',
                'duration'    => 3,
                'rate'        => 1045,
                'methods'     => 'card',
                'min_amount'  => 400000,
                'merchant_id' => '100000Razorpay',
                'type'        => 'credit',
            ],
            'method' => 'POST',
            'url'    => '/emi',
        ],
        'response' => [
            'content' => [
                'bank'             => 'FDRL',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_payback' => 172,
                'merchant_id'      => '100000Razorpay',
            ],
        ],
    ],
];
