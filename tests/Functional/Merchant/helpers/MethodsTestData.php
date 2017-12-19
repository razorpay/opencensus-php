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
                    'netbanking' => true
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
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
                    'cards' => [
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
                    'mobikwik' => true,
                ],
                'recurring' => [
                    'cards' => [
                        'credit' => [
                            'MasterCard',
                            'Visa',
                        ],
                    ],
                    'netbanking' => []
                ],
            ],
        ],
    ],
];
