<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
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

    'testCreateVirtualAccountForOrder' => [
        'name'            => 'Test Merchant',
        'entity'          => 'virtual_account',
        'status'          => 'active',
        'amount_expected' => 1000000,
        'amount_paid'     => 0,
        'customer_id'     => NULL,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'RAZR0000001',
                'bank_name'      => NULL,
                'name'           => 'Test Merchant',
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
        'customer_id'     => NULL,
        'receivers'       => [
            [
                'entity'         => 'bank_account',
                'ifsc'           => 'RAZR0000001',
                'bank_name'      => NULL,
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
                        'amount_expected' => NULL,
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

    'testPayVirutalAccountOnBankingBalance' => [
        'request' => [
            'url'     => '/ecollect/validate',
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
];
