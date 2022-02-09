<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRecurringOtpFix' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testSaveGatewayPriority' => [
        'request' => [
            'content' => [
                'axis_migs'   => '100',
            ],
            'url' => '/gateway/priorities/card',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'card' => [
                    'axis_migs'   => '100',
                ]
            ]
        ]
    ],

    'testCardRecurringAutoPaymentIfTokenIsPaused' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "customer_id" => "cust_id",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
        ],
    ],

    'testCardRecurringAutoPaymentIfTokenIsCancelled' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "customer_id" => "cust_id",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
        ],
    ],

    'testCardRecurringAutoPaymentIfTokenIsExpired' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "customer_id" => "cust_id",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_RECURRING_TOKEN_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_RECURRING_TOKEN_EXPIRED,
        ],
    ],

    'testCardRecurringAutoPaymentIfTokenStatusIsDeactivated' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "customer_id" => "cust_id",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING,
        ],
    ],

    'testCardRecurringAutoPaymentIfTokenStatusIsSuspended' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "customer_id" => "cust_id",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'description' => PublicErrorDescription::BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING,
        ],
    ],

    'testRecurringInternationalPaymentWhenNotAllowed' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testRecurringPaymentCreateFeatureDisabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
        ],
    ],

    'testRecurringPaymentCreatePrivateAuthS2SDisabled' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
        ],
    ],

    'testRecurringPaymentFailedCardNotSupported' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
        ],
    ],

    'testRecurringPaymentAmexCardNotSupported' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
        ],
    ],

    'testRecurringPaymentUsingSavedCardTokenNotRecurring' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
        ],
    ],

    'testRecurringPaymentsWithMultipleGatewayTokensForOneToken' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testRecurringSecondPaymentUnassignedTerminal' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testRecurringSecondPaymentCreatePrivateAuthHitachi' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testRecurringSecondPaymentCreatePrivateAuth' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testRecurringPaymentWithNewCustomer' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testRecurringPaymentCardNetworkNotSupported' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'message'             => 'Your payment was not successful as the seller does not support recurring payments.We suggest contacting the seller for more details.',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED,
        ],
    ]
];
