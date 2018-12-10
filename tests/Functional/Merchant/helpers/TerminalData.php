<?php

use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use \RZP\Models\Payment\Gateway;
use RZP\Error\PublicErrorDescription;

return [
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
                'category'            => 4567,
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
                'category'            => 4567,
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
                    'description' => 'Editing not defined for used terminal of gateway: '.Gateway::BT_YESBANK,
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
        'response' =>  [
            'content' => [
                'merchant_id'          => '100001Razorpay',
                'gateway'              => Gateway::BT_YESBANK,
                'gateway_merchant_id'  => '222334',
                'gateway_merchant_id2' => '01',
                'type'                 => [
                    'non_recurring',
                    Terminal\Type::ALPHA_NUMERIC_ACCOUNT,
                ],
                'bank_transfer'             => true,
            ]
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
                    'description' => PublicErrorDescription::BAD_REQUEST_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS,
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
                'category'            => 4567,
                'enabled'             => true
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
                'mc_mpan'             => '1234567880123456',
                'visa_mpan'           => '1234567890123456',
                'rupay_mpan'          => '1234567890123456',
                'category'            => 4567,
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
                    'description' => PublicErrorDescription::BAD_REQUEST_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS,
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
                    'description' => PublicErrorDescription::BAD_REQUEST_FIELD_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FIELD_ALREADY_EXISTS,
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
                'gateway'               => 'atom',
                'netbanking'            => 1,
                'gateway_merchant_id'   => '12345',
                'gateway_secure_secret' => 'random_secret',
                'gateway_access_code'   => 'random_access_code',
                'network_category'      => 'ecommerce',
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
                'gateway' => 'netbanking_kotak',
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
                'category'            => 4567,
                'enabled'             => true,
                'enabled_banks'       => ['KKBK'],
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

    'testCreateDirectSettlemtTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'netbanking_kotak',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'type'                      => [
                    'non_recurring'     => '1',
                    'direct_settlement' => '1',
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

    'testCreateDirectSettlemtTerminalFailure' => [
        'request' => [
            'content' => [
                'gateway'                   => 'upi_mindgate',
                'gateway_merchant_id'       => '12345',
                'gateway_merchant_id2'      => '12345678',
                'gateway_terminal_password' => '12345678',
                'upi'                       => '1',
                'tpv'                       => '2',
                'type'                      => [
                    'non_recurring'     => '1',
                    'direct_settlement' => '1',
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
                'toggle' => '0'
            ],
        'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'enabled' => false
            ]
        ]
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
                'gateway'                   => 'axis_migs',
                'gateway_acquirer'          => 'axis',
                'gateway_merchant_id'       => 'randommerchantid',
                'gateway_secure_secret'     => 'randomsecuresecretwhichis32chars',
                'gateway_access_code'       => 'rndmcode',
                'gateway_terminal_id'       => 'randomterminalid',
                'gateway_terminal_password' => 'randomterminalpassword',
                'mode'                      => Terminal\Mode::DUAL,
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
                    'description' => 'axis_migs terminals must be in AuthCapture mode',
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
            'class' => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED ,
        ],
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
                'enabled'                   => true
            ]
        ]
    ],

    'testAssignIsgBharatQrTerminal' => [
        'request' => [
            'content' => [
                'gateway'                   => 'isg',
                'gateway_merchant_id'       => 'random',
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
                'mc_mpan'             => '1234567880123456',
                'visa_mpan'           => '1234567890123456',
                'rupay_mpan'          => '1234567890123456',
                'enabled'             => true
            ]
        ]
    ],

    'testAddIsgBharatQrTerminalFailed' => [
        'request' => [
            'content' => [
                'gateway'                   => 'isg',
                'gateway_merchant_id'       => 'random',
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
                    'ANDB'   => "Andhra Bank",
                    'BKID'   => "Bank of India",
                    'MAHB'   => "Bank of Maharashtra",
                    'CNRB'   => "Canara Bank",
                    'CBIN'   => "Central Bank of India",
                    'CIUB'   => "City Union Bank",
                    'CORP'   => "Corporation Bank",
                    'DCBL'   =>"DCB Bank",
                    'DEUT'   => "Deutsche Bank",
                    'DLXB'   => "Dhanlaxmi Bank",
                    'ESFB'   => "Equitas Small Finance Bank",
                    'IBKL'   =>"IDBI",
                    'IDIB'   => "Indian Bank",
                    'IOBA'   => "Indian Overseas Bank",
                    'JAKA'   => "Jammu and Kashmir Bank",
                    'KARB'   => "Karnataka Bank",
                    'KVBL'   => "Karur Vysya Bank",
                    'LAVB_R' => "Lakshmi Vilas Bank - Retail Banking",
                    'PMCB'   => "Punjab & Maharashtra Co-operative Bank",
                    'PSIB'   => "Punjab & Sind Bank",
                    'PUNB_R' => "Punjab National Bank - Retail Banking",
                    'SRCB'   => "Saraswat Co-operative Bank",
                    'SIBL'   => "South Indian Bank",
                    'SCBL'   => "Standard Chartered Bank",
                    'SBBJ'   => "State Bank of Bikaner and Jaipur",
                    'SBHY'   => "State Bank of Hyderabad",
                    'SBIN'   => "State Bank of India",
                    'SBMY'   => "State Bank of Mysore",
                    'STBP'   => "State Bank of Patiala",
                    'SBTR'   => "State Bank of Travancore",
                    'TMBL'   => "Tamilnadu Mercantile Bank",
                    'UCBA'   =>"UCO Bank",
                    'UBIN'   => "Union Bank of India",
                    'UTBI'   => "United Bank of India",
                    'VIJB'   => "Vijaya Bank",
                ],
                'disabled' => [
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
                    'MAHB' => "Bank of Maharashtra",
                    'CIUB' => "City Union Bank",
                    'DCBL' => "DCB Bank",
                    'DEUT' => "Deutsche Bank",
                    'DLXB' => "Dhanlaxmi Bank",
                    'IBKL' => "IDBI",
                    'IDIB' => "Indian Bank",
                    'JAKA' => "Jammu and Kashmir Bank",
                    'KVBL' => "Karur Vysya Bank",
                    'LAVB_R' => "Lakshmi Vilas Bank - Retail Banking",
                    'SRCB' => "Saraswat Co-operative Bank",
                    'SBIN' => "State Bank of India",
                    'TMBL' => "Tamilnadu Mercantile Bank",
                    'YESB' => "Yes Bank",
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
                    'UTIB_C' => "Axis Bank - Corporate Banking",
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
                    'description' => 'Banks available only for netbanking gateways',
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
                    'SBIN'   => "State Bank of India",
                ],
                'disabled' => [
                    'ANDB'   => "Andhra Bank",
                    'BKID'   => "Bank of India",
                    'MAHB'   => "Bank of Maharashtra",
                    'CNRB'   => "Canara Bank",
                    'CBIN'   => "Central Bank of India",
                    'CIUB'   => "City Union Bank",
                    'CORP'   => "Corporation Bank",
                    'DCBL'   =>"DCB Bank",
                    'DEUT'   => "Deutsche Bank",
                    'DLXB'   => "Dhanlaxmi Bank",
                    'ESFB'   => "Equitas Small Finance Bank",
                    'IBKL'   =>"IDBI",
                    'IDIB'   => "Indian Bank",
                    'IOBA'   => "Indian Overseas Bank",
                    'JAKA'   => "Jammu and Kashmir Bank",
                    'KARB'   => "Karnataka Bank",
                    'KVBL'   => "Karur Vysya Bank",
                    'LAVB_R' => "Lakshmi Vilas Bank - Retail Banking",
                    'PMCB'   => "Punjab & Maharashtra Co-operative Bank",
                    'PSIB'   => "Punjab & Sind Bank",
                    'PUNB_R' => "Punjab National Bank - Retail Banking",
                    'SRCB'   => "Saraswat Co-operative Bank",
                    'SIBL'   => "South Indian Bank",
                    'SCBL'   => "Standard Chartered Bank",
                    'SBBJ'   => "State Bank of Bikaner and Jaipur",
                    'SBHY'   => "State Bank of Hyderabad",
                    'SBMY'   => "State Bank of Mysore",
                    'STBP'   => "State Bank of Patiala",
                    'SBTR'   => "State Bank of Travancore",
                    'TMBL'   => "Tamilnadu Mercantile Bank",
                    'UCBA'   =>"UCO Bank",
                    'UBIN'   => "Union Bank of India",
                    'UTBI'   => "United Bank of India",
                    'VIJB'   => "Vijaya Bank",
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
                    'description' => 'Banks available only for netbanking gateways',
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
                    'HDFC' => "HDFC Bank",
                ],
                'disabled' => [
                ],
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
                    'HDFC'   => "HDFC Bank",
                ],
                'disabled' => [
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
                'gateway_secure_secret'     => 'random_secret',
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
];
