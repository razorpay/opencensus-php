<?php

namespace RZP\Tests\Functional\VirtualAccount;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'createVAWithAllowedPayer' => [
        'allowed_payers' => [
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ]
            ],
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'UTIB0000013',
                    'account_number' => '000123499988'
                ],
            ],
        ],
    ],

    'testCreateVirtualBankAccountWithTpv' => [
        'name'           => 'Test virtual account',
        'entity'         => 'virtual_account',
        'status'         => 'active',
        'description'    => 'VA for tests',
        'receivers'      => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
        'allowed_payers' => [
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ]
            ],
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'UTIB0000013',
                    'account_number' => '000123499988'
                ]
            ],
        ],
    ],

    'testCreateVirtualVpaWithTpv' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account validation is only applicable on bank account as a receiver type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_RECEIVER_TYPES_NOT_ALLOWED,
        ],
    ],

    'testWebhookVirtualAccountCreatedWithAllowedPayers' => [
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
                        'name'           => 'Test virtual account',
                        'entity'         => 'virtual_account',
                        'status'         => 'active',
                        'description'    => 'VA for tests',
                        'notes'          => [
                            'a' => 'b',
                        ],
                        'amount_paid'    => 0,
                        'customer_id'    => null,
                        'receivers'      => [
                            [
                                'name'      => 'Test virtual account',
                                'entity'    => 'bank_account',
                                'ifsc'      => 'RAZR0000001',
                                'bank_name' => null,
                            ],
                        ],
                        'allowed_payers' => [
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'HDFC0000053',
                                    'account_number' => '765432123456789'
                                ]
                            ],
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'UTIB0000013',
                                    'account_number' => '000123499988'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testWebhookVirtualAccountClosedWithAllowedPayers' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event'    => 'virtual_account.closed',
            'contains' => [
                'virtual_account',
            ],
            'payload'  => [
                'virtual_account' => [
                    'entity' => [
                        'name'           => 'Test virtual account',
                        'entity'         => 'virtual_account',
                        'status'         => 'closed',
                        'description'    => 'VA for tests',
                        'notes'          => [
                            'a' => 'b',
                        ],
                        'amount_paid'    => 0,
                        'customer_id'    => null,
                        'receivers'      => [
                            [
                                'name'   => 'Test virtual account',
                                'entity' => 'bank_account',
                                'ifsc'   => 'RAZR0000001',
                            ],
                        ],
                        'allowed_payers' => [
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'HDFC0000053',
                                    'account_number' => '765432123456789',
                                ]
                            ],
                            [
                                'type'         => 'bank_account',
                                'bank_account' => [
                                    'ifsc'           => 'UTIB0000013',
                                    'account_number' => '000123499988',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testFetchVirtualAccountWithAllowedPayer' => [
        'name'           => 'Test virtual account',
        'entity'         => 'virtual_account',
        'status'         => 'active',
        'description'    => 'VA for tests',
        'receivers'      => [
            [
                'entity' => 'bank_account',
                'ifsc'   => 'RAZR0000001',
                'name'   => 'Test virtual account'
            ],
        ],
        'allowed_payers' => [
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ]
            ],
            [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'UTIB0000013',
                    'account_number' => '000123499988'
                ]
            ],
        ],
    ],

    'testCreateVirtualAccountWithInvalidAllowedPayerType' => [
        'request'   => [
            'allowed_payers' => [
                [
                    'type'         => 'vpa',
                    'bank_account' => [
                        'ifsc'           => 'HDFC0000053',
                        'account_number' => '765432123456789'
                    ]
                ],
                [
                    'type'         => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'UTIB0000013',
                        'account_number' => '000123499988'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'vpa is not an allowed payer type.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateVirtualAccountWithMissingAllowedPayerDetails' => [
        'request'   => [
            'allowed_payers' => [
                [
                    'type' => 'bank_account',
                ],
                [
                    'type'         => 'bank_account',
                    'bank_account' => [
                        'ifsc'           => 'UTIB0000013',
                        'account_number' => '000123499988'
                    ],
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The bank account field is required when type is bank_account.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddIciciVpaReceiverToVaWithoutTpv' => [
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
                'entity' => 'vpa',
                'handle' => 'icici',
            ],
        ],
    ],

    'testAddTpvToExistingVirtualAccount' => [
        'request'  => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'name'           => 'Test virtual account',
                'entity'         => 'virtual_account',
                'status'         => 'active',
                'description'    => 'VA for tests',
                'receivers'      => [
                    [
                        'entity' => 'bank_account',
                        'ifsc'   => 'RAZR0000001',
                        'name'   => 'Test virtual account'
                    ],
                ],
                'allowed_payers' => [
                    [
                        'type'         => 'bank_account',
                        'bank_account' => [
                            'ifsc'           => 'HDFC0000053',
                            'account_number' => '765432123456789'
                        ]
                    ],

                ],
            ],
        ],
    ],

    'testAddTpvToExistingVirtualAccountWithoutIfsc' => [
        'request'   => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'account_number' => '765432123456789'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'IFSC is required for account validation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_IFSC_REQUIRED,
        ]
    ],

    'testAddTpvToExistingVirtualAccountWithInvalidIfsc' => [
        'request'   => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'account_number' => '765432123456789',
                    'ifsc'           => 'HDFC00000',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The ifsc must be 11 characters.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_INVALID_IFSC,
        ]
    ],

    'testAddTpvToExistingVirtualAccountWithoutBankAccountNumber' => [
        'request'   => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc' => 'HDFC0000053' ,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bank account number is required for account validation.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_BANK_ACCOUNT_REQUIRED,
        ],
    ],

    'testAddTpvToExistingVirtualAccountWithTpv' => [
        'request'  => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'SBIN0000002',
                    'account_number' => '765432123456789'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'name'           => 'Test virtual account',
                'entity'         => 'virtual_account',
                'status'         => 'active',
                'description'    => 'VA for tests',
                'receivers'      => [
                    [
                        'entity' => 'bank_account',
                        'ifsc'   => 'RAZR0000001',
                        'name'   => 'Test virtual account'
                    ],
                ],
                'allowed_payers' => [
                    [
                        'type'         => 'bank_account',
                        'bank_account' => [
                            'ifsc'           => 'HDFC0000053',
                            'account_number' => '765432123456789'
                        ]
                    ],
                    [
                        'type'         => 'bank_account',
                        'bank_account' => [
                            'ifsc'           => 'UTIB0000013',
                            'account_number' => '000123499988'
                        ]
                    ],
                    [
                        'type'         => 'bank_account',
                        'bank_account' => [
                            'ifsc'           => 'SBIN0000002',
                            'account_number' => '765432123456789'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testDuplicateAddTpvToExistingVirtualAccountWithTpv' => [
        'request'   => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Allowed payer details already exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_ADD_ALLOWED_PAYER_ALREADY_EXISTS,
        ],
    ],

    'testAddTpvToClosedVirtualAccount' => [
        'request'   => [
            'url'     => '/virtual_accounts/{va_id}/allowed_payers',
            'method'  => 'post',
            'content' => [
                'type'         => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'HDFC0000053',
                    'account_number' => '765432123456789'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The virtual account is closed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED,
        ],
    ],

    'testDeleteTpvForClosedVirtualAccount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The virtual account is closed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_CLOSED,
        ],
    ],

    'testDeleteTpvForVirtualAccountWithInvalidAllowedPayerId' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The allowed_payer id provided is incorrect.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_ALLOWED_PAYER_ID,
        ],
    ],
];

