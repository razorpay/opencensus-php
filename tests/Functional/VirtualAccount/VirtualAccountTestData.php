<?php

namespace RZP\Tests\Functional\BankTransfer;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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

    'testCreateVirtualAccountWithDescriptor' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Descriptor field cannot be used as ' .
                                     'merchant handle is not set for your account.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DESCRIPTOR_SANS_HANDLE,
        ],
    ],

    'testVirtualAccountCreateRequestUpdate' => [
        'descriptorWithNumeric' => [
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Descriptor cannot be used for numeric accounts.',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        'descriptorWithAlphaWithoutHandle' => [
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Descriptor cannot be used as merchant handle is not set.',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        'descriptorWithNumericWithHandle' => [
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Descriptor cannot be used for numeric accounts.',
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

    'testCreateVirtualAccountDescriptorLengths' => [
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

    'testWebhookOnVirtualAccountPay' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'payment.captured',
            'contains' => ['payment'],
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
            ],
        ],
    ],
];
