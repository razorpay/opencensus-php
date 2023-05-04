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
                'links' =>  "https://www.sandbox.paypal.com/IN/merchantsignup/partner/onboardingentry?token=MWRiYWM1NDQtZWJlZC00M2VjLTlkMGMtZmM2MjRmYzc0N2M4ZW5NUGdxS2FUb0ozcTRRYmtSUkd5bXNtYnJiOUs0Y2ZYQU9JZURVL29SWT12MQ==&context_token=4909428984085513216"
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingProxyRouteForPaytm' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => [
                'gateway' => 'paytm',
                'identifiers' => [
                    'gateway_merchant_id' => 'merchant_provided_paytm_key',
                    'gateway_terminal_id' => 'industry_type_id',
                    'gateway_access_code' => 'website'
                ],
                'secrets' => [
                    'gateway_secure_secret' => 'merchant_provided_paytm_sec'
                ]
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingProxyRouteForPhonepe' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => [
                'gateway' => 'wallet_phonepe',
            ]
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

    'testInitiateOnboardingAxisAdminRoutePaysecureAxis' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/external_org/terminals/onboard',
            'content' => ['gateway' => 'paysecure', 'gateway_acquirer' => 'axis']
        ],
        'response' => [
            'content' => [
                'data' => [
                    'id'                => 'ETbhgqkBRIiAkt',
                    'merchant_id'       => '10000000000000',
                    'org_id'            => '100000razorpay',
                    'procurer'          => 'Razorpay',
                    'gateway_acquirer'  => 'axis',
                ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingNonAxisOrgAdminRoutePaysecureAxis' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/external_org/terminals/onboard',
            'content' => ['gateway' => 'paysecure', 'gateway_acquirer' => 'axis']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 400,
        ],
    ],
    'testInitiateOnboardingAdminRoutePaysecureAxisExtraFieldsValidationFailure' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/external_org/terminals/onboard',
            'content' => ['gateway' => 'paysecure', 'currency_code' => "INR"]
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 400,
        ],
    ],

    'testInitiateOnboardingAdminRoutePaysecureAxisValidationFailureInvalidAcquirer' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/external_org/terminals/onboard',
            'content' => ['gateway' => 'paysecure', 'gateway_acquirer' => 'ratn']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 400,
        ],
    ],


    'testInitiateOnboardingAdminRouteFulcrum' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals/onboard',
            'content' => ['gateway' => 'fulcrum', 'gateway_acquirer'  => 'ratn', 'currency_code' => 'INR']
        ],
        'response' => [
            'content' => [
                'data' => [
                    'id'                => 'ETbhgqkBRIiAkt',
                    'merchant_id'       => '10000000000000',
                    'org_id'            => '100000razorpay',
                    'procurer'          => 'Razorpay',
                    'gateway_acquirer'  => 'ratn',
                ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingAdminRouteExtraFieldsFulcrumValidationFailure' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals/onboard',
            'content' => ['gateway' => 'fulcrum', 'gateway_input' => 'axis']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 400,
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
    'testEnableUpiMethodInternal'  =>  [
        'request' => [
            'method' => 'PATCH',
            'url' => '/merchants/10000000000000/methods',
            'content' => [
                'upi'      => true
            ]
        ],
        'response' => [
            'content' => [
                'upi'      => true
            ],
            'status_code'   => 200,
        ],
    ],
];
