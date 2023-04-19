<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testCreateVirtualAccount' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testCreateVirtualAccount401ForOrgMerchantFeatureFlag' => [
        'error' => [
            'code'          => "BAD_REQUEST_ERROR",
            'description'   =>"You do not have permission to access this feature.",
        ]
    ],

    'testCreateVirtualAccountForOrgMerchantFeatureFlag' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testFetchVirtualAccount401ForOrgMerchantFeatureFlag' => [
        'error' => [
            'code'          => "BAD_REQUEST_ERROR",
            'description'   =>"You do not have permission to access this feature.",
        ]
    ],

    'testFetchVirtualAccountForOrgMerchantFeatureFlag' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testFetchMultipleVirtualAccount401ForOrgMerchantFeatureFlag' => [
        'error' => [
            'code'          => "BAD_REQUEST_ERROR",
            'description'   =>"You do not have permission to access this feature.",
        ]
    ],

    'testFetchMultipleVirtualAccountForOrgMerchantFeatureFlag' => [
        'entity' => 'collection',
        'count'  => 2,
        'items'  => [
            [
                'name'            => 'Second VA',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => 'VA for tests',
            ],
            [
                'name'            => 'First VA',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => 'VA for tests',
            ],
        ],
    ],

    'testFetchPaymentsForVirtualAccountForOrgMerchantFeatureFlag' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 5000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'bank_transfer',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'Test bank transfer',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testFetchPaymentsForVirtualAccount401ForOrgMerchantFeatureFlag' => [
        'error' => [
            'code'          => "BAD_REQUEST_ERROR",
            'description'   =>"You do not have permission to access this feature.",
        ]
    ],

    'testCreateVirtualAccountWithOrderIdFeatureEnabled' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testCreateVirtualAccountWithCloseBy' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testCreateVirtualAccountWithInvalidCloseBy' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'description' => 'VA for tests',
                'close_by'     => 1560584249,
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'close_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateVirtualAccountPartnerAuth' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testCreateVirtualAccountForOrder' => [
        'name'            => 'Test Merchant',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'amount_expected' => 1000000,
        'amount_paid'     => 0,
        'customer_id'     => null,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'RAZR0000001',
                'bank_name'      => null,
                'name'           => 'Test Merchant',
            ],
        ],
    ],

    'testCreateHdfcEcmsVirtualAccount' => [
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'amount_expected' => 1000000,
        'amount_paid'     => 0,
        'customer_id'     => null,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'HDFC0000113',
                'bank_name'      => 'HDFC Bank',
            ],
        ],
    ],

    'testVirtualAccountExpirySetting' => [
        'request'  => [
            'url'     => '/virtual_accounts/setting/expiry',
            'method'  => 'post',
            'content' => [
                'va_expiry_offset'  => 24
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testVirtualAccountExpirySettingFetch' => [
        'request'  => [
            'url'     => '/virtual_accounts/setting/expiry',
            'method'  => 'get'
        ],
        'response' => [
            'content' => [
                'expiry' => 24
            ],
        ],
    ],

    'testVirtualAccountExpirySettingForAdminDashboard' => [
        'request'  => [
            'url'     => '/admins/virtual_accounts/setting/expiry',
            'method'  => 'post',
            'content' => [
                'va_expiry_offset'  => 24,
                'merchant_id' => 10000000000000
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testVirtualAccountExpirySettingForAdminDashboardNegative' => [
        'request'  => [
            'url'     => '/admins/virtual_accounts/setting/expiry',
            'method'  => 'post',
            'content' => [
                'va_expiry_offset'  => 24,
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],


    'testVirtualAccountExpirySettingForAdminDashboardNegative2' => [
        'request'  => [
            'url'     => '/admins/virtual_accounts/setting/expiry',
            'method'  => 'post',
            'content' => [
                'va_expiry_offset'  => 24,
                'merchant_id' => 10000000000000
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED
        ],
    ],

    'testVirtualAccountExpirySettingFetchForAdminDashboard' => [
        'request'  => [
            'url'     => '/admins/virtual_accounts/setting/expiry',
            'method'  => 'get',
            'content' => [
                'merchant_id' => 10000000000000
            ]
        ],
        'response' => [
            'content' => [
                "expiry" => 12
            ],
        ],
    ],

    'testFetchOrderWithVirtualAccountExpand' => [
        'request' => [
            'method'  => 'GET',
            'content' => [
                'expand' => [
                    'virtual_account',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'order',
                'amount'          => 1000000,
                'amount_paid'     => 0,
                'amount_due'      => 1000000,
                'currency'        => 'INR',
                'offer_id'        => null,
                'status'          => 'created',
                'attempts'        => 0,
                'notes'           => [],
                'virtual_account' => [
                    'name'            => 'Test Merchant',
                    'entity'          => 'virtual_account',
                    'status'          => 'active',
                    'description'     => null,
                    'amount_expected' => 1000000,
                    'notes'           => [],
                    'amount_paid'     => 0,
                    'customer_id'     => null,
                    'receivers'       => [
                        [
                            'entity'    => 'bank_account',
                            'ifsc'      => 'RAZR0000001',
                            'bank_name' => null,
                            'name'      => 'Test Merchant',
                        ],
                    ],
                    'close_by'        => null,
                    'closed_at'       => null,
                ],
            ],
        ]
    ],

    'testFetchOrderWithoutVirtualAccountExpand' => [
        'request' => [
            'method'  => 'GET',
            'content' => [
                'expand' => [
                    'virtual_account',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'order',
                'virtual_account' => null,
            ],
        ],
    ],

    'testVaOfflineQRGeneration' => [
        'request' => [
            'url'     => '/virtual_accounts/offline_qr',
            'method'  => 'POST',
            'content' => [
                'currency'      => 'INR',
                'amount'        => 100,
                'receipt'       => 'test_data',
                'description'   => 'description',
                'notifications' => [
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'createOfflineQrVA' => [
        'url'     => '/virtual_accounts/offline_qr',
        'method'  => 'POST',
        'content' => [
            'currency'      => 'INR',
            'amount'        => 100,
            'receipt'       => 'test_data',
            'description'   => 'description',
            'notifications' => [
            ],
        ],
    ],

    'testFetchOrderWithVirtualAccountNoExpand' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'          => 'order',
            ],
        ],
    ],

    'testCreateVirtualAccountForOrderCustomerFeeBearer' => [
        'name'            => 'Test Merchant',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'amount_expected' => 1005900,
        'notes'           => [],
        'amount_paid'     => 0,
        'customer_id'     => null,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'RAZR0000001',
                'bank_name'      => null,
                'name'           => 'Test Merchant',
            ],
        ],
    ],

    'testCreateVirtualAccountInvalidReceiverTypes' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'random_receiver_type',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more of the given receiver types is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
        ],
    ],

    'testCreateVirtualAccountWithBankAccountNameOption' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                    'bank_account' => [
                        'name' => 'lalala_what'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'virtual_account',
                'receivers' => [
                    [
                        'entity' => 'bank_account',
                        'name'   => 'lalala_what'
                    ]
                ],
            ],
        ]
    ],

    'testCreateVirtualAccountValidationFailure' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'description' => 'VA for tests',
                'receivers'   => 'This is the best receiver ever.',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The receivers must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateVirtualAccountCrypto' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Creation of new virtual accounts is '.
                                        'currently blocked for your account.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ACCOUNT,
        ],
    ],

    'testCreateVirtualAccountWithBharatQr' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
            ],
        ],
    ],

    'testCreateVirtualAccountWithBharatQrWithAmount' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'amount_expected' => 10000,
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
            ],
        ],
    ],

    'testFetchVirtualAccount' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testFetchVirtualAccounts' => [
        'entity' => 'collection',
        'count'  => 2,
        'items'  => [
            [
                'name'            => 'Second VA',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => 'VA for tests',
            ],
            [
                'name'            => 'First VA',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => 'VA for tests',
            ],
        ],
    ],

    'testFetchVirtualAccountByCustomerEmail' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'            => 'Test virtual account',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => 'VA for tests',
                'customer_id'     => 'cust_100000customer',
            ],
        ],
    ],

    'testFetchPaymentsForVirtualAccount' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 5000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'bank_transfer',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'Test bank transfer',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testFetchPaymentsForVirtualAccountForQrCode' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 200,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'card',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'Bharat Qr Payment',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testVirtualAccountCreateRequestUpdate' => [
        'descriptorWithNumeric' => [
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Descriptor cannot be used with your account.',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
    ],

    'testCreateVirtualAccountDescriptorInvalidLength' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid length for descriptor.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_DESCRIPTOR_LENGTH,
        ],
    ],

    'testCreateVirtualAccountForInvalidCustomer' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'qwerty is not a valid id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCloseVirtualAccountInBulk' => [
        'request' => [
            'url' => '/virtual_accounts/close/bulk',
            'method' => 'post',
        ],
        'response' => [
            'status_code' => 200,
        ],
    ],

    'testEditBulkVirtualAccounts' => [
        'request'  => [
            'content' => [
                [
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'virtual_account_id'    => 'ASDF1234567890',
                    'close_by'              => '2023-12-12 12:12',
                ],
                [
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaN',
                    'virtual_account_id'    => 'ASDF1234567890',
                    'close_by'              => '2023-12-12 12:12',
                ],
                [
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaO',
                    'virtual_account_id'    => 'ASDF1234567890',
                    'close_by'              => '2023-12-12 12:12',
                ],
            ],
            'url'       => '/virtual_accounts/edit/bulk',
            'method'    => 'post',
        ],
        'response' => [
            'content'   => [
                'entity'    => 'collection',
                'count'     => 3,
                'items'     => [
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaM',
                        'success'           => true,
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaN',
                        'success'           => false,
                        'error'             => ['code' => 'BAD_REQUEST_INVALID_ID', 'description' => 'The id provided does not exist']
                    ],
                    [
                        'idempotency_key'   => 'batch_DZtFGiJXmcdLaO',
                        'success'           => false,
                        'error'             => ['code' => 'BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_EXPIRY_DATE', 'description' => 'Expiry Date is not a valid date.']
                    ],
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testEditBulkVirtualAccountsWithoutFeature' => [
        'request'  => [
            'content' => [
                [
                    'idempotency_key'       => 'batch_DZtFGiJXmcdLaM',
                    'virtual_account_id'    => 'ASDF1234567890',
                    'close_by'              => '2023-12-12 12:12',
                ]
            ],
            'url'       => '/virtual_accounts/edit/bulk',
            'method'    => 'post',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Feature not enabled for merchant'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testCreateVirtualAccountWithIdenticalDescriptor' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'An active virtual account with the same' .
                                     ' descriptor already exists for your account.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR,
        ],
    ],

    'testWebhookVirtualAccountCredited' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.credited',
            'contains' => [
                'payment',
                'virtual_account',
                'bank_transfer',
            ],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 10000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'method'            => 'bank_transfer',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'Test bank transfer',
                        'email'             => null,
                        'contact'           => null,
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
                'virtual_account' => [
                    'entity' => [
                        'name'            => 'Test virtual account',
                        'entity'          => 'virtual_account',
                        'status'          => 'active',
                        'description'     => 'VA for tests',
                        'amount_expected' => null,
                        'notes' => [
                            'a' => 'b',
                        ],
                        'amount_paid' => 10000,
                        'customer_id' => null,
                        'receivers' => [
                            [
                                'name'      => 'Test virtual account',
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'bank_name' => null,
                            ],
                        ],
                    ],
                ],
                'bank_transfer' => [
                    'entity' => [
                        'entity'             => 'bank_transfer',
                        'mode'               => 'NEFT',
                        'amount'             => 10000,
                        'payer_bank_account' => [
                            'entity' => 'bank_account',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testWebhookVirtualAccountCreditedForBharatQr' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.credited',
            'contains' => [
                'payment',
                'virtual_account',
            ],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 200,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'order_id'          => null,
                        'invoice_id'        => null,
                        'method'            => 'card',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'Bharat Qr Payment',
                        'email'             => null,
                        'contact'           => null,
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
                'virtual_account' => [
                    'entity' => [
                        'name'            => 'Test virtual account',
                        'entity'          => 'virtual_account',
                        'status'          => 'active',
                        'description'     => 'VA for tests',
                        'amount_expected' => null,
                        'notes' => [
                            'a' => 'b',
                        ],
                        'amount_paid' => 200,
                        'customer_id' => null,
                        'receivers' => [
                            [
                                'entity'    => 'qr_code',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testWebhookVirtualAccountCreated' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.created',
            'contains' => [
                'virtual_account',
            ],
            'payload' => [
                'virtual_account' => [
                    'entity' => [
                        'name'            => 'Test virtual account',
                        'entity'          => 'virtual_account',
                        'status'          => 'active',
                        'description'     => 'VA for tests',
                        'notes' => [
                            'a' => 'b',
                        ],
                        'amount_paid' => 0,
                        'customer_id' => null,
                        'receivers' => [
                            [
                                'name'      => 'Test virtual account',
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'bank_name' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testWebhookVirtualAccountClosed' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'virtual_account.closed',
            'contains' => [
                'virtual_account',
            ],
            'payload' => [
                'virtual_account' => [
                    'entity' => [
                        'name'            => 'Test virtual account',
                        'entity'          => 'virtual_account',
                        'status'          => 'closed',
                        'description'     => 'VA for tests',
                        'amount_expected' => null,
                        'notes' => [
                            'a' => 'b',
                        ],
                        'amount_paid' => 0,
                        'customer_id' => null,
                        'receivers' => [
                            [
                                'name'      => 'Test virtual account',
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'bank_name' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPayVirutalAccountOnBankingBalance' => [
        'request' => [
            'url'     => '/ecollect/validate/test',
            'method'  => 'post',
            'content' => [
                'payer_account'  => '7654321234567',
                'payer_ifsc'     => 'HDFC0000001',
                'mode'           => 'neft',
                'transaction_id' => 'AYDIC1W48ZXUVGLE0H6FQC',
                'time'           => 1543052014,
                'amount'         => 25,
                'description'    => 'Test bank transfer',
                'payee_account'  => '2224440041626905',
                'payee_ifsc'     => 'RZPB0000000',
            ],
        ],
        'response' => [
            'content' => [
                'valid'          => true,
                'message'        => null,
                'transaction_id' => 'AYDIC1W48ZXUVGLE0H6FQC',
            ],
        ],
    ],

    'testFetchVirtualAccountsMustNotIncludeBankingVAs' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'        => 'Test virtual account',
                'entity'      => 'virtual_account',
                'status'      => 'active',
                'description' => 'VA for tests',
                'receivers'   => [
                    [
                        'entity'    => 'bank_account',
                        // This ifsc is for vas on primary balance.
                        'ifsc'      => 'RAZR0000001',
                        'bank_name' => null,
                        'name'      => 'Test virtual account',
                    ],
                ],
            ],
        ],
    ],

    'testFetchVirtualAccountsMultiple' => [
        'input' => [
            'input1' => [
                'description' => 'after ES',
            ],
            'input2' => [
                'email' => 'test@razorpay.com',
                'contact' => '1234567890',
            ]
        ],
        'output' => [
            'entity' => 'collection',
            'count'  => 1,
            'items'  => [
                [
                    'name'        => 'Test virtual account',
                    'entity'      => 'virtual_account',
                    'status'      => 'active',
                    'description' => 'Testing VA fetch after ES sync',
                    'receivers'   => [
                        [
                            'entity'    => 'bank_account',
                            // This ifsc is for vas on primary balance.
                            'ifsc'      => 'RAZR0000001',
                            'bank_name' => null,
                            'name'      => 'Test virtual account',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchVirtualAccountsMultipleByPayeeAccount' => [
        'input' => [
            'input1' => [
                'payee_account' => '2323230087430709',
            ],
            'input2' => [
                'payee_account' => 'rzr.payto000007451209527@icici',
            ]
        ],
        'output' => [
            'output1' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'name'        => 'bank account search',
                        'entity'      => 'virtual_account',
                        'status'      => 'active',
                        'receivers'   => [
                            [
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'bank_name' => null,
                            ],
                        ],
                    ],
                ],
            ],
            'output2' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'name'        => 'vpa search',
                        'entity'      => 'virtual_account',
                        'status'      => 'active',
                        'receivers'   => [
                            [
                                'entity'    => 'vpa',
                            ],
                        ],
                    ],
                ],
            ]
        ],
    ],

    'testCreateVirtualAccountWithVpa' => [
        'name'        => 'Test virtual account',
        'entity'      => 'virtual_account',
        'status'      => 'active',
        'description' => 'VA for tests',
        'receivers'   => [
            [
                "entity"   => "vpa",
                "username" => "rzr.payto00000virtualvpa",
                "handle"   => "icici",
                "address"  => "rzr.payto00000virtualvpa@icici"
            ],
        ],
    ],

    'testAddVpaToExistingVirtualAccount' => [
        'name'        => 'Test virtual account',
        'entity'      => 'virtual_account',
        'status'      => 'active',
        'description' => 'VA for tests',
        'receivers'   => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
            [
                "entity"   => "vpa",
                "username" => "rzr.payto00000virtualvpa",
                "handle"   => "icici",
                "address"  => "rzr.payto00000virtualvpa@icici"
            ],
        ],
    ],

    'testWebhookVirtualAccountCreatedForVpa' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event'    => 'virtual_account.created',
            'contains' => [
                'virtual_account',
            ],
            'payload'  => [
                'virtual_account' => [
                    'entity' => [
                        'name'        => 'Test virtual account',
                        'entity'      => 'virtual_account',
                        'status'      => 'active',
                        'description' => 'VA for tests',
                        'notes'       => [],
                        'amount_paid' => 0,
                        'customer_id' => null,
                        'receivers'   => [
                            [
                                "entity"   => "vpa",
                                "username" => "rzr.payto00000virtualvpa",
                                "handle"   => "icici",
                                "address"  => "rzr.payto00000virtualvpa@icici"
                            ],
                        ],
                        "close_by"    => null,
                        "closed_at"   => null,
                    ],
                ],
            ],
        ],
    ],

    'testAddVpaToExistingVAWithVpa' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Receiver type is already present for the virtual account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_ALREADY_PRESENT,
        ],
    ],

    'testCreateVirtualAccountWithoutCustomerIdWithCustomerDetails' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'customer' =>[
                    'name' => 'test',
                    'contact' => '1234567890',
                    'email' => 'abc@abc.com'
                ],
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'virtual_account',
                'description' => 'VA for tests',
                'status' => 'active',
                'receivers' => [
                    [
                        'entity' => 'bank_account',
                        'ifsc' => 'RAZR0000001',
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVirtualAccountWithoutCustomerIdWithoutCustomerDetails' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'customer' =>[],
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'virtual_account',
                'description' => 'VA for tests',
                'status' => 'active',
                'receivers' => [
                    [
                        'entity' => 'bank_account',
                        'ifsc' => 'RAZR0000001',
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVirtualAccountWithCustomerIdWithCustomerDetails' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100022customer',
                'customer' =>[
                    'name' => 'test',
                    'contact' => '1234567890',
                    'email' => 'abc@abc.com'
                ],
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'virtual_account',
                'description' => 'VA for tests',
                'status' => 'active',
                'receivers' => [
                    [
                        'entity' => 'bank_account',
                        'ifsc' => 'RAZR0000001',
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVirtualAccountInvalidCustomerEmail' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'customer' =>[
                    'name' => 'test',
                    'contact' => '1234567890',
                    'email' => 'abc'
                ],
                'description' => 'VA for tests',
                'receivers'   => [
                    'types' => [
                        'bank_account',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email must be a valid email address.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOfflineQrCloseBy' => [
        'request' => [
            'url' =>'/virtual_accounts',
            'method' => 'post',
            'content' => [
                'description' => 'VA for tests',
                'close_by' => '1577644500',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'close_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOfflineVACreation' => [
        'request' => [
            'url' =>'/virtual_accounts/offline_qr',
            'method' => 'post',
            'content' => [
                'description' => 'VA for tests',
                'amount'      => 100,
                'receipt'     => 'OfflineQrVa',
                'currency'    => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'virtual_account',
                'status' => 'active',
                'closed_at' => null
            ],
        ]
    ],

    'testFetchVirtualAccountBankingMultipleWithBalanceId' => [
        'request' => [
            'url' =>'/virtual_accounts/banking/account?balance_id=10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'name' => "Test Merchant",
                        'entity' => "virtual_account",
                        'status' => "active",
                    ],
                ],
            ],
        ],
    ],

    'testCreateVirtualAccountForBanking' => [
        'request' => [
            'url' => '/virtual_accounts/banking',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'name'            => 'Test Merchant',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => null,
                'receivers'  => [
                    [
                        'entity' => 'bank_account',
                        'ifsc'   => 'YESB0CMSNOC',
                        'name'   => 'Test Merchant'
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVirtualAccountForBankingWithBody' => [
        'request' => [
            'url' => '/virtual_accounts/banking',
            'method' => 'post',
            'content' => [
                'name'  => 'Akshay Goyal'
            ],
        ],
        'response' => [
            'content' => [
                'name'            => 'Akshay Goyal',
                'entity'          => 'virtual_account',
                'status'          => 'active',
                'description'     => null,
                'receivers'  => [
                    [
                        'entity' => 'bank_account',
                        'ifsc'   => 'YESB0CMSNOC',
                        'name'   => 'Akshay Goyal'
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateVirtualAccountForBankingWithoutFeature' => [
        'request' => [
            'url' => '/virtual_accounts/banking',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCreateVirtualAccountForBankingWithoutBusinessBankingFlagEnabled' => [
        'request' => [
            'url' => '/virtual_accounts/banking',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
        ],
    ],

    'testCreateVirtualAccountInBulkForBanking' => [
        'request' => [
            'url' => '/virtual_accounts/banking/bulk',
            'method' => 'post',
            'content' => [
                "merchant_ids" => ["10000000000000", "10000000000001"]
            ],
        ],
        'response' => [
            'content' => [
                'success'  => 1,
                'failure'  => 1,
                'failures'  => ['10000000000001'],
            ],
            'status_code' => 200,
        ],
    ],

    'testCloseVirtualAccountInBulkForBanking' => [
        'request' => [
            'url' => '/virtual_accounts/banking/close/bulk',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'success'  => 2,
                'failure'  => 1,
                'failures'  => ['va_10000000000000'],
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchVirtualAccountPayments' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 5000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'bank_transfer',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'Test bank transfer',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testPaymentsFetchMultiple' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 5000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'bank_transfer',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'Test bank transfer',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testCreateVirtualAccountForVpaWithCustomPrefix' => [
        'name'        => 'Test virtual account',
        'entity'      => 'virtual_account',
        'status'      => 'active',
        'description' => 'VA for tests',
        'receivers'   => [
            [
                "entity"   => "vpa",
                "username" => "rzr.paytorazorvirtualvpa",
                "handle"   => "icici",
                "address"  => "rzr.paytorazorvirtualvpa@icici"
            ],
        ],
    ],

    'testCreateVirtualAccountWithVpaForIcici' => [
        'name'        => 'Test virtual account',
        'entity'      => 'virtual_account',
        'status'      => 'active',
        'description' => 'VA for tests',
        'receivers'   => [
            [
                "entity"   => "vpa",
                "username" => "rzr.payto00000virtualvpa",
                "handle"   => "icici",
                "address"  => "rzr.payto00000virtualvpa@icici"
            ],
        ],
    ],

    'testCreateVirtualAccountWithVpaForIciciAndCustomPrefix' => [
        'name'        => 'Test virtual account',
        'entity'      => 'virtual_account',
        'status'      => 'active',
        'description' => 'VA for tests',
        'receivers'   => [
            [
                "entity"   => "vpa",
                "username" => "rzr.paytorazorvirtualvpa",
                "handle"   => "icici",
                "address"  => "rzr.paytorazorvirtualvpa@icici"
            ],
        ],
    ],

    'testMerchantVAUpdateExpiry' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '31-12-2028 23:00',
            ]
        ],
        'response' => [
            'content' => [
                'close_by' => 1861896600,
            ],
            'status_code' => 200,
        ]
    ],

    'testMerchantVAUpdateInvalidExpiry' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '32-13-2021',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Expiry Date is not a valid date.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_EXPIRY_DATE,
        ],
    ],

    'testMerchantVAUpdateInvalidFormat' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '2022-11-3',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Expiry Date is not a valid date.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_EXPIRY_DATE,
        ],
    ],

    'testMerchantVAUpdateExpiryLessThanCurrent' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '30-11-2021 00:00',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Expiry Date cannot be less than system date.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_EXPIRY_LESS_THAN_CURRENT_TIME,
        ],
    ],

    'testMerchantVAUpdateClosed' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '31-12-2028',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The virtual account is closed.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED,
        ],
    ],

    'testMerchantVAUpdateFeatureNotEnabled' => [
        'request' => [
            'url' =>'/merchant/virtual_accounts/{id}',
            'method' => 'patch',
            'content' => [
                'close_by' => '1-2-2028',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.'
                ],
            ],
            'status_code' => 400,
        ],
        ],

    'testCreateVAFromCheckoutForOffline' => [
    'request'  => [
        'convertContentToString' => false,
        'url'                    => '/orders',
        'method'                 => 'POST',
        'content'                => [
            'amount'           => 1000,
            'currency'         => 'INR',
            'receipt'          => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc',
            ],
        ],
    ],
    'response' => [
        'content' => [
            'amount'           => 1000,
            'currency'         => 'INR',
            'receipt'          => 'rec1',
            'customer_additional_info' => [
                'property_id' => '12345',
                'property_value' => 'abc',
            ],
        ],
    ],
],

    'testCreateVAFromCheckoutForOfflineWithoutMetadata' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
            ],
        ],
    ],


    'testValidateOfflineChallan' => [
        'request' => [
            'convertContentToString'    => false,
            'url'                       => '/validate/ecollect/offline',
            'method'                    => 'POST'
        ],
    ],

    'testValidateOfflineChallanWithoutCert' => [
        'request' => [
            'convertContentToString'    => false,
            'url'                       => '/validate/ecollect/offline',
            'method'                    => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED,
        ],
    ],

    'testCreateVirtualAccountWithTerminalCaching' => [
        'name'            => 'Test virtual account',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'description'     => 'VA for tests',
        'receivers'  => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
    ],

    'testCheckCustomerInfoReturned' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_id'      => 'cust_100000customer',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc',
                ],
            ],
        ],
    ],


    'testCheckCustomerInfoNotReturned' => [
        'request'  => [
            'convertContentToString' => false,
            'url'                    => '/orders',
            'method'                 => 'POST',
            'content'                => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_id'      => 'cust_100000customer',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'amount'           => 1000,
                'currency'         => 'INR',
                'receipt'          => 'rec1',
                'customer_additional_info' => [
                    'property_id' => '12345',
                    'property_value' => 'abc',
                ],
            ],
        ],
    ],
    'testCreateVirtualAccountWithDefaultExpiry' => [
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'amount_expected' => 1000000,
        'amount_paid'     => 0,
        'customer_id'     => null,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'HDFC0000113',
                'bank_name'      => 'HDFC Bank',
            ],
        ],
    ],

    'testCreateVirtualAccountWithQrCodeReceiver' => [
        'request' => [
            'url' => '/virtual_accounts',
            'method' => 'post',
            'content' => [
                'description' => 'VA for qr as receiver',
                'receivers'   => [
                    'types' => [
                        'qr_code',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'QR receiver type is not supported.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_QR_RECEIVER_TYPE_IS_NOT_SUPPORTED,
        ],
    ],

    'testUpdateVirtualAccountExpirySettingForHDFCLife' => [
        'request'  => [
            'url'     => '/virtual_accounts/setting/expiry',
            'method'  => 'post',
            'content' => [
                'va_expiry_offset'  => 24
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],
];
