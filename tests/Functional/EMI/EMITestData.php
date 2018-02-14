<?php

use RZP\Gateway\HdfcGateway\HdfcGatewayErrorCode;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddEmiPlans' => [
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
        'response' => [
            'content' => [
                'bank'             => 'HDFC',
                'duration'         => 3,
                'rate'             => 1045,
                'methods'          => 'card',
                'min_amount'       => 400000,
                'merchant_payback' => 172
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
                'issuer_name'      => 'HDFC Bank',
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
                'issuer_name'      => 'American Express',
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
];
