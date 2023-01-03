<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testFetchRuleCascadingForAdminAuth' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'amount' => 1000000,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testfetchAxisPaysecurePayments' => [
        'request' => [
            'url'     => '/axis_admin/axis_paysecure/payment',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                [
                    'method' => 'card',
                    'gateway_terminal_id' => 'meowmeow',
                    ],
            ],
        ],
    ],

    'testfetchAxisEntitiesAll' => [
        'request' => [
            'url'     => '/axis_admin/entities/all',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'fields' => [],
                'entities'  => [
                    'payment' => [],
                ],
            ],
        ],
    ],

    'testFetchRuleCascadingForAdminAuthRestricted' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'status' => 'created',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testFetchPaymentsWithNoTenantRole' => [
        'request'   => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'status' => 'created',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testFetchSinglePaymentWithNoTenantRole' => [
        'request'   => [
            'url'    => '/admin/payment/:id',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testFetchPaymentsWithTenantRole' => [
        'request'  => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'status' => 'created',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testFetchSinglePaymentWithTenantRole' => [
        'request'  => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'status' => 'created',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created',
            ],
        ],
    ],

    'testPaymentVerifyAdminWithTenantRole' => [
        'request'  => [
            'url'     => '',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'payment' => [
                    'status' => 'created',
                ]
            ],
        ],
    ],

    'testPaymentVerifyAdminWithNoTenantRole' => [
        'request'  => [
            'url'     => '',
            'method'  => 'get',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],

    'testFetchForAdminAuthRestrictedFilterAcquirerData' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'acquirer_data' => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'amount' => 1000000,
                        'currency' => "INR",
                        'status' => "created",
                        'acquirer_data' => [
                            'bank_transaction_id' => "1234"
                        ],
                    ]
                ]
            ],
        ],
    ],

    'testAdminAuthPaymentFetch' => [
        'request' => [
            'url'     => '/admin/payment/',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'entity'              => 'payment',
                'dcc'                 => false,
                'forex_rate'          => null,
                'dcc_mark_up_percent' => null,
                'gateway_amount'      => 1000000,
                'gateway_currency'    => 'INR',
                'dcc_offered'         => null,
                'dcc_markup_amount'   => null,
            ],
        ],
    ],

    'testAxisAdminAuthPaymentFetch' => [
        'request' => [
            'url'     => '/admin/payment/',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'entity'              => 'payment',
                'merchant_id'         => '10000000000000',
                'amount'              =>  1000000,
                'currency'            =>  'INR',
                'method'              =>  'card',
                'base_amount'         =>  1000000,
                'status'              =>  'created',
                'gateway'             =>  "hdfc",
                'terminal_id'         =>  "1000HdfcShared",
                'recurring'           =>  FALSE,
                'captured'            =>  FALSE,
            ],
        ],
    ],

    'testAdminAuthPaymentFetchWithDefaultMccFields' => [
        'request' => [
            'url'     => '/admin/payment/',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'entity'              => 'payment',
                'mcc'                 => false,
                'forex_rate_received' => null,
                'forex_rate_applied'  => null,
            ],
        ],
    ],

    'testFetchRuleVPAFilterForAdminAuth' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'vpa' => 'success1@razorpay',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'vpa' => 'success1@razorpay',
                    ]
                ],
            ],
        ],
    ],

    'testAdminDashboardPaymentsFetchWhenContactIsPassedExpectsPaymentsAssociatedWithContact' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'contact' => '+919876543210',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'contact' => '+919876543210',
                        'entity' => 'payment',
                    ]
                ],
            ],
        ],
    ],

    'testFetchCardQueryParams' => [
        'request' => [
            'url'     => '/admin/payment',
            'method'  => 'get',
            'content' => [
                'iin'   => '411111',
                'last4' => '1111',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'card_id' => 'card_100000001lcard',
                    ]
                ],
            ],
        ],
    ],

    'testFetchRulesForPrivateWithExtraFieldsError' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'email' => 'test@example.com',
            ],
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],


    'testFetchRuleswithCustomerIdError' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_9evnGgkvo0XnSh',
            ],
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesCascadingForProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'email' => 'test@example.com',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testErrorFetchRulesForProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'amount' => 1000000,
            ],
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchRulesWithSignedIdForPrivateAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'order_id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testFetchResponseForLateAuthFlagResponse' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'order_id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ],
        ],
    ],

    'testFetchByIdForExpressAuth' => [
        'request' => [
            'url'     => '/payments_internal/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'order' => [
                    'entity' => 'order',
                ],
            ],
        ],
    ],

    'testFetchByIdNotExpressAuthError' => [
        'request' => [
            'url'     => '/payments_internal/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchWithExpandsForProxyAuth' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'expand'  => [
                    'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity' => 'payment',
                        'card' => [
                            'name' => ''
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFindWithExpandsForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'card'   => [
                    'name' => ''
                ],
            ],
        ],
    ],

    'testGpayPaymentFetchWithUnselectedMethod' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'provider' => 'google_pay'
            ],
        ],
    ],

    'testGpayPaymentFetchWithCardMethod' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'provider' => 'google_pay'
            ],
        ],
    ],

    'testGpayPaymentFetchWithUpiMethod' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'provider' => 'google_pay'
            ],
        ],
    ],

    'testNonGpayPaymentFetch' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
            ],
        ],
    ],

    'testFindWithExpandsForPrivateAuthWithExtraAttributesExposed' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'authorized_at' => null,
                'late_authorized' => false,
                'captured_at' => null,
                'auto_captured' => null,
            ],
        ],
    ],

    'testFetchStatusCountForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/transaction/count',
            'method'  => 'get',
            'content' => [
                'to' => 1589242880,
                'from' => 1589242280
            ],
        ],
        'response' => [
            'content' => [
                "status" => "true",
                "response" => [
                    "captured" => 2,
                    "created" => 0,
                    "authorized" => 1,
                    "count" => 3
                ]
            ],
        ],
    ],

    'testFetchStatusCountForPrivateAuthError' => [
        'request' => [
            'url'     => '/payments/transaction/count',
            'method'  => 'get',
            'content' => [
                'to' => 1589242880,
                'from' => 1589242980
            ],
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
        ],
    ],

    'testFetchStatusCountForPrivateAuthFeatureOff' => [
        'request' => [
            'url'     => '/payments/transaction/count',
            'method'  => 'get',
            'content' => [
                'to' => 1589242880,
                'from' => 1589242980
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFindWithEmiAsExpandsForPrivateAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'emi',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'emi'   => [
                    'issuer' => 'HDFC',
                    'rate' => 1399,
                    'duration' => 6,
                ],
            ],
        ],
    ],

    'testFindWithEmiPlanAsExpandsForProxyAuth' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'emi_plan',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'emi_plan'   => [
                    'issuer' => 'HDFC',
                    'rate' => 1399,
                    'duration' => 6,
                ],
            ],
        ],
    ],

    'testFindWithExpandsForPrivateAuthWithInvalidExpand' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'card',
                    'fake',
                ],
            ],
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
        ],
    ],

    'testFetchWithExpandsTransfer' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payments',
            'content' => [
                'expand' => ['transfer'],
            ],
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
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testFetchWithDisputes' => [
        'request'   => [
            'method'  => 'get',
            'url'     => '/payments',
            'content' => [
                'email'  => 'abc@email.com',
                'expand' => [
                    'disputes',
                ],
            ],
        ],
        'response'  => [
            'content'       => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'             => 'payment',
                        'amount'             => 1000000,
                        'currency'           => 'INR',
                        'status'             => 'captured',
                        'method'             => 'card',
                        'amount_refunded'    => 0,
                        'amount_transferred' => 0,
                        'captured'            => true,
                        'email'             => 'abc@email.com',
                        'fee'               => 0,
                        'disputes'          => [
                            'entity' => 'collection',
                            'count'  => 2,
                            'items'  => [
                                [
                                    'amount'      => 1000000,
                                    'currency'    => 'INR',
                                    'reason_code' => 'SOMETHING_BAD',
                                    'status'      => 'open',
                                    'phase'       => 'chargeback',
                                ],
                                [
                                    'amount'      => 1000000,
                                    'currency'    => 'INR',
                                    'reason_code' => 'SOMETHING_BAD',
                                    'status'      => 'open',
                                    'phase'       => 'chargeback',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchPaymentByRecurringFilter' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'recurring' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testPaymentFetchWithPartnerAuthWithoutAccountIdInHeader' => [
        'request' => [
            'url'     => '/payments/{id}/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'method'   => 'card',
                'status'   => 'authorized',
                'entity'   => 'payment',
                'currency' => 'INR',
            ],
        ],
    ],

    'testPaymentFetchWithDifferentPartnerAuthWithoutAccountIdInHeader' => [
        'request' => [
            'url'     => '/payments/{id}/partner',
            'method'  => 'GET',
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
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testPrivateAuthPaymentFetchFeeBearerAttribute' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                ],
        ],
    ],
    'testProxyAuthPaymentFetchFeeBearerAttribute' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchPaymentFromPgRouterWithPrivateAuth' => [
        'request' => [
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity'            => 'payment',
            ],
        ],
    ],

    'testFetchPaymentFromPgRouterWithPrivateAuthFailure' => [
        'request' => [
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testFetchPaymentFromPgRouterWithAdminAuth' => [
        'request' => [
            'url' => '/admin/payment',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'entity'            => 'payment',
            ],
        ],
    ],

    'testProxyAuthPaymentWithExtraAttributesExposed' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPrivateAuthPaymentWithReceiverType' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchPaymentFromPgRouterWithExpandsCard' => [
        'request' => [
                'method'  => 'get',
                'content' => [
                    'expand' => [
                        'card',
                    ],
                 ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'payment',
                'card' => [
                    'name' => ''
                ],
            ],
        ],
    ],

    'testFetchPaymentFromPgRouterWithCard' => [
        'request' => [
            'method'  => 'get',
            'content' => [
                'expand' => [
                    'card',
                ],
             ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'payment',
                'card'    => [
                    'name' => ''
                ],
            ],
        ],
    ],

    'testFetchPaymentFromPgRouterWithNonToken' => [
        'request' => [
            'method'  => 'get',
            'content' => [
                'id'     => '',
                'expand' => [
                    'token',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'payment',
            ],
        ],
    ],

    'testProxyAuthFetchPaymentOnTerminalId' => [
        'request' => [
            'url'     => '/payments',
            'method'  => 'get',
            'content' => [
                'terminal_id' => '',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
            ],
        ],
    ],

    'testProxyAuthFetchPaymentByIdForOptimiser' => [
        'request' => [
            'url'     => '/payments/',
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'payment',
                'optimizer_provider' => 'Razorpay'
            ],
        ],
    ],

    'testFetchPaymentByIdWithReplicaLag' => [
        'request' => [
            'url'   => '/payments/{id}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment'
            ],
        ],
    ],
];
