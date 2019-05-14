<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testImplicitVariableOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitFixedOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitForRecordOnly' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicit' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicitForSubvention' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnInternationalPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitPricingRuleAbsent' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testGSTForPaymentsLessThan2K' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicitPostpaid' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCustomerBearerExplicitBearerAuth' => [
        'request'  => [
            'url'     => '/payments/create/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 400000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'harshil@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => [
                    'name'  => 'HarshilMathur',
                    'number' => '5104015555555558',
                    'expiry_month' => 11,
                    'expiry_year' => 24,
                    'cvv'         => 124,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => ((4000 * 2 * 18 / 100) + (4000 * 0.2 * 18 / 100)) / 100,
                    'fees'            => ((4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)) / 100,
                    'amount'          => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100) + (4000 * 0.2) + (4000 * 0.2 * 18 / 100)) / 100,
                    'razorpay_fee'    => (80 + 8),
                    'original_amount' => 4000,
                ],
            ],
        ],
    ],

    'testCustomerBearerExplicitPublicAuth' => [
        'request'  => [
            'url'     => '/payments/create/fees',
            'method'  => 'POST',
            'content' => [
                'amount'   => 400000,
                'currency' => 'INR',
                'method'   => 'card',
                'email'    => 'harshil@razorpay.com',
                'contact'  => '+918888888888',
                'card'     => [
                    'name'  => 'HarshilMathur',
                    'number' => '5104015555555558',
                    'expiry_month' => 11,
                    'expiry_year' => 24,
                    'cvv'         => 124,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'display' => [
                    'tax'             => ((4000 * 2 * 18 / 100)) / 100,
                    'fees'            => ((4000 * 2) + (4000 * 2 * 18 / 100)) / 100,
                    'amount'          => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18 / 100)) / 100,
                    'razorpay_fee'    => 80,
                    'original_amount' => 4000,
                ],
            ],
        ],
    ],

    'testCustomerBearerPaymentCreateBearerAndPublicAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment failed because fees or tax was tampered',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCustomerBearerExplicitOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testCustomerBearerOnExistingAuthorizedPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],
];
