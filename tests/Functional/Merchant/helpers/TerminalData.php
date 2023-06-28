<?php

use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use \RZP\Models\Payment\Gateway;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;

return [

    'testProxyFetchMerchantTerminals' => [
        'request' => [
            'method' => 'GET',
            'url' => '/proxy/merchant/terminals',
            'content' => ['gateway' => 'wallet_paypal']
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count'  => 1,
                'items'  => [
                    [
                    'entity'   => "terminal",
                    'status'   => "activated",
                    'enabled'  => true,
                    'mpan'     => [
                        'mc_mpan'    => NULL,
                        'rupay_mpan' => NULL,
                        'visa_mpan'  => NULL
                     ],
                    'notes' => NULL,
                    ]
                ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testProxyTerminalOnboardStatus' => [
        'request' => [
            'method' => 'GET',
            'url' => '/proxy/terminal/onboard/status',
            'content' => ['gateway' => 'wallet_paypal']
        ],
        'response' => [
            'content' => [
                [
                    'message'   => "test_message",
                    'terminal'   => ["id"=>"10000000000000", "gateway"=> "hitachi"],
                ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testProxyFetchMerchantTerminalsWithNoTerminalInApi' => [
        'request' => [
            'method' => 'GET',
            'url' => '/proxy/merchant/terminals',
            'content' => [
                'gateway' => 'wallet_paypal'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count'  => 1,
                'items'  => [
                    [
                        'id'       => "ETbhgqkBRIiAkt",
                        'entity'   => "terminal",
                        'status'   => "requested",
                        'enabled'  => false,
                        'mpan'     => [
                            'mc_mpan'    => '',
                            'rupay_mpan' => '',
                            'visa_mpan'  => ''
                        ],
                        'notes' => NULL,
                    ]
                ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testAssignTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'                  => '4567',
                'emi_subvention'            => 'merchant',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'hdfc',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignTerminalWithInvalidGatewayAcquirer' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'icic',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'                  => '4567',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'icic is not a valid acquirer for hdfc',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAssignHitachiTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'category'                  => '4567',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignPaysecureTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'paysecure',
                'gateway_acquirer'          => 'axis',
                'card'                      => 1,
                'currency'                  => ["INR"],
                'gateway_merchant_id'       => '123456789012345',
                'gateway_terminal_id'       => '12345678',
                'mode'                      => 3,
                'status'                    => 'activated',
                'enabled'                   => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
            'status_code' =>400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAssignPaysecureTerminalNonRzpOrg' => [
        'request' => [
            'content' => [
                'gateway'                   => 'paysecure',
                'gateway_acquirer'          => 'axis',
                'card'                      => 1,
                'currency'                  => ["INR"],
                'gateway_merchant_id'       => '123456789012345',
                'gateway_terminal_id'       => '12345678',
                'mode'                      => 3,
                'status'                    => 'activated',
                'enabled'                   => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreatePaysecureTerminal' => [
        'request' => [
            'content' => [
                'id'                        => '12345678901234',
                'gateway'                   => 'paysecure',
                'gateway_acquirer'          => 'axis',
                'card'                      => 1,
                'currency'                  => ["INR"],
                'gateway_merchant_id'       => '123456789012345',
                'gateway_terminal_id'       => '12345678',
                'mode'                      => 3,
                'status'                    => 'activated',
                'enabled'                   => 1,
                'type'                      => [
                    'non_recurring'                 => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'axis',
                'gateway_merchant_id' => '123456789012345',
                'gateway_terminal_id' => '12345678',
                'enabled'             => true
            ]
        ]
    ],

    'testCreatePaysecureTerminalNonRzpOrg' => [
        'request' => [
            'content' => [
                'id'                        => '12345678901234',
                'gateway'                   => 'paysecure',
                'gateway_acquirer'          => 'axis',
                'card'                      => 1,
                'currency'                  => ["INR"],
                'gateway_merchant_id'       => '123456789012345',
                'gateway_terminal_id'       => '12345678',
                'mode'                      => 3,
                'status'                    => 'activated',
                'enabled'                   => 1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'axis',
                'gateway_merchant_id' => '123456789012345',
                'gateway_terminal_id' => '12345678',
                'enabled'             => true
            ]
        ]
    ],
    'testAssignTerminalWhenDuplicateDeactivatedTerminalExist' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'category'                  => '4567',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignTerminalWhenDuplicateDisabledTerminalExist' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'category'                  => '4567',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignTerminalWhenDuplicateTerminalExist' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'category'                  => '4321',
                'gateway_merchant_id'       => '1234567',
                'gateway_terminal_id'       => '87654321',
                'gateway_terminal_password' => 'password',
                'gateway_acquirer'          => 'hdfc',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'     => \RZP\Exception\BadRequestException::class,
            'message'   => 'A terminal for this gateway for this merchant already exists',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,

        ],
    ],

    'testAssignTerminalWhenDuplicateTerminalExistSharedAccount' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'category'                  => '4321',
                'gateway_merchant_id'       => '1234567',
                'gateway_terminal_id'       => '87654321',
                'gateway_terminal_password' => 'password',
                'gateway_acquirer'          => 'hdfc',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'          => 'hdfc',
                'category'                  => '4321',
                'gateway_merchant_id'       => '1234567',
                'gateway_terminal_id'       => '87654321',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignTerminalWhenDuplicateTerminalExistHitachiGateway' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '54321',
                'gateway_terminal_id'       => '12345678',
                'category'                  => '1234',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '54321',
                'gateway_terminal_id' => '12345678',
                'category'            => '1234',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignHitachiBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'mc_mpan'                   => '1234567880123456',
                'visa_mpan'                 => '1234567890123456',
                'rupay_mpan'                => '1234567890123456',
                'category'                  => '4567',
                'type'                      => [
                    'non_recurring' => 1,
                    'bharat_qr'     => 1,
                ],
                'account_number'            => '1234567891011121314',
                'ifsc_code'                 => 'HDFC0009080'
            ],

            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'mc_mpan'             => 'MTIzNDU2Nzg4MDEyMzQ1Ng==', // base64_encode('1234567880123456')
                'visa_mpan'           => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'rupay_mpan'          => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignBankAccountTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => '222333',
                'gateway_merchant_id2'      => '00',
                'type'                      => [
                    'non_recurring'                 => '1',
                    Terminal\Type::NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '100001Razorpay',
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => '222333',
                'gateway_merchant_id2' => '00',
                'type'                 => [
                    'non_recurring',
                    Terminal\Type::NUMERIC_ACCOUNT,
                ],
                'bank_transfer'             => true,
            ]
        ]
    ],

    'testCreateSbiTpvTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_sbi',
                'gateway_merchant_id'       => 'netbanking_sbi_merchant_id',
                'gateway_secure_secret'     => 'random_secret',
                'netbanking'                => '1',
                'tpv'                       => '1',
                'network_category'          => 'ecommerce',
                ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => 'netbanking_sbi_merchant_id',
                'enabled'              => true,
                'tpv'                  => 1
            ]
        ]
    ],

    'testBankAccountTerminalValidationRules' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => '222333',
                'gateway_merchant_id2'      => '00',
                'type'                      => [
                    Terminal\Type::NON_RECURRING    => '1',
                    Terminal\Type::NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '0',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected bank transfer is invalid.',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testEditUsedBankAccountTerminal' => [
        'request' => [
            'content' => [
                'merchant_id'          => '100001Razorpay',
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => '222333',
                'gateway_merchant_id2' => '00',
                'type'    => [
                    'non_recurring'              => '1',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => true,
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Editing not defined for terminal of gateway: '.Gateway::BT_YESBANK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBankAccountTerminal' => [
        'request' => [
            'content' => [
                'merchant_id'          => '100001Razorpay',
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => '222334',
                'gateway_merchant_id2' => '01',
                'type'    => [
                    'non_recurring'                         => '1',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT    => '1',
                    Terminal\Type::NUMERIC_ACCOUNT          => '0',
                ],
                'bank_transfer'             => true,
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Editing not defined for terminal of gateway: '.Gateway::BT_YESBANK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSameRootBankAccountTerminalWithDifferentMerchant' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => '222333',
                'gateway_merchant_id2'      => '01',
                'type'                      => [
                    'non_recurring'               => '1',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => '222333',
                'gateway_merchant_id2' => '01',
                'merchant_id'          => '100002Razorpay',
                'type'                 => [
                    'non_recurring',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT,
                ],
                'bank_transfer'             => true,
            ]
        ]
    ],

    'testAssignDifferentTypeBankAccountTerminalForSameMerchant' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => 'ABCDEF',
                'gateway_merchant_id2'      => 'RZ',
                'type'                      => [
                    'non_recurring'               => '1',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => 'ABCDEF',
                'gateway_merchant_id2' => 'RZ',
                'merchant_id'          => '100001Razorpay',
                'type'                 => [
                    'non_recurring',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT,
                ],
                'bank_transfer'             => true,
            ]
        ]
    ],

    'testAssignSameTypeBankAccountTerminalForSameMerchant' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => '222334',
                'gateway_merchant_id2'      => '01',
                'type'                      => [
                    'non_recurring'         => '1',
                    Terminal\Type::NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,
                ],
            ],
            'status_code' => 400,
        ],
                'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,
        ],
    ],

    'testAssignSameRootAndSameTypeBankAccountTerminalAfterSharedTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => Gateway::BT_YESBANK,
                'gateway_merchant_id'       => '222333',
                'gateway_merchant_id2'      => '00',
                'type'                      => [
                    'non_recurring'         => '1',
                    Terminal\Type::NUMERIC_ACCOUNT  => '1',
                ],
                'bank_transfer'             => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
        ],
    ],

    'testAssignHitachiTerminalWithInvalidGatewayAcquirer' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'icic',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'category'                  => '4567',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'icic is not a valid acquirer for hitachi',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAddEmiTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'                  => '4567',
                'emi'                       => '1',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'hdfc',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'emi_subvention'      => 'customer',
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testCreateUpiIciciOnlineTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'upi_icici',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'type'                 => [
                    'pay'           => '1',
                    'collect'       => '1',
                    'non_recurring' => '1',
                    'online'        => '1',
                ],
            ],
            'method'  => 'POST',
            'url'     => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true
            ]
        ]
    ],

    'testCreateUpiIciciOfflineTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_icici',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => 'rzpbqr@icici',
                'upi'                       => true,
                'type'                      => [
                    'offline'       => '1',
                    'pay'           => '1',
                    'collect'       => '1',
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true
            ]
        ]
    ],

    'testCreateUpiIciciOnlineAndOfflineTerminal' => [
        'request'   => [
            'content' => [
                'gateway'              => 'upi_icici',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'type'                 => [
                    'pay'           => '1',
                    'collect'       => '1',
                    'non_recurring' => '1',
                    'online'        => '1',
                    'offline'       => '1',
                ],
            ],

            'method' => 'POST',
            'url'    => '/merchants/10000000000000/terminals',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Terminal should be either online or offline.',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateUPIInAppTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'upi_axis',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'vpa'                  => 'some@axis',
                'type'                 => [
                    'non_recurring' => '1',
                    'in_app'         => '1',
                ],
            ],
            'method'  => 'POST',
            'url'     => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true,
                'type'    => [
                    'non_recurring',
                    'in_app',
                ],
            ]
        ]
    ],

    'testCreateUPIInAppIOSTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'upi_axis',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'vpa'                  => 'some@axis',
                'type'                 => [
                    'in_app'         => '1',
                    'ios'            => '1',
                ],
            ],
            'method'  => 'POST',
            'url'     => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true,
                'type'    => [
                    'in_app',
                    'ios',
                ],
            ]
        ]
    ],

    'testCreateUPIInAppAndroidTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'upi_axis',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'vpa'                  => 'some@axis',
                'type'                 => [
                    'in_app'         => '1',
                    'android'        => '1',
                ],
            ],
            'method'  => 'POST',
            'url'     => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true,
                'type'    => [
                    'in_app',
                    'android',
                ],
            ]
        ]
    ],

    'testCreateUPIInAppIOSAndAndroidTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'upi_axis',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'upi'                  => true,
                'vpa'                  => 'some@axis',
                'type'                 => [
                    'in_app'         => '1',
                    'ios'            => '1',
                    'android'        => '1',
                ],
            ],
            'method'  => 'POST',
            'url'     => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzpbqr@icici',
                'enabled'              => true,
                'type'    => [
                    'in_app',
                    'ios',
                    'android',
                ],
            ]
        ]
    ],

    'testAddBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'mc_mpan'                   => '1234567880123456',
                'visa_mpan'                 => '1234567890123456',
                'rupay_mpan'                => '1234567890123456',
                'category'                  => '4567',
                'type'                      => [
                    'non_recurring' => '1',
                    'bharat_qr'     => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'ratn',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'mc_mpan'             => 'MTIzNDU2Nzg4MDEyMzQ1Ng==', // base64_encode('1234567880123456')
                'visa_mpan'           => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'rupay_mpan'          => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'category'            => '4567',
                'enabled'             => true
            ]
        ]
    ],

    'testAddUpiMindgateBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'vpa'                       => 'random@hdfc',
                'gateway_terminal_password' => 'password',
                'type'                      => [
                    'non_recurring' => '1',
                    'bharat_qr'     => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'hdfc',
                'gateway_merchant_id' => '12345',
                'enabled'             => true
            ]
        ]
    ],

    'testReassignBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hitachi',
                'gateway_acquirer'          => 'ratn',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'mc_mpan'                   => '4287346423986423',
                'visa_mpan'                 => '5287346853986423',
                'rupay_mpan'                => '6287346823986423',
                'category'                  => '4567',
                'type'                      => [
                    'non_recurring' => '1',
                    'bharat_qr'     => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
        ],
    ],

    'testAddUpiBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_icici',
                'gateway_merchant_id'       => '12345',
                'vpa'                       => 'rzpbqr@icici',
                'upi'                       => true,
                'type'                      => [
                    'non_recurring' => '1',
                    'bharat_qr'     => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => '12345',
                'vpa'                 => 'rzpbqr@icici',
                'enabled'             => true
            ]
        ]
    ],

    'testReassignUpiBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'             => 'upi_icici',
                'gateway_merchant_id' => '12345',
                'vpa'                 => 'random@icici',
                'upi'                 => true,
                'type'                => [
                    'bharat_qr'       => '1',
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
        ],
    ],

    'testReassignTerminalForSameGateway' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'type'                      => [
                    'non_recurring' => '1',
                ],
                'international'             => 0,
            ],
            'url' => '/merchants/10000000000000/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY,
        ],
    ],

    'testAssignTerminalForDifferentGateway' => [
        'request'  => [
            'content' => [
                'gateway'                    => 'atom',
                'netbanking'                 => 1,
                'gateway_merchant_id'        => '12345',
                'gateway_secure_secret'      => 'random_secret',
                'gateway_access_code'        => 'random_access_code',
                'network_category'           => 'ecommerce',
                'gateway_terminal_password'  => 'password',
                'gateway_terminal_password2' => 'password2',
                'gateway_secure_secret2'     => 'securepassword',
            ],
            'url'     => '/merchants/10000000000000/terminals',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway'               => 'atom',
                'gateway_merchant_id'   => '12345',
                'enabled'               => true,
            ]
        ],
    ],

    'testDeleteTerminal' => [
        'request' => [
            'url' => '/merchants/10abcdefghsdfs/terminals/testatomrandom',
            'method' => 'DELETE'
        ],
        'response' => [
              'content' => [
            ]
        ],
    ],

    'testDeleteTerminal2' => [
        'request' => [
            'url' => '/terminals/testatomrandom',
            'method' => 'DELETE',
        ],
        'response' => [
              'content' => [
            ]
        ],
    ],

    'testDeleteTerminal2WithAxisOrgId' => [
        'request' => [
            'url' => '/terminals/testatomrandom',
            'method' => 'DELETE',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCopySharedTerminal' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SHARED_TERMINAL_CANNOT_BE_COPIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SHARED_TERMINAL_CANNOT_BE_COPIED,
        ],
    ],

    'testCreateTerminalWithNetworkCategory' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'  => '4567',
                'netbanking'   => '1',
                'network_category' => 'govt_education',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true,
                'enabled_banks'       => ['HDFC'],
            ]
        ]
    ],
    'testCreateTerminalWithInvalidNetworkCategory' => [
        'request' => [
            'content' => [
                'gateway' => 'hdfc',
                'gateway_acquirer' => 'hdfc',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'  => '4567',
                'card'   => '1',
                'network_category' => 'education',
                'gateway_acquirer' => 'hdfc',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Category provided invalid for gateway',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreateTerminalUpiJuspay' =>  [
        'request' => [
            'content' => [
                'gateway' => 'upi_juspay',
                'mode' => '3',
                'gateway_merchant_id'   => '100000Razorpay',
                'gateway_merchant_id2'  => 'rzpChannelId123',
                'gateway_terminal_id'   => 'xmerchantid',
                'vpa'       => 'shubh123@abfspay',
                'procurer'  =>  'razorpay',
                'upi'       => '1',
                'expected'  => '1',
                'gateway_acquirer'      => 'axis',
                'gateway_secure_secret' => 'supersecret',
                'type'                  => [
                    'collect'       => '1',
                    'pay'          => '1',
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'upi_juspay',
                'gateway_merchant_id'   => '100000Razorpay',
                'gateway_merchant_id2'  => 'rzpChannelId123',
                'gateway_terminal_id'   => 'xmerchantid',
                'enabled'               => true,
                'status'                => 'activated',
            ]
        ],
        'status_code'   => 200,
    ],

    'testCreateTerminalBilldeskSiHub' =>  [
        'request' => [
            'content' => [
                'gateway' => 'billdesk_sihub',
                'card' => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'billdesk_sihub',
                'enabled' => true,
                'status'  => 'activated',
            ]
        ],
        'status_code' => 200,
    ],

    'testCreateTerminalMandateHq' =>  [
        'request' => [
            'content' => [
                'gateway' => 'mandate_hq',
                'card' => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'mandate_hq',
                'enabled' => true,
                'status'  => 'activated',
            ]
        ],
        'status_code' => 200,
    ],

    'testCreateTerminalWithPendingStatus' => [
        'request' => [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'gateway_terminal_password' => '12345678',
                'category'  => '4567',
                'netbanking'   => '1',
                'network_category' => 'govt_education',
                'status' => 'pending'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => '12345',
                'gateway_terminal_id' => '12345678',
                'category'            => '4567',
                'enabled'             => true,
                'enabled_banks'       => ['HDFC'],
                'status'              => 'pending',
            ]
        ]
    ],

    'testCreateHitachiDebitRecurringTerminal' => [
        'request'  => [
            'content' => [
                'gateway'             => 'hitachi',
                'gateway_acquirer'    => 'ratn',
                'card'                => 1,
                'type'                => [
                    'recurring_non_3ds' => '1',
                    'recurring_3ds'     => '1',
                    'debit_recurring'   => '1',
                ],
                'gateway_merchant_id' => 'random',
                'gateway_terminal_id' => '12345678',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'hitachi',
                'card'    => true,
                'type'    => [
                    'recurring_3ds',
                    'recurring_non_3ds',
                    'debit_recurring',
                ],
                'enabled' => true,
            ],
        ],
    ],

    'testCreateTerminalPendingStatus' => [
        'request'  => [
            'content' => [
                'gateway'             => 'hitachi',
                'gateway_acquirer'    => 'ratn',
                'card'                => 1,
                'type'                => [
                    'recurring_non_3ds' => '1',
                    'recurring_3ds'     => '1',
                    'debit_recurring'   => '1',
                ],
                'gateway_merchant_id' => 'random',
                'gateway_terminal_id' => '12345678',
                'status'              => 'pending',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'hitachi',
                'card'    => true,
                'type'    => [
                    'recurring_3ds',
                    'recurring_non_3ds',
                    'debit_recurring',
                ],
                'enabled' => true,
                'status'              => 'pending',
            ],
        ],
    ],


    'testCreateUpiCollectTerminal' => [
        'request'  => [
            'content' => [
                'gateway'             => 'upi_icici',
                'upi'                 => 1,
                'type'                => [
                    'collect' => '1',
                ],
                'gateway_merchant_id'       => 'razorpay upi',
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'upi_icici',
                'upi'    => true,
                'type'    => [
                    'collect'
                ],
                'enabled' => true,
            ],
        ],
    ],

    'testCreateGooglePayTerminal' => [
        'request'  => [
            'content' => [
                'gateway'              => 'google_pay',
                'omnichannel'          => 1,
                'gateway_merchant_id'  => 'razorpay upi',
                'gateway_merchant_id2' => 'abc@icici',
                'vpa'                  => 'abc@icici',
                'capability'           => 1,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway'       => 'google_pay',
                'upi'           => false,
                'omnichannel'   => true,
                'enabled'       => true,
            ],
        ],
    ],

    'testCreateGooglePayTerminalDuplicateVpa' => [
        'request'  => [
            'content' => [
                'gateway'              => 'google_pay',
                'omnichannel'          => 1,
                'gateway_merchant_id'  => 'razorpay upi',
                'gateway_merchant_id2' => 'abc@sbi',
                'vpa'                  => 'abc@sbi',
                'capability'           => 1,
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway'       => 'google_pay',
                'upi'           => false,
                'omnichannel'   => true,
                'enabled'       => true,
            ],
        ],
    ],

    'testCreateTpvTerminalWithInvalidMethod' => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'card'                      => '1',
                'tpv'                       => '2'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'tpv is not required and shouldn\'t be sent',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testCreateTpvTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'tpv'                       => '2'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => '12345678',
                'enabled'              => true,
                'tpv'                  => 2
            ]
        ]
    ],

    'testCreatePaytmTerminal' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                  => 'paytm',
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'procurer'                 => 'merchant',
                'netbanking'               => '0',
                'card'                     => '0',
                'upi'                      => '0',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'type'  => [
                    'direct_settlement_with_refund'
                ],
                'netbanking' => false,
                'card'       => false,
                'upi'        => false,
                'emi'        => false,
                'bank_transfer' => false,
                'aeps'       => false,
                'cardless_emi' => false,
                'paylater'    => false,
                'cred'        => false,
            ]
        ],
    ],

    'testCreateCashfreeCardTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'cashfree',
                'gateway_terminal_id'   => 'CF1234',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'gateway_access_code'   => '12344',
                'gateway_secure_secret' => '2d2fe54f576ff428d93019f48695870abebb2327',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateCcavenueCardTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'ccavenue',
                'gateway_terminal_id'   => 'CF1234',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'gateway_access_code'   => '12344',
                'gateway_secure_secret' => '2d2fe54f576ff428d93019f48695870abebb2327',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateCredTerminal' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                  => 'cred',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'cred'                     =>  1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testCreateOfflineTerminal' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                  => 'offline_hdfc',
                'gateway_merchant_id'      => '12344',
                'gateway_acquirer'         => 'hdfc',
                'offline'                  =>  1,
                'type'                  => [
                    'direct_settlement_without_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testCreateOfflineBadRequestTerminal' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                  => 'offline_hdfc',
                'gateway_merchant_id'      => '12344',
                'gateway_acquirer'         => 'hdfc',
                'gateway_terminal_id'      => '12344',
                'offline'                     =>  1,
                'type'                  => [
                    'direct_settlement_without_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "gateway_terminal_id is/are not required and should not be sent",
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateEzetapBadRequestTerminal' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                  => 'hdfc_ezetap',
                'gateway_merchant_id'      => '12344',
                'gateway_acquirer'         => 'hdfc',
                'gateway_terminal_id'      => '12344',
                'upi'                     =>  1,
                'type'                  => [
                    'pos' => '1',
                    'direct_settlement_with_refund' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "gateway_terminal_id is/are not required and should not be sent",
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testCreateTwidTerminal' => [
        'request' => [
            'url' => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                  => 'twid',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => 'gateway_secure_secret',
                'gateway_secure_secret2'   => 'gateway_secure_secret2',
                'app'                      =>  1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'      => '12344',
                'enabled_apps'             => [
                    'twid'
                ],
                'app'                       => true,
            ]
        ],
    ],

    'testCreateTwidTerminal' => [
        'request' => [
            'url' => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                  => 'twid',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => 'gateway_secure_secret',
                'gateway_secure_secret2'   => 'gateway_secure_secret2',
                'app'                      =>  1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'      => '12344',
                'enabled_apps'             => [
                    'twid'
                ],
                'app'                       => true,
            ]
        ],
    ],

    'testCreatePaytmCardTerminal' => [
        'request' => [
            'content' => [
                'gateway'                  => 'paytm',
                'card'                      => 1,
                'gateway_terminal_id'      => '12344',
                'gateway_access_code'      => '12344',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => '12345',
                'type'                      => [
                    'non_recurring'                 => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'type'  => ['non_recurring',
                    'direct_settlement_with_refund'
                ],
            ]
        ],
    ],

    'testCreateZaakpayCardTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'zaakpay',
                'gateway_terminal_id'   => 'CF1234',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'gateway_access_code'   => '12344',
                'gateway_secure_secret' => '2d2fe54f576ff428d93019f48695870abebb2327',
                'gateway_secure_secret2' => '2d2fe54f576ff428d93019f48695870abebb2343',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreatePinelabsCardTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'pinelabs',
                'gateway_merchant_id'   => '100000',
                'gateway_access_code'   => '12344',
                'gateway_secure_secret' => '2d2fe54f576ff428d93019f48695870abebb2327',
                'gateway_secure_secret2'=> '2d2fe54f576ff428d93019f48695870abebb2343',
                'card'                  => 1,
                'type'                  => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '100000',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateBilldeskOptimizerTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'billdesk_optimizer',
                'gateway_merchant_id'   => '100000',
                'gateway_secure_secret2' => '2d2fe54f576ff428d93019f48695870abebb2327',
                'card'                  => 1,
                'type'                  => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '100000',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateIngenicoCardTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'ingenico',
                'gateway_merchant_id'   => 'T706040',
                'gateway_access_code'   => '8036335687FEMIEC',
                'gateway_secure_secret' => '2418691025AFCDDF',
                'card'                  => 1,
                'type'                  => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'T706040',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateDirectSettlemtTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'type'                      => [
                    'non_recurring'                    => '1',
                    'direct_settlement_without_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => '12345678',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateDirectSettlementIndusIndTerminal' => [
        'request' => [
            'url'   => '/merchants/100000Razorpay/terminals',
            'method' => 'POST',
            'content' => [
                'gateway'                   => 'netbanking_indusind',
                'gateway_merchant_id'       => '12345',
                'gateway_secure_secret'     => '12345678',
                'netbanking'                => '1',
                'network_category'          => 'ecommerce',
                'type'                      => [
                    'direct_settlement_with_refund' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'gateway'             => 'netbanking_indusind',
                'gateway_merchant_id' => '12345',
                'status'              => 'activated',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateDirectSettlementTerminalValidationFailure' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'type'                      => [
                    'non_recurring'                    => '1',
                    'direct_settlement_without_refund' => '1',
                    'direct_settlement_with_refund'    => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Direct Settlement Terminal should be either with refund enabled or without refund.',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateTerminalWithMerchantProcurer' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'procurer'                  => 'merchant',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => '12345678',
                'enabled'              => true,
                'procurer'             => 'merchant',
            ]
        ]
    ],

    'testCreateCardlessEmiTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'cardless_emi',
                'gateway_acquirer'          => 'zestmoney',
                'category'                  => 1234,
                'gateway_merchant_id'       => '64517b42-7b8d-4137-924a-4b6a065e7e4d',
                'gateway_merchant_id2'      => 'test merchant',
                'mode'                      => 1,
                'cardless_emi'              => 1,
                'gateway_terminal_password' => 'aabbccdd'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '64517b42-7b8d-4137-924a-4b6a065e7e4d',
                'gateway_merchant_id2' => 'test merchant',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreatePayLaterTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'paylater',
                'gateway_acquirer'          => 'epaylater',
                'category'                  => 1234,
                'gateway_merchant_id'       => 'abcd',
                'gateway_merchant_id2'      => 'test merchant',
                'mode'                      => 1,
                'paylater'                  => 1,
                'gateway_terminal_password' => '64517b42-7b8d-4137-924a-4b6a065e7e4d'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'abcd',
                'gateway_merchant_id2' => 'test merchant',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateFlexmoneyTerminalWithEnabledBanks'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'paylater',
                'gateway_acquirer'          => 'flexmoney',
                'category'                  => 1234,
                'gateway_merchant_id'       => 'abcd',
                'gateway_merchant_id2'      => 'test merchant',
                'mode'                      => 1,
                'paylater'                  => 1,
                'gateway_terminal_password' => '64517b42-7b8d-4137-924a-4b6a065e7e4d'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'abcd',
                'gateway_merchant_id2' => 'test merchant',
                'enabled'              => true,
                'enabled_banks'        => [
                    "HDFC"
                ]
            ]
        ]
    ],

    'testCreateCardlessEmiFlexmoneyTerminalWithEnabledBanks'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'cardless_emi',
                'gateway_acquirer'          => 'flexmoney',
                'category'                  => 1234,
                'gateway_merchant_id'       => 'abcd',
                'gateway_merchant_id2'      => 'test merchant',
                'mode'                      => 1,
                'cardless_emi'              => 1,
                'gateway_terminal_password' => '64517b42-7b8d-4137-924a-4b6a065e7e4d'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'abcd',
                'gateway_merchant_id2' => 'test merchant',
                'enabled'              => true,
                'enabled_banks'        => []
            ]
        ]
    ],

    'testCreateUpiAirtelTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_airtel',
                'gateway_merchant_id'       => 'MER0000000001202',
                'upi'                       => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_merchant_id2'      => 'rzp@apbl'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'MER0000000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateUpiCitiTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_citi',
                'gateway_merchant_id'       => 'CITI0000000001202',
                'upi'                       => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_merchant_id2'      => 'rzp@apbl',
                'type'                      => [
                    'collect'               => 1,
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'CITI0000000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateMpgsTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'mpgs',
                'gateway_merchant_id'       => 'MPGS0000000001202',
                'card'                      => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_acquirer'          => 'hdfc',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'MPGS0000000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateMpgsAcquirerOcbcTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'mpgs',
                'gateway_merchant_id'       => 'MPGSOCBC000001202',
                'card'                      => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_acquirer'          => 'ocbc',
                'capability'                => 0,
                'type'                      => [
                    'non_recurring' => '1',
                ],
                'currency'                  => ['MYR']
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'MPGSOCBC000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateEghlTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => 'EGHL00001000',
                'card'                      => 1,
                'enabled_wallets'           => ['boost'],
                'gateway_terminal_password' => 'abcd',
                'capability'                => 0,
                'type'                      => [
                    'non_recurring' => '1',
                ],
                'currency'                  => ['MYR']
            ],
            'method' => 'POST'
        ],

        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'EGHL00001000',
                'enabled'              => true,
            ]
        ]
    ],

    'testDeleteEghlTerminal' => [
        'request' => [
            'method' => 'DELETE',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTerminalInvalidAcquirerForCountry'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'mpgs',
                'gateway_merchant_id'       => 'MPGSOCBC000001202',
                'card'                      => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_acquirer'          => 'ocbc',
                'capability'                => 0,
                'type'                      => [
                    'non_recurring' => '1',
                ],
                'currency'                  => ['MYR']
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'   => [
            ]
        ],
    ],

    'testCreateMpgsPurchaseTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'mpgs',
                'gateway_merchant_id'       => 'MPGS0000000001202',
                'card'                      => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_acquirer'          => 'hdfc',
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'MPGS0000000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateIsgCardTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'isg',
                'gateway_merchant_id'       => 'some_random_val',
                'gateway_merchant_id2'      => 'some_random2',
                'gateway_access_code'       => 'oxymoron',
                'mode'                      => Terminal\Mode::PURCHASE,
                'card'                      => 1,
                'gateway_secure_secret'     => 'hogwards',
                'gateway_terminal_id'       => 'CG000001',
                'gateway_acquirer'          => 'kotak',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'some_random_val',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateDirectSettlemtTerminalFailure' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_airtel',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => 'random password',
                'upi'                       => '1',
                'tpv'                       => '2',
                'type'                      => [
                    'non_recurring'                    => '1',
                    'direct_settlement_without_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No terminal gateway mapping for direct settlement',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_NO_GATEWAY_MAPPING_FOR_DIRECTSETTLEMENT,
        ],
    ],

    'testToggleTerminal' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
        'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'enabled' => false
            ]
        ]
    ],

    'testToggleTerminalWithAxisOrgId' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'enabled' => false
            ],
        ],
    ],

    'testTerminalModeDual' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'first_data',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_acquirer'          => 'icic',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'non_recurring'
                ],
            ]
        ],
    ],

    'testTerminalModePurchase' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'first_data',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_acquirer'          => 'icic',
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'recurring_non_3ds'
                ],
            ]
        ],
    ],

    'testTerminalModeAuthCapture' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'axis_migs',
                'gateway_acquirer'          => 'axis',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'randomsecuresecretwhichis32chars',
                'gateway_access_code'       => 'rndmcode',
                'gateway_terminal_id'       => 'randomterminalid',
                'gateway_terminal_password' => 'randomterminalpassword',
                'mode'                      => Terminal\Mode::AUTH_CAPTURE,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::AUTH_CAPTURE,
                'type'                      => [
                    'non_recurring'
                ],
            ]
        ],
    ],

    'testTerminalModeDualFailure' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'first_data',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_acquirer'          => 'icic',
                'mode'                      => Terminal\Mode::AUTH_CAPTURE,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'first_data terminals must be in Dual mode',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTerminalModePurchaseSuccess' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'wallet_paypal',
                'gateway_merchant_id'       => 'randommerchantid',
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => []
        ],
    ],

    'testPaypalTerminalModeNotPurchase' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'wallet_paypal',
                'gateway_merchant_id'       => 'randommerchantid',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'wallet_paypal terminals must be in Purchase mode',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTerminalModePurchaseError' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'getsimpl',
                'gateway_merchant_id'       => 'randommerchantid',
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'getsimpl terminals must be in Dual mode',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTerminalModePurchaseFailure' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'first_data',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_acquirer'          => 'icic',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'FirstData Non-3DS terminals must be in Purchase mode',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTerminalModeAuthCaptureFailure' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'amex',
                'card'                      => true,
                'gateway_acquirer'          => 'amex',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'randomsecuresecretwhichis32chars',
                'gateway_access_code'       => 'rndmcode',
                'gateway_terminal_id'       => 'randomterminalid',
                'gateway_terminal_password' => 'randomterminalpassword',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'non_recurring' => '1',
                ],
                'network_category'          => 'fuel_government',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amex terminals must be in AuthCapture mode',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTerminalTypeRecurringNon3DS' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'cybersource',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_terminal_id'       => 'randommerchantid',
                'gateway_terminal_password' => 'randommerchantidrandommerchantidrandommerchantidrandommerchantid',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_secure_secret2'    => 'secure_secret2',
                'gateway_access_code'       => 'access_code',
                'gateway_acquirer'          => 'hdfc',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_3ds'     => '0',
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_non_3ds'
                ],
            ]
        ],
    ],

    'testTerminalTypeRecurring3DS' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'cybersource',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_terminal_id'       => 'randommerchantid',
                'gateway_terminal_password' => 'randommerchantidrandommerchantidrandommerchantidrandommerchantid',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_secure_secret2'    => 'secure_secret2',
                'gateway_access_code'       => 'access_code',
                'gateway_acquirer'          => 'hdfc',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_3ds'     => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_3ds',
                ],
            ]
        ],
    ],

    'testTerminalTypeRecurringBoth' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'axis_migs',
                'gateway_acquirer'          => 'axis',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'abcdefghijklmnopqrstuvwxyz123456',
                'gateway_access_code'       => 'abcdef12',
                'gateway_terminal_id'       => 'randommerchantid',
                'gateway_terminal_password' => 'randomuser123',
                'mode'                      => Terminal\Mode::AUTH_CAPTURE,
                'type'                      => [
                    'recurring_3ds'     => '1',
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::AUTH_CAPTURE,
                'type'                      => [
                    'recurring_3ds',
                    'recurring_non_3ds',
                ],
            ]
        ],
    ],

    'testTerminalTypeIvr' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_id'       => '12345678',
                'gateway_terminal_password' => '12345678',
                'type'                      => [
                    'ivr'     => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'ivr',
                ],
            ]
        ],
    ],

    'testTerminalCheckAutoDisable' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
            'gateway_error_code'  => 'GW00154',
        ],
    ],

    'testEditWalletAirtelmoneyTerminalWithNotRequiredFields' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE ,
        ],
    ],

    'testHitachiTerminalsCurrencyUpdateCron' => [
        'request' => [
            'url' => '/hitachi_terminals/update_cron',
            'content' => [
                'count' => 100
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success' => 2,
                'failed'  => 0,
                'total'   => 2,
            ]
        ]
    ],

    'testTerminalSecretCheck' => [
        'request' => [
            'content' => [
                'gateway_terminal_password'  => '1234',
                'gateway_terminal_password2' => '21234',
                'gateway_secure_secret'      => '0123',
                'gateway_secure_secret2'     => '201235',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_terminal_password'  => true,
                'gateway_terminal_password2' => true,
                'gateway_secure_secret'      => true,
                'gateway_secure_secret2'     => false,
            ]
        ]
    ],

    'testAddAmazonPayTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_amazonpay',
                'gateway_merchant_id'       => '12345',
                'gateway_terminal_password' => '12345678',
                'gateway_access_code'       => '1234567880123456',
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway'                   => 'wallet_amazonpay',
                'gateway_merchant_id'       => '12345',
                'enabled'                   => true,
                'enabled_wallets'           => ['amazonpay']
            ]
        ]
    ],

    'testAssignIsgBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'isg',
                'gateway_merchant_id'       => 'random',
                'gateway_merchant_id2'      => 'random3',
                'gateway_terminal_id'       => '12345678',
                'mc_mpan'                   => '1234567880123456',
                'visa_mpan'                 => '1234567890123456',
                'rupay_mpan'                => '1234567890123456',
                'type'                      => [
                    'non_recurring' => '1',
                    'bharat_qr'     => '1',
                ],
            ],

            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => 'random',
                'gateway_terminal_id' => '12345678',
                'mc_mpan'             => 'MTIzNDU2Nzg4MDEyMzQ1Ng==', // base64_encode('1234567880123456')
                'visa_mpan'           => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'rupay_mpan'          => 'MTIzNDU2Nzg5MDEyMzQ1Ng==', // base64_encode('1234567890123456')
                'enabled'             => true
            ]
        ]
    ],

    'testAddIsgBharatQrTerminalFailed' => [
        'request' => [
            'content' => [
                'gateway'                   => 'isg',
                'gateway_merchant_id'       => 'random',
                'gateway_merchant_id2'      => 'random2',
                'gateway_terminal_id'       => '12345678',
                'mc_mpan'                   => '1234567880123456',
                'visa_mpan'                 => '1234567890123456',
                'rupay_mpan'                => '1234567890123456',
                'type'                      => [
                    'bharat_qr'     => '1',
                ],
            ],

            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The type.non recurring field is required.',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAddHulkTerminalWithAppAuth' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_hulk',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => 'vpa_12345678901234',
                'gateway_terminal_password' => '12345678',
                'gateway_access_code'       => 'app',
                'upi'                       => true,
            ],
            'method' => 'POST',
            'url' => '/merchants/10000000000000/terminals',
        ],
        'response' => [
            'content' => [
                'gateway'                   => 'upi_hulk',
                'gateway_merchant_id'       => 'vpa_12345678901234',
                'enabled'                   => true
            ]
        ]
    ],

    'testGetTerminalBanks' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'BKID'   => 'Bank of India',
                    'MAHB'   => 'Bank of Maharashtra',
                    'CNRB'   => 'Canara Bank',
                    'CBIN'   => 'Central Bank of India',
                    'CIUB'   => 'City Union Bank',
                    'DCBL'   => 'DCB Bank',
                    'DEUT'   => 'Deutsche Bank',
                    'DLXB'   => 'Dhanlaxmi Bank',
                    'ESFB'   => 'Equitas Small Finance Bank',
                    'IBKL'   => 'IDBI',
                    'IDIB'   => 'Indian Bank',
                    'IOBA'   => 'Indian Overseas Bank',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'KVBL'   => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'SIBL'   => 'South Indian Bank',
                    'SCBL'   => 'Standard Chartered Bank',
                    'SBBJ'   => 'State Bank of Bikaner and Jaipur',
                    'SBHY'   => 'State Bank of Hyderabad',
                    'SBIN'   => 'State Bank of India',
                    'SBMY'   => 'State Bank of Mysore',
                    'STBP'   => 'State Bank of Patiala',
                    'SBTR'   => 'State Bank of Travancore',
                    'TMBL'   => 'Tamilnad Mercantile Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testGetTerminalWallets' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'amazonpay'
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testFillEnabledWallets' => [
        'request' => [
            'url'=> '/terminals/fill/enabled_wallets',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'total'   => 1,
                'count'   => 100,
                'success' => 1,
                'failed'  => 0
            ],
        ],
    ],

    'testGetTerminalBanksForBilldesk' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'BKID'   => 'Bank of India',
                    'MAHB'   => 'Bank of Maharashtra',
                    'CNRB'   => 'Canara Bank',
                    'CBIN'   => 'Central Bank of India',
                    'CIUB'   => 'City Union Bank',
                    'DCBL'   => 'DCB Bank',
                    'DEUT'   => 'Deutsche Bank',
                    'DLXB'   => 'Dhanlaxmi Bank',
                    'IBKL'   => 'IDBI',
                    'IDIB'   => 'Indian Bank',
                    'IOBA'   => 'Indian Overseas Bank',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'KVBL'   => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'SIBL'   => 'South Indian Bank',
                    'SCBL'   => 'Standard Chartered Bank',
                    'SBBJ'   => 'State Bank of Bikaner and Jaipur',
                    'SBHY'   => 'State Bank of Hyderabad',
                    'SBIN'   => 'State Bank of India',
                    'SBMY'   => 'State Bank of Mysore',
                    'STBP'   => 'State Bank of Patiala',
                    'SBTR'   => 'State Bank of Travancore',
                    'TMBL'   => 'Tamilnad Mercantile Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                ],
                'disabled' => [
                    'ESFB'   => 'Equitas Small Finance Bank',
                    'FDRL'   => 'Federal Bank',
                ],
            ],
        ],
    ],

    'testGetTpvTerminalBanks' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'MAHB' => 'Bank of Maharashtra',
                    'CIUB' => 'City Union Bank',
                    'DCBL' => 'DCB Bank',
                    'DEUT' => 'Deutsche Bank',
                    'DLXB' => 'Dhanlaxmi Bank',
                    'IBKL' => 'IDBI',
                    'IDIB' => 'Indian Bank',
                    'JAKA' => 'Jammu and Kashmir Bank',
                    'KVBL' => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'SRCB' => 'Saraswat Co-operative Bank',
                    'SBIN' => 'State Bank of India',
                    'TMBL' => 'Tamilnad Mercantile Bank',
                    'YESB' => 'Yes Bank',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testGetCorpTerminalBanks' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'UTIB_C' => 'Axis Bank - Corporate Banking',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testGetTerminalBanksForNonNetbankingTerminal' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Banks available only for netbanking gateways and some paylater/cardless_emi providers',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSetBanksForTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['SBIN'],
            ],
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'SBIN'   => 'State Bank of India',
                ],
                'disabled' => [
                    'BKID'   => 'Bank of India',
                    'MAHB'   => 'Bank of Maharashtra',
                    'CNRB'   => 'Canara Bank',
                    'CBIN'   => 'Central Bank of India',
                    'CIUB'   => 'City Union Bank',
                    'DCBL'   => 'DCB Bank',
                    'DEUT'   => 'Deutsche Bank',
                    'DLXB'   => 'Dhanlaxmi Bank',
                    'ESFB'   => 'Equitas Small Finance Bank',
                    'IBKL'   => 'IDBI',
                    'IDIB'   => 'Indian Bank',
                    'IOBA'   => 'Indian Overseas Bank',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'KVBL'   => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'SIBL'   => 'South Indian Bank',
                    'SCBL'   => 'Standard Chartered Bank',
                    'SBBJ'   => 'State Bank of Bikaner and Jaipur',
                    'SBHY'   => 'State Bank of Hyderabad',
                    'SBMY'   => 'State Bank of Mysore',
                    'STBP'   => 'State Bank of Patiala',
                    'SBTR'   => 'State Bank of Travancore',
                    'TMBL'   => 'Tamilnad Mercantile Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                ],
            ],
        ],
    ],

    'testSetWalletsForTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_wallets' => ['paytm', 'freecharge'],
            ],
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'paytm',
                    "freecharge",
                ],
                'disabled' => [
                    "itzcash",
                    "jiomoney",
                    'mobikwik',
                ],
            ],
        ],
    ],

    'testSetUnsupportedBankForTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['FDRL'],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'banks not supported by gateway',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSetBanksForNonNetbankingGateway' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['SBIN'],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Banks available only for netbanking gateways and some paylater/cardless_emi providers',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSetBanksWithIncorrectInput' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => 'SBIN',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'enabled_banks should be an array',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetTerminalBanksForDirectNetbankingTerminal' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testGetTerminalBanksForPaylater' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testGetTerminalBanksForCardlessEmi' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'enabled'  => [
                    'HDFC'  => 'HDFC Bank',
                    'KKBK'  => 'Kotak Mahindra Bank',
                    'FDRL'  => 'Federal Bank',
                    'IDFB'  => 'IDFC FIRST Bank'
                ],
                'disabled' => [],
            ],
        ],
    ],

    'testSetBanksForDirectNetbankingTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['HDFC'],
            ],
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC'   => 'HDFC Bank',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testSetBanksForPaylaterTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['HDFC'],
            ],
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC'   => 'HDFC Bank',
                ],
                'disabled' => [
                ],
            ],
        ],
    ],

    'testSetBanksForCardlessEmiTerminal' => [
        'request' => [
            'method' => 'PATCH',
            'content' => [
                'enabled_banks' => ['HDFC'],
            ],
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC'   => 'HDFC Bank',
                ],
                'disabled' => [
                    'FDRL'   => 'Federal Bank',
                    'IDFB'   => 'IDFC FIRST Bank',
                    'KKBK'   => 'Kotak Mahindra Bank',
                ],
            ],
        ],
    ],

    'testCreateAllahabadTpvTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_allahabad',
                'gateway_merchant_id'       => 'netbanking_alla_merchant_id',
                'gateway_merchant_id2'      => 'netbanking_alla_merchant_id2',
                'netbanking'                => '1',
                'tpv'                       => '1',
                'network_category'          => 'ecommerce',
                'gateway_terminal_password' => 'random_password',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => 'netbanking_alla_merchant_id',
                'gateway_merchant_id2' => 'netbanking_alla_merchant_id2',
                'enabled'              => true,
                'tpv'                  => 1
            ]
        ]
    ],

    'testAssignUpiYesbankTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_yesbank',
                'upi'                       => '1',
                'gateway_merchant_id'       => '1245',
                'type'                      => ['pay' => '1'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => '1245',
                'enabled'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateForBankUnsupportedMethod' => [
        'request' => [
            'content' => [
                'bank'                     => 'ICIC',
                'action'                   => 'testMethod',
                'terminal_ids'             => ['1000AepsShared', '100NbIciciTmnl'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error'                    => ['description' => 'The selected action is invalid.']
            ],
            'status_code'   => 400,

        ]
    ],

    'testBulkTerminalUpdateForBankWithTerminalNotExist' => [
        'request' => [
            'content' => [
                'bank'                     => 'ICIC',
                'action'                   => 'add',
                'terminal_ids'             => ['testTerminal'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'testTerminal'      => 'Terminal doesn\'t exist',
                'success'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateForBankRemoveMethod' => [
        'request' => [
            'content' => [
                'bank'                     => 'ANDB',
                'action'                   => 'remove',
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                '100000EbsTrmnl' => [
                    'BKID'   => 'Bank of India',
                    'MAHB'   => 'Bank of Maharashtra',
                    'CNRB'   => 'Canara Bank',
                    'CBIN'   => 'Central Bank of India',
                    'CIUB'   => 'City Union Bank',
                    'DLXB'   => 'Dhanlaxmi Bank',
                    'IDIB'   => 'Indian Bank',
                    'IOBA'   => 'Indian Overseas Bank',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                    'YESB'   => 'Yes Bank'
                ],
                '1000AtomShared' => [
                    'BKID'   => 'Bank of India',
                    'MAHB'   => 'Bank of Maharashtra',
                    'CNRB'   => 'Canara Bank',
                    'CBIN'   => 'Central Bank of India',
                    'CIUB'   => 'City Union Bank',
                    'DCBL'   => 'DCB Bank',
                    'DEUT'   => 'Deutsche Bank',
                    'DLXB'   => 'Dhanlaxmi Bank',
                    'ESFB'   => 'Equitas Small Finance Bank',
                    'IBKL'   => 'IDBI',
                    'IDIB'   => 'Indian Bank',
                    'IOBA'   => 'Indian Overseas Bank',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'KVBL'   => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'SIBL'   => 'South Indian Bank',
                    'SBIN'   => 'State Bank of India',
                    'SBBJ'   => 'State Bank of Bikaner and Jaipur',
                    'SBHY'   => 'State Bank of Hyderabad',
                    'SBMY'   => 'State Bank of Mysore',
                    'STBP'   => 'State Bank of Patiala',
                    'SBTR'   => 'State Bank of Travancore',
                    'SCBL'   => 'Standard Chartered Bank',
                    'TMBL'   => 'Tamilnad Mercantile Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                ],
                'success'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateForBulkBankRemoveMethod' => [
        'request' => [
            'url'   => '/terminals/banks/bulk',
            'content' => [
                'banks'                    => ['ANDB', 'BKID', 'MAHB', 'CNRB', 'CIUB', 'DLXB', 'IDIB', 'IOBA'],
                'action'                   => 'remove',
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                '100000EbsTrmnl' => [
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                    'YESB'   => 'Yes Bank'
                ],
                '1000AtomShared' => [
                    'KARB'   => 'Karnataka Bank',
                    'KVBL'   => 'Karur Vysya Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                    'PUNB_R' => 'Punjab National Bank - Retail Banking',
                    'SRCB'   => 'Saraswat Co-operative Bank',
                    'SIBL'   => 'South Indian Bank',
                    'SBIN'   => 'State Bank of India',
                    'SBBJ'   => 'State Bank of Bikaner and Jaipur',
                    'SBHY'   => 'State Bank of Hyderabad',
                    'SBMY'   => 'State Bank of Mysore',
                    'STBP'   => 'State Bank of Patiala',
                    'SBTR'   => 'State Bank of Travancore',
                    'SCBL'   => 'Standard Chartered Bank',
                    'TMBL'   => 'Tamilnad Mercantile Bank',
                    'UCBA'   => 'UCO Bank',
                    'UBIN'   => 'Union Bank of India',
                    'UTBI'   => 'PNB (Erstwhile-United Bank of India)',
                ],
                'success' => true,
            ]
        ]

    ],

    'testBulkTerminalUpdateForBankAddMethod' => [
        'request' => [
            'content' => [
                'bank'                     => 'BKID',
                'action'                   => 'add',
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                '1000AtomShared' => [
                    'BKID'   => 'Bank of India',
                ],
                '100000EbsTrmnl' => [
                    'BKID'   => 'Bank of India',
                ],
                'success'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateForBulkBankAddMethod' => [
        'request' => [
            'url'     => '/terminals/banks/bulk',
            'content' => [
                'banks'                    => ['BKID', 'JAKA', 'KARB', 'LAVB_R', 'PSIB'],
                'action'                   => 'add',
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                '1000AtomShared' => [
                    'BKID'   => 'Bank of India',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                ],
                '100000EbsTrmnl' => [
                    'BKID'   => 'Bank of India',
                    'JAKA'   => 'Jammu and Kashmir Bank',
                    'KARB'   => 'Karnataka Bank',
                    'LAVB_R' => 'Lakshmi Vilas Bank - Retail Banking',
                    'PSIB'   => 'Punjab & Sind Bank',
                ],
                'success'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateBulkBankAddRemoveInvalidBank' => [
        'request' => [
            'url'     => '/terminals/banks/bulk',
            'content' => [
                'banks'                    => ['JAKA', 'KARB', 'ZZZZ',  'LAVB_R', 'PSIB'],
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method'  => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    "description" => "Invalid bank name in input: ZZZZ",
                ],
            ],
            'status_code' => '400'
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],

    ],

    'testBulkTerminalUpdateForBankAndBanksShouldFail' => [
        'request' => [
            'url'     => '/terminals/banks/bulk',
            'content' => [
                'bank'                     => 'SBIN',
                'banks'                    => ['BKID', 'JAKA', 'KARB', 'LAVB_R', 'PSIB'],
                'action'                   => 'add',
                'terminal_ids'             => ['100000EbsTrmnl', '1000AtomShared'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "'banks' and 'bank' should not be sent at the same time",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]

    ],

    'testBulkTerminalUpdateForUnsupportedBankAddMethod' => [
        'request' => [
            'content' => [
                'bank'                     => 'SBIN',
                'action'                   => 'add',
                'terminal_ids'             => ['1000AtomShared', '100000EbsTrmnl'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                '100000EbsTrmnl' => 'banks not supported by gateway',
                '1000AtomShared' => [
                    'SBIN'   => 'State Bank of India',
                ],
                'success'             => true
            ]
        ]
    ],

    'testBulkTerminalUpdateForInvalidBankCode' => [
        'request' => [
            'content' => [
                'bank'                     => 'INVALID',
                'action'                   => 'add',
                'terminal_ids'             => ['1000AtomShared', '100000EbsTrmnl'],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error'                    => ['description' => 'Invalid Bank Code.']
            ],
            'status_code'   => 400,
        ]
    ],

    'testUpdateTerminalsBulk'    =>  [
        'request' => [
            'method'  => 'PATCH',
            'url'     => '/terminals/bulk',
            'content' => [
                'terminal_ids' => [

                ],
                'attributes'  =>  [
                    'status'  => 'activated'
                ]
            ],
        ],
        'response' => [
            'content'   =>  [
                'total'     => 3,
                'success'   => 2,
                'failed'    => 1,
                'failedIds' =>  ['notexisttermid']
            ]
        ]
    ],

    'testTerminalsEnableBulk'    =>  [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/terminals/enable/bulk',
            'content' => [
                'terminal_ids' => [

                ],
            ],
        ],
        'response' => [
            'content'   =>  [
                'total'     => 4,
                'success'   => 2,
                'failed'    => 2,
                'failedIds' =>  []
            ]
        ]
    ],

    'testUpdateTerminalsBulkTryEnablingFailedTerminal'    =>  [
        'request' => [
            'method'  => 'PATCH',
            'url'     => '/terminals/bulk',
            'content' => [
                'terminal_ids' => [

                ],
                'attributes'  =>  [
                    'status'  => 'activated'
                ]
            ],
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Terminal status should be activated or pending for enabling a terminal',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_STATUS_SHOULD_BE_ACTIVATED_OR_PENDING_TO_ENABLE
        ],
    ],

    'testUpdateTerminalsBulkTryStatusUpdateWithoutEnableField'    =>  [
        'request' => [
            'method'  => 'PATCH',
            'url'     => '/terminals/bulk',
            'content' => [
                'terminal_ids' => [

                ],
                'attributes'  =>  [
                    'status'  => 'activated'
                ]
            ],
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The enabled field must be present.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testQueryCacheforTerminals' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'tpv'                       => '2'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => '12345678',
                'enabled'              => true,
                'tpv'                  => 2
            ]
        ]
    ],

    'testCreateWalletPhonepeTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_phonepe',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_access_code'       => 'access_code',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
                'enabled_wallets'      => ['phonepe']
            ]
        ]
    ],

    'testCreateWalletPhonepeTerminalWrongWallet'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_phonepe',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_access_code'       => 'access_code',
                'enabled_wallets'           => ['amazonpay']
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "wallets is not supported for the gateway",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]
    ],

    'testCreateHdfcTerminalWithEnabledWallet'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'hdfc',
                'gateway_merchant_id'       => '12345567',
                'gateway_terminal_id'       => '12345567',
                'gateway_terminal_password' => '12345567',
                'gateway_acquirer'           => 'hdfc',
                'enabled_wallets'           => ['amazonpay']
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "enabled_wallets is not required and shouldn't be sent",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ]
    ],

    'testCreateWalletPhonepeSwitchTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_phonepeswitch',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_secure_secret2'    => 'access_code',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
                'enabled_wallets'      => ['phonepeswitch']
            ]
        ]
    ],

    'testCreateWalletPaypalTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_paypal',
                'gateway_merchant_id'       => 'merchant_id',
                'category'                  => '1234',
                'type'                      => [
                    'direct_settlement_with_refund' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
                'category'             => '1234',
                'enabled_wallets'      => ['paypal']
            ]
        ]
    ],

    'testCreateNetbankingSibTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_sib',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_access_code'       => 'gateway_access_code'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateNetbankingYesbTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_yesb',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateNetbankingIbkTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_ibk',
                'gateway_merchant_id'       => 'merchant_id',
                'gateway_secure_secret'     => 'secure_secret',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateNetbankingCanaraTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_canara',
                'gateway_merchant_id'       => 'merchant_id',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'merchant_id',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateWorldlineTerminal'  => [
        'request' => [
            'content' => [
                'merchant_id'               => '10000000000000',
                'gateway'                   => 'worldline',
                'expected'                  => '1',
                'card'                      => '1',
                'gateway_merchant_id'       => '037122003842039',
                'gateway_terminal_id'       => '70374018',
                'gateway_acquirer'          => 'axis',
                'gateway_terminal_password' => '9900991100',
                'mc_mpan'                   => '5122600004774122',
                'visa_mpan'                 => '4604901004774122',
                'rupay_mpan'                => '6100020004774141',
                'vpa'                       => 'MAB.037122003842039@AXISBANK',
                'type'                      => [
                    'non_recurring'                 => '1',
                    'bharat_qr'                     => '1',
                    'direct_settlement_with_refund' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '037122003842039',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateEzetapTerminal'  => [
        'request' => [
            'content' => [
                'merchant_id'               => '10000000000000',
                'gateway'                   => 'hdfc_ezetap',
                'card'                      => '1',
                'gateway_merchant_id'       => '037122003842039',
                'gateway_acquirer'          => 'hdfc',
                'type'                      => [
                    'pos' => '1',
                    'direct_settlement_with_refund' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '037122003842039',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateBilldeskTerminal'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'billdesk',
                'gateway_merchant_id'       => 'testmerchantid',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_access_code'       => 'gateway_access_code'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'testmerchantid',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateTerminalWithWrongGatewayCase'  => [
        'request' => [
            'content' => [
                'gateway'                   => 'upI_airtel',
                'gateway_merchant_id'       => 'MER0000000001202',
                'upi'                       => 1,
                'gateway_terminal_password' => 'abcd',
                'gateway_merchant_id2'      => 'rzp@apbl'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'MER0000000001202',
                'enabled'              => true,
            ]
        ]
    ],

    'testAssignUpiMindgateVirtualVPATerminal' => [
        'request'  => [
            'content' => [
                'gateway'                     => 'upi_mindgate',
                'gateway_acquirer'            => 'hdfc',
                'gateway_merchant_id'         => '12345',
                'gateway_merchant_id2'        => '12345678',
                'gateway_terminal_password'   => 'password',
                'upi'                         => 1,
                'type'                        => [
                    Terminal\Type::NON_RECURRING => '1',
                    Terminal\Type::UPI_TRANSFER  => '1',
                ],
                'virtual_upi_root'            => 'rzpy.',
                'virtual_upi_merchant_prefix' => 'payto00000',
                'virtual_upi_handle'          => 'hdfcbank',
            ],
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'hdfc',
                'gateway_merchant_id' => '12345',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignUpiMindgateVirtualVPATerminalWithoutConfig' => [
        'request'   => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_acquirer'          => 'hdfc',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => 'password',
                'upi'                       => 1,
                'type'                      => [
                    Terminal\Type::NON_RECURRING => '1',
                    Terminal\Type::UPI_TRANSFER  => '1',
                ],
            ],
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateUpiYesbankCollectTerminal'        => [
        'request' => [
            'content' => [
                'gateway'                       => 'upi_yesbank',
                'gateway_acquirer'              => 'yesbank',
                'category'                      => '1520',
                'gateway_merchant_id'           => 'YES0000000012026',
                'gateway_secure_secret'         => 'Something',
                'upi'                           => 1,
                'vpa'                           => 'abcd@some'
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'YES0000000012026',
                'gateway_acquirer'          => 'yesbank',
                'enabled'                   => true
            ]
        ]
    ],

    'testCreateUpiYesbankIntentTerminal'        => [
        'request' => [
            'content' => [
                'gateway'                       => 'upi_yesbank',
                'gateway_acquirer'              => 'yesbank',
                'category'                      => '1520',
                'gateway_merchant_id'           => 'YES0000000012026',
                'gateway_secure_secret'         => 'Something',
                'upi'                           => 1,
                'vpa'                           => 'abcd@some',
                'type'                          => [
                    'pay'                       => '1'
                ]
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'YES0000000012026',
                'gateway_acquirer'          => 'yesbank',
                'enabled'                   => true
            ]
        ]
    ],

    'testCreatePayuUpiTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'payu',
                'gateway_acquirer'              => 'payu',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret'         => '12344',
                'upi'                           => 1,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true
            ]
        ]
    ],

    'testCreatePayuWalletTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'payu',
                'gateway_acquirer'              => 'payu',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret'         => '12344',
                'enabled_wallets'               => ['amazonpay'],
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true,
                'enabled_wallets'           => ['amazonpay']
            ]
        ]
    ],

    'testCreateBilldeskOptimizerUpiTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'billdesk_optimizer',
                'gateway_acquirer'              => 'billdesk_optimizer',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret2'        => '12344',
                'upi'                           => 1,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true
            ]
        ]
    ],

    'testCreateCashfreeUpiTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'cashfree',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret'         => '12344',
                'upi'                           => 1,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true
            ]
        ]
    ],

    'testCreateJuspayTerminal'                => [
        'request' => [
            'content' => [
                'gateway'                       => 'upi_juspay',
                'gateway_acquirer'              => 'axis',
                'category'                      => '1234',
                'gateway_merchant_id'           => 'MER0000000000111',
                'gateway_merchant_id2'          => 'MERCHANNEL0000000000111',
                'gateway_secure_secret'         => 'NotUsedAsOfNow',
                'upi'                           => 1,
                'vpa'                           => 'abcd@some'
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'MER0000000000111',
                'gateway_acquirer'          =>  'axis',
                'enabled'                   => true
            ]
        ]
    ],

    'testEditJuspayTerminal'                  => [
        'request' => [
            'content' => [
                'category'                      => '1234',
                'gateway_secure_secret'         => 'NotUsedAsOfNow',
                'vpa'                           => 'abcd@some'
            ],
            'method' =>  'POST'
        ],
        'response'  => [
            'content'   => [
                'gateway_terminal_password'    => 'new_password',
                'enabled'                      => true
            ]
        ]
    ],
    'testCreateJuspayIntentTerminal'           =>  [
        'request'   => [
            'content'   => [
                'gateway'                       => 'upi_juspay',
                'gateway_acquirer'              => 'axis',
                'category'                      => '1234',
                'gateway_merchant_id'           => 'MER0000000000111',
                'gateway_merchant_id2'          => 'MERCHANNEL0000000000111',
                'gateway_secure_secret'         => 'NotUsedAsOfNow',
                'upi'                           => 1,
                'vpa'                           => 'abcd@some',
                'type'                          => [
                    'non_recurring'             => '1',
                    'pay'                       => '1'
                ]
            ]
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'MER0000000000111',
                'gateway_acquirer'          =>  'axis',
                'enabled'                   => true
            ]
        ]
    ],
    'testCreateUpiJuspayQrExpectedTerminal' => [
        'request'   => [
            'content'   => [
                'gateway'                       => 'upi_juspay',
                'gateway_acquirer'              => 'axis',
                'category'                      => '1234',
                'gateway_merchant_id'           => 'MER0000000000111',
                'gateway_merchant_id2'          => 'MERCHANNEL0000000000111',
                'gateway_secure_secret'         => 'NotUsedAsOfNow',
                'upi'                           => 1,
                'vpa'                           => 'abcd@some',
                'type'                          => [
                    'non_recurring'             => '1',
                    'pay'                       => '1'
                ],
                'expected'                      => 1
            ]
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'MER0000000000111',
                'gateway_acquirer'          => 'axis',
                'enabled'                   => true,
                'expected'                  => true,
            ]
        ]
    ],
    'testCreateUpiRzprblTerminal' => [
        'request'   => [
            'content'   => [
                'gateway'                       => 'upi_rzprbl',
                'gateway_acquirer'              => 'rbl',
                'category'                      => '1234',
                'gateway_merchant_id'           => 'VtaMPNpoFglc',
                'gateway_merchant_id2'          => 'KwwvvxPNpoTeli',
                'upi'                           => 1,
                'vpa'                           => 'kk@rzp',
                'type'                          => [
                    'non_recurring'             => '1',
                    'pay'                       => '1'
                ],
            ]
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'VtaMPNpoFglc',
                'gateway_acquirer'          => 'rbl',
                'enabled'                   => true,
            ]
        ]
    ],
    'testCreateCybersourceYesBTerminal'      => [
        'request'   => [
            'content'   => [
                'gateway'                   => 'cybersource',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_terminal_id'       => 'randommerchantid',
                'gateway_terminal_password' => 'randommerchantidrandommerchantidrandommerchantidrandommerchantid',
                'gateway_secure_secret'     => 'secure_secret',
                'gateway_secure_secret2'    => 'secure_secret2',
                'gateway_access_code'       => 'access_code',
                'gateway_acquirer'          => 'yesb',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_3ds'     => '0',
                    'recurring_non_3ds' => '1',
                ],
            ]
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_acquirer'          => 'yesb',
                'enabled'                   =>  true
            ]
        ]
    ],

    'testAssignTerminalWithNoAccountTypeAttribute' => [
        'request'   => [
            'content'   => [
                'bank_transfer'             => '1',
                'type'                      => [
                    'business_banking'  => '1',
                    'non_recurring'     => '1',
                    'numeric_account'   => '1',
                ],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS,
        ]
    ],

    'testAssignTerminalWithDifferentAccountTypeAttribute' => [
        'request'   => [
            'content'   => [
                'bank_transfer'             => '1',
                'type'                      => [
                    'business_banking'  => '1',
                    'non_recurring'     => '1',
                    'numeric_account'   => '1',
                ],
            ]
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '2323',
                'enabled'                   =>  true
            ]
        ]
    ],

    'testAssignTerminalWithDifferentAccountTypeAttributeForKotak' => [
        'request'   => [
            'content'   => [
                'bank_transfer'             => '1',
                'type'                      => [
                    'business_banking'  => '1',
                    'non_recurring'     => '1',
                    'numeric_account'   => '1',
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'account_type is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testTerminalFetchByIdAppAuth' => [
        'request'   => [
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => "terminal",
                'status' => "activated",
            ],
            'status_code'   => 200,
        ],
    ],

    'testTerminalFetchByIdAppAuthBadRequest' => [
        'request'   => [
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ]
    ],

    'testAssignUpiIciciVirtualVPATerminal' => [
        'request'  => [
            'url'     => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                     => Gateway::UPI_ICICI,
                'gateway_acquirer'            => 'icici',
                'gateway_merchant_id'         => '12345',
                'gateway_merchant_id2'        => 'rzr.payto00000@icici',
                'upi'                         => 1,
                'type'                        => [
                    Terminal\Type::NON_RECURRING => '1',
                    Terminal\Type::UPI_TRANSFER  => '1',
                ],
                'virtual_upi_root'            => 'rzr.',
                'virtual_upi_merchant_prefix' => 'payto00000',
                'virtual_upi_handle'          => 'icici',
            ],
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'gateway_acquirer'    => 'icici',
                'gateway_merchant_id' => '12345',
                'enabled'             => true
            ]
        ]
    ],

    'testAssignUpiIciciVirtualVPATerminalWithoutConfig' => [
        'request'   => [
            'url'     => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'              => Gateway::UPI_ICICI,
                'gateway_acquirer'     => 'icici',
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => 'rzr.payto00000@icici',
                'upi'                  => 1,
                'type'                 => [
                    Terminal\Type::NON_RECURRING => '1',
                    Terminal\Type::UPI_TRANSFER  => '1',
                ],
            ],
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testTerminalModePurchaseForAxisMigs' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'axis_migs',
                'gateway_acquirer'          => 'axis',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'randomsecuresecretwhichis32chars',
                'gateway_access_code'       => 'rndmcode',
                'gateway_terminal_id'       => 'randomterminalid',
                'gateway_terminal_password' => 'randomterminalpassword',
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::PURCHASE,
                'type'                      => [
                    'recurring_non_3ds'
                ],
            ]
        ],
    ],

    'testTerminalModeDualForAxisMigs' => [
        'request' => [
            'url' => '/merchants/100000Razorpay/terminals',
            'content' => [
                'gateway'                   => 'axis_migs',
                'gateway_acquirer'          => 'axis',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'randomsecuresecretwhichis32chars',
                'gateway_access_code'       => 'rndmcode',
                'gateway_terminal_id'       => 'randomterminalid',
                'gateway_terminal_password' => 'randomterminalpassword',
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_non_3ds' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'mode'                      => Terminal\Mode::DUAL,
                'type'                      => [
                    'recurring_non_3ds'
                ],
            ]
        ],
    ],
    'testTokenizeExistingTerminalMpans' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  5,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count' =>  2,
                'tokenization_failed_count'  =>  0,
            ],
        ],
    ],

    'testTokenizeExistingTerminalMpansWithTerminalIdInInput' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'terminal_ids' =>  ['terminalId'],
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count' =>  1,
                'tokenization_failed_count'  =>  0,
            ],
        ],
    ],

    'testTokenizeExistingMpansSingleMpanShouldAlsoGetTokenized' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  5,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count' =>  1,
                'tokenization_failed_count'  =>  0,
            ],
        ],
    ],

    'testTokenizeExistingTerminalMpansInputValidationFailure' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  -3,
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTokenizeExistingTerminalMpansSameFields' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  5,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count' =>  2,
                'tokenization_failed_count'  =>  0,
            ],
        ],
    ],

    'testTokenizeExistingTerminalMpansTransaction' => [
        'request' => [
            'url'     => '/terminals/mpans/tokenize',
            'method'  => 'POST',
            'content' => [
                'count' =>  5,
            ],
        ],
        'response' => [
            'content' => [
                'tokenization_success_count' =>  1,
                'tokenization_failed_count'  =>  1,
            ],
        ],
    ],

    'testCreateTerminalMpansShouldBeTokenized' => [
        'request' => [
            'content' => [
                'gateway' => 'isg',
                'gateway_merchant_id'  => '123452112',
                'gateway_merchant_id2' => '123452134',
                'gateway_terminal_id'  => '12345679',
                "type" => [
                    "non_recurring" => "1",
                    "bharat_qr" => "1",
                ],
                "mc_mpan"       => "5122600116743268",
                "visa_mpan"     => "4604901116743090",
                "rupay_mpan"    => "6100020116743712"
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => "isg",
                'mc_mpan' =>  "NTEyMjYwMDExNjc0MzI2OA==",
                'visa_mpan' =>  "NDYwNDkwMTExNjc0MzA5MA==",
                'rupay_mpan' =>  "NjEwMDAyMDExNjc0MzcxMg==",
            ]
        ]
    ],

    'testCreateTerminalMpansTokenizationFailure' => [
        'request' => [
            'content' => [
                'gateway' => 'isg',
                'gateway_merchant_id' => '123452112',
                'gateway_terminal_id' => '12345679',
                "type" => [
                    "non_recurring" => "1",
                    "bharat_qr" => "1",
                ],
                "mc_mpan"       => "5122600116743268",
                "visa_mpan"     => "4604901116743090",
                "rupay_mpan"    => "6100020116743712"
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\ServerErrorException::class,
            'internal_error_code' => 'SERVER_ERROR',
        ],
    ],

    'testAdminFetchTerminalShouldNotHaveOriginalMpans' => [
        'request' => [
            'url'     => '/admin/terminal',
            'method'  => 'GET',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSmsSyncSaveTerminalTestOtp'   =>  [
        'request' => [
            'url'     => '/terminal_test_otp',
            'method'  => 'POST',
            'content' => [
                'message' => "From: AD-HDFCBK\n OTP is 123456 for txn of INR 1.00 at Terminal Testing Mer on HDFC Bank card ending 6654. Valid till 17:36:20. Do not share OTP for security reasons"
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testCreateCheckoutDotComTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'checkout_dot_com',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateCheckoutDotComRecurringTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'checkout_dot_com',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'card'                  => 1,
                'type'                      => [
                    'recurring_non_3ds' => '1',
                    'recurring_3ds' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateCheckoutDotComNonRecurringTerminal' => [
        "request" => [
            'content' => [
                'gateway'               => 'checkout_dot_com',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testCreateCheckoutDotComTerminalAllTypes' => [
        "request" => [
            'content' => [
                'gateway'               => 'checkout_dot_com',
                'gateway_merchant_id'   => '323395bf6400747e2f43bbd9a93323',
                'card'                  => 1,
                'type'                      => [
                    'non_recurring' => '1',
                    'recurring_non_3ds' => '1',
                    'recurring_3ds' => '1'
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => '323395bf6400747e2f43bbd9a93323',
                'enabled'              => true,
            ]
        ]
    ],

    'testEditToCheckoutDotComRecurringTerminal' => [
        "request" => [
            'content' => [
                'type' => [
                    'recurring_non_3ds' => '1',
                    'recurring_3ds' => '1'
                ],
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content'  => [
                'gateway_merchant_id'  => 'abcd1',
                'enabled'              => true,
            ]
        ]
    ],

    'testTerminalEncryption' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => 'umesh12345678',
                'upi'                       => '1',
                'tpv'                       => '2',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '12345',
                'gateway_merchant_id2' => '12345678',
                'enabled'              => true,
                'tpv'                  => 2
            ]
        ]
    ],

    'testTerminalEncryptionAxisOrg' =>  [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_merchant_id'       => '123456',
                'gateway_merchant_id2'      => '123456789',
                'gateway_terminal_password' => 'umesh123456789',
                'upi'                       => '1',
                'tpv'                       => '2',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'  => '123456',
                'gateway_merchant_id2' => '123456789',
                'enabled'              => true,
                'tpv'                  => 2
            ]
        ]
    ],

    'testCreateWalletPayzappTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_payzapp',
                'category'                  => 7299,
                'network_category'          => 'food_and_beverage',
                'gateway_terminal_id'       => '98765431',
                'gateway_merchant_id'       => '98982332',
                'type'                      => [
                    'non_recurring' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'wallet_payzapp',
                'category'                  => '7299',
                'network_category'          => 'food_and_beverage',
            ]
        ]
    ],

    'testCreateWalletBajajTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'wallet_bajaj',
                'gateway_merchant_id'       => '98982332',
                'gateway_secure_secret'     => 'randomsecret123',
                'gateway_secure_secret2'    => 'randomsecret1234',
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['bajajpay'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'wallet_bajaj',
                'gateway_merchant_id'       => '98982332',
                //Secrets are not return back in response
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['bajajpay'],
            ]
        ]
    ],

    'testCreateWalletBoostTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                'gateway_secure_secret'     => 'randomsecret123',
                'gateway_secure_secret2'    => 'randomsecret1234',
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['boost'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                //Secrets are not return back in response
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['boost'],
            ]
        ]
    ],

    'testCreateWalletMCashTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                'gateway_secure_secret'     => 'randomsecret123',
                'gateway_secure_secret2'    => 'randomsecret1234',
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['mcash'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                //Secrets are not return back in response
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['mcash'],
            ]
        ]
    ],

    'testCreateWalletGrabPayTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                'gateway_secure_secret'     => 'randomsecret123',
                'gateway_secure_secret2'    => 'randomsecret1234',
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['grabpay'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                //Secrets are not return back in response
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['grabpay'],
            ]
        ]
    ],

    'testCreateWalletTouchNGoTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                'gateway_secure_secret'     => 'randomsecret123',
                'gateway_secure_secret2'    => 'randomsecret1234',
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['touchngo'],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'gateway'                   => 'eghl',
                'gateway_merchant_id'       => '98982332',
                //Secrets are not return back in response
                'gateway_access_code'       => '9591',
                'enabled_wallets'           => ['touchngo'],
            ]
        ]
    ],

    'testFetchMerchantsInfoForIIR' => [
        'request' => [
            'url'     => '/internal/iir/merchants',
            'method'  => 'GET',
            'content' => [
                'org_id' => 'org_'.Org::AXIS_ORG_ID,
                'merchant_ids' => ['testIIRMid1234', 'testIIRMid1235', 'testIIRMid1236']
            ],
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count'  => 2,
                'items'  => [
                    [
                        "id"                    => "testIIRMid1235",
                        "business_category"     => 'biz category 2',
                        "business_subcategory"  => 'biz subcategory',
                        "business_type"         => 'biz type 2',
                        "website"               => 'www.testIIRMid1235.com',
                        "category2"             => 'category2_1',
                        "org_id"                => "CLTnQqDj9Si8bx",
                        "entity"                =>  "merchant",
                        "admin"                 => true
                    ],
                    [
                        "id"                    => "testIIRMid1236",
                        "business_category"     => 'biz category 2',
                        "business_subcategory"  => 'biz subcategory',
                        "business_type"         => 'biz type 2',
                        "website"               => 'www.testIIRMid1236.com',
                        "category2"             => 'category2_1',
                        "org_id"                => "CLTnQqDj9Si8bx",
                        "entity"                =>  "merchant",
                        "admin"                 => true
                    ],
                ],
            ],
            'status_code'   => 200,
        ],
    ],

    'testGetSalesforceDetailsForMerchantIDs' => [
        'request' => [
            'url'     => '/internal/salesforce_details',
            'method'  => 'GET',
            'content' => [
                'merchant_ids' => ['random-MID-123', 'random-MID-124']
            ],
        ],
        'response' => [
            'content' => [
                    'random-MID-123' => [
                        'owner_role' => 'KAM'
                    ],
                    'random-MID-124' => [
                        'owner_role' => 'Sales'
                    ]
            ],
            'status_code'   => 200,
        ],
    ],

    'testCreateEmerchantpayTerminal' => [
        'request' => [
            'url' => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                  => 'emerchantpay',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => 'gateway_secure_secret',
                'gateway_secure_secret2'   => 'gateway_secure_secret2',
                'gateway_terminal_id'      => 'emtrustly',
                'app'                      =>  1,
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'      => '12344',
                'enabled_apps'             => [
                    'trustly'
                ],
                'app'                       => true,
            ]
        ],
    ],

    'testAssignTerminalWhenTerminalExistForEmerchantPayGatewayButDifferentGatewayMerchantId' => [
        'request' => [
            'url' => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                  => 'emerchantpay',
                'gateway_merchant_id'      => '12344',
                'gateway_secure_secret'    => 'gateway_secure_secret',
                'gateway_secure_secret2'   => 'gateway_secure_secret2',
                'gateway_terminal_id'      => 'emtrustly',
                'app'                      =>  1,
                'currency'                  => ["EUR"],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id'      => '12344',
                'enabled_apps'             => [
                    'trustly'
                ],
                'app'                       => true,
            ]
        ],
    ],

    'testCreateTerminalRupaySiHub' =>  [
        'request' => [
            'content' => [
                'gateway' => 'rupay_sihub',
                'card' => '1',
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway' => 'rupay_sihub',
                'enabled' => true,
                'status'  => 'activated',
            ]
        ],
        'status_code' => 200,
    ],

    'testEditTerminalWithoutGodMode' => [
        "request" => [
            'content' => [
                'gateway_merchant_id' => 'editedMid'
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "gateway_merchant_id is/are not required and should not be sent",
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],

    ],

    'testEditTerminalWithGodModeEdit' => [
        "request" => [
            'content' => [
                'gateway_merchant_id' => 'editedMid',
                'terminal_edit_god_mode' => true
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'gateway_merchant_id' => 'editedMid',
                'gateway_terminal_id' => 'tid12345',

            ],
            'status_code'   => 200,
        ],
    ],

    'testGetTerminalEditableFields' => [
        "request" => [
            'url' => '/terminals/editable_fields',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'hitachi' =>  [
                    "gateway_terminal_id",
                    "international",
                    "type",
                    "expected",
                    "gateway_acquirer",
                    "network_category",
                    "mc_mpan",
                    "visa_mpan",
                    "rupay_mpan",
                    "account_number",
                    "ifsc_code",
                    "status",
                    "procurer",
                    "mode",
                    "currency",
                ],
                'emi_sbi' => [
                    "enabled",
                    "procurer",
                    "status",
                ],
            ],
            'status_code'   => 200,
        ],
    ],

    'testDisableTerminalWithOnlyDsWhenOnlyOneTerminal' => [
        'request' => [
            'method'  => 'PUT',
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testDisableTerminalWithOnlyDsWhenMoreThanOneTerminal' => [
        'request' => [
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'enabled' => false,
                'status'  => 'deactivated'
            ],
            'status_code' => 200,
        ],
    ],

    'testUnassignTheOnlyNonDSTerminalOfMerchantWithOnlyDs' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testCreatePayuSodexoTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'payu',
                'gateway_acquirer'              => 'payu',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret'         => '12344',
                'card'                          => 1,
                'mode'                          => 2,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                    'sodexo' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true,
                'type'    => [
                    'non_recurring',
                    'direct_settlement_with_refund',
                    'sodexo',
                ],
            ]
        ]
    ],

    'testCreateOptimizerRazorpayTerminal'        => [
        'request' => [
            'url'     => '/merchants/10000000000000/terminals',
            'content' => [
                'gateway'                       => 'optimizer_razorpay',
                'gateway_acquirer'              => 'axis_vas',
                'gateway_merchant_id'           => '12344',
                'gateway_secure_secret'         => '12344',
                'card'                          => 1,
                'upi'                           => 1,
                'type'                      => [
                    'non_recurring' => '1',
                    'direct_settlement_with_refund' => '1',
                    'sodexo' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'  => [
                'gateway_merchant_id'       => '12344',
                'enabled'                   => true,
                'type'    => [
                    'non_recurring',
                    'direct_settlement_with_refund',
                    'sodexo',
                ],
            ]
        ]
    ],

];
