<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal;

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
                'upi'                       => true,
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
                'gateway_access_code'   => 'random_access_code'
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
];
