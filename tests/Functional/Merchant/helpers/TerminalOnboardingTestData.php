<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testInitiateOnboardingProxyRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => ['gateway' => 'wallet_paypal']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingWithNoGatewayInInput' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingProxyRouteInvalidGateway' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => ['gateway' => 'invalid_gateway']
        ],
        'response' => [
            'response' => []
        ],
    ],

    'testInitiateOnboardingAdminRoutePaysecureAxis' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals/onboard',
            'content' => ['gateway' => 'paysecure', 'gateway_acquirer' => 'axis']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingAdminRouteInvalidGateway' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals/onboard',
            'content' => ['gateway' => 'invalid_gateway', 'gateway_acquirer' => 'axis']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testMerchantPricingPaypalPlanRule' => [
        'request' => [
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
            'content'   =>  [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_network'       => 'paypal',
                    'percent_rate'          => '0',
                    'international'         => '0',
                    'idempotency_key'       => 'random123'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'plan_id'   =>  '1A0Fkd38fGZPVC',
                        'success'   =>   true,
                        'idempotency_key'   =>  'random123'
                    ]
                ]
            ],
        ],
    ],

    'testMerchantPricingPaypalPlanRuleAlreadyExist' => [
        'request' => [
            'url'       => '/pricing/rules/bulk',
            'method'    => 'POST',
            'content'   =>  [
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_network'       => 'paypal',
                    'percent_rate'          => '0',
                    'international'         => '0',
                    'idempotency_key'       => 'random123'
                ],
                [
                    'merchant_id'           => '10000000000000',
                    'product'               => 'primary',
                    'feature'               => 'payment',
                    'payment_method'        => 'wallet',
                    'payment_method_type'   => '',
                    'payment_network'       => 'paypal',
                    'percent_rate'          => '0',
                    'international'         => '0',
                    'idempotency_key'       => 'random223'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'plan_id'   =>  '1A0Fkd38fGZPVC',
                        'success'   =>   true,
                        'idempotency_key'   =>  'random123'
                    ],
                    [
                        'success'   =>   false,
                        'idempotency_key'   =>  'random223',
                        'error' =>  [
                            'description' => 'The new rule matches with an active existing rule',
                            'code' => 'BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED'
                        ],
                    ],
                ]
            ],
        ],
    ],
    'testEnablePaypalMethodInternal'  =>  [
        'request' => [
            'method' => 'PATCH',
            'url' => '/merchants/10000000000000/methods',
            'content' => [
                'paypal'      => true
            ]
        ],
        'response' => [
            'content' => [
                'paypal'      => true
            ],
            'status_code'   => 200,
        ],
    ],

];
