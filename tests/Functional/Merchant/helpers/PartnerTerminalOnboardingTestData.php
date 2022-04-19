<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testEnableTerminal'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'entity'              => 'terminal',
                'status'              => 'activated',
                'enabled'             =>  true,
                'notes'               =>  'some notes',
                'mpan'                =>  [
                    'mc_mpan'             => '1234567890123456',
                    'visa_mpan'           => '9876543210123456',
                    'rupay_mpan'          => '1234123412341234'
                ]
            ]
        ]
    ],

    'testEnableTerminalFailedOnGateway'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'Terminal enable failed on gateway',
                ],
            ],
            'status_code' => 502
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TERMINAL_ENABLE_FAILED
        ],
    ],

    'testEnableTerminalAlreadyEnabledOnGateway'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'entity'              => 'terminal',
                'status'              => 'activated',
                'enabled'             =>  true,
                'notes'               =>  'some notes',
                'mpan'                =>  [
                    'mc_mpan'             => '1234567890123456',
                    'visa_mpan'           => '9876543210123456',
                    'rupay_mpan'          => '1234123412341234'
                ]
            ]
        ]
    ],

    'testDisableTerminal'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'entity'              => 'terminal',
                'status'              => 'deactivated',
                'enabled'             =>  false,
                'notes'               =>  'some notes',
                'mpan'                =>  [
                    'mc_mpan'             => '1234567890123456',
                    'visa_mpan'           => '9876543210123456',
                    'rupay_mpan'          => '1234123412341234'
                ]
            ]
        ]
    ],

    'testDisablePendingTerminal'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'entity'              => 'terminal',
                'status'              => 'deactivated',
                'enabled'             =>  false,
                'notes'               =>  'some notes',
                'mpan'                =>  [
                    'mc_mpan'             => '1234567890123456',
                    'visa_mpan'           => '9876543210123456',
                    'rupay_mpan'          => '1234123412341234'
                ]
            ]
        ]
    ],

    'testDisableTerminalFailedOnGateway'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => 'Terminal disable failed on gateway',
                ],
            ],
            'status_code' => 502
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TERMINAL_DISABLE_FAILED
        ],
    ],

    'testDisableTerminalAlreadyDisabledOnGateway'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'entity'              => 'terminal',
                'status'              => 'deactivated',
                'enabled'             =>  false,
                'notes'               =>  'some notes',
                'mpan'                =>  [
                    'mc_mpan'             => '1234567890123456',
                    'visa_mpan'           => '9876543210123456',
                    'rupay_mpan'          => '1234123412341234'
                ]
            ]
        ]
    ],

    'testOnlyDeactivatedTerminalsShouldBeEnabled'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only deactivated terminals can be enabled',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ONLY_DEACTIVATED_TERMINALS_CAN_BE_ENABLED
        ],
    ],

    'testSubMerchantsShouldNotBeAbleToDisableTerminals'  => [
        'request' => [
            'method' => 'PUT'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Merchant is not a partner',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER
        ],
    ],

    'testFetchTerminals'  => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content'  => [
                'count'   => 2,
                'entity'  => 'collection',
                'items'   => [
                    [
                        'entity'  => 'terminal',
                        'status'  => 'pending',
                        'enabled' => false,
                        'notes'   => null,
                        'mpan' => [
                            'mc_mpan'    => '5220240401208405',
                            'rupay_mpan' => '6100030401208403',
                            'visa_mpan'  => '4403844012084006'
                        ]
                    ],
                    [
                        'entity'  => 'terminal',
                        'status'  => 'activated',
                        'enabled' => true,
                        'notes'   => null,
                        'mpan' => [
                            'mc_mpan'    => '4287346823986423',
                            'rupay_mpan' => '6287346823986423',
                            'visa_mpan'  => '5287346823986423'
                        ]
                    ]
                ]
            ]
        ],
        'expected_passport' => [
            'mode'          => 'test',
            'identified'    => true,
            'authenticated' => true,
            'domain'        => 'razorpay',
            'consumer'      => [
                'type' => 'merchant',
                'id'   => '10000000000000',
            ],
            'impersonation' => [
                'type'     => 'partner',
                'consumer' => [
                    // 'id' => 'GtDgJK5g0e5EeR',
                ],
            ],
            'credential' => [
                // 'username'   => 'rzp_test_partner_GtD7rnNaPwUMJT',
                // 'public_key' => 'rzp_test_partner_GtD7rnNaPwUMJT-acc_GtDgJK5g0e5EeR',
            ],
        ],
    ],

    'testPartnerWithoutTerminalOnboardingFeatureShouldNotBeAbleToFetchTerminals'  => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Terminal onboarding feature is disabled',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_DISABLED
        ],
    ],

    'testSubMerchantsShouldNotBeAbleToFetchTerminals'  => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Merchant is not a partner',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER
        ],
    ],

    'testTerminalOnboardingCreateTerminal' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005789',
                  'visa'        => '4604901005005799',
                  'rupay'       => '6100020005005792'
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'entity'   => 'terminal',
                'enabled'  => true,
                'status'   => 'pending',
                'mpan'     => [
                    'mc_mpan'       =>  '5122600005005789',
                    'rupay_mpan'    =>  '6100020005005792',
                    'visa_mpan'     =>  '4604901005005799'
                ],
            ]
        ]
    ],

    'testTerminalOnboardingCreateTerminalWithBarredMcc' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005789',
                  'visa'        => '4604901005005799',
                  'rupay'       => '6100020005005792'
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The merchant`s mcc is barred.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MCC_IS_BARRED
        ],
    ],

    'testTerminalOnboardingCreateTerminalAdditionalTidFlow' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'entity'   => 'terminal',
                'enabled'  => true,
                'status'   => 'pending',
                'mpan'     => [
                    'mc_mpan'       =>  '5122600005005813',
                    'rupay_mpan'    =>  '6100020005005826',
                    'visa_mpan'     =>  '4604901005005823'
                ]
            ]
        ]
    ],

    'testTerminalOnboardingCreateTerminalForNonActivatedMerchant' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '1234567880123456',
                  'visa'        => '1234567890123456',
                  'rupay'       => '1234567890123457'
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The merchant has not been activated. This action can only be taken for activated merchants',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED
        ],
    ],

    'testTerminalOnboardingCreateTerminalWithSameFields' => [
        'request' => [
            'content' => [
                'mpan' => [
                    'mastercard'  => '5122600005005789',
                    'visa'        => '4604901005005799',
                    'rupay'       => '6100020005005792'
                  ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'A terminal with the same field exists - <terminalId>',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_WITH_SAME_FIELD_ALREADY_EXISTS
        ],
    ],

    'testTerminalOnboardingCreateTerminalWithSameFieldsExistingTerminalIsFailed' => [
        'request' => [
            'content' => [
                'mpan' => [
                    'mastercard'  => '5122600005005789',
                    'visa'        => '4604901005005799',
                    'rupay'       => '6100020005005792'
                  ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'entity'   => 'terminal',
                'enabled'  => true,
                'status'   => 'pending',
                'mpan'     => [
                    'mc_mpan'       =>  '5122600005005789',
                    'rupay_mpan'    =>  '6100020005005792',
                    'visa_mpan'     =>  '4604901005005799'
                ]

            ]
        ]
    ],

    'testTerminalOnboardingCreateTerminalWithMpansNotIssued'    =>  [
        'request' => [
            'url'     => '/terminals',
            'content' => [
                'mpan' => [
                    'mastercard'  => '1234567890123456',
                    'visa'        => '2234567890123456',
                    'rupay'       => '3234567890123456'
                  ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The MPAN used is not issued to your account.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MPAN
        ],
    ],

    'testTerminalOnboardingCreateTerminalWithSwappedNetworks'    =>  [
        'request' => [
            'url'     => '/terminals',
            'content' => [
                'mpan' => [
                    'mastercard'  => '5122600005005789',
                    'visa'        => '5122600005005961',
                    'rupay'       => '6100020005005792'
                  ]
              ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The MPAN used does not belong to the network.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MPAN_FOR_NETWORK
        ],
    ],

    'testTerminalOnboardingCreateTerminalWithSwappedNetworks2' => [
        'request' => [
            'url'     => '/terminals',
            'content' => [
                'mpan' => [
                    'mastercard'  => '4604901005005799',
                    'visa'        => '5122600005005995',
                    'rupay'       => '6100020005005792'
                  ]
              ],
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The MPAN used does not belong to the network.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MPAN_FOR_NETWORK
        ],
    ],

    // Validation failure by mozart
    'testTerminalOnboardingCreateTerminalCase2' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => '',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    // invalid tid error from gateway
    'testTerminalOnboardingCreateTerminalCase3' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Invalid Terminal ID',
                ],
            ],
            'status_code' => 502
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR
        ],
    ],

    // mozart hits invalid gateway route
    'testTerminalOnboardingCreateTerminalCase4' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => '',
                ],
            ],
            'status_code' => 500
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR
        ],
    ],

    // duplicate mpan is used
    'testTerminalOnboardingCreateTerminalCase5' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Duplicate MVISAPAN',
                ],
            ],
            'status_code' => 502
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_DATA
        ],
    ],

    'testTerminalOnboardingCreateTerminalGatewayErrorByMozart' => [
        'request' => [
            'content' => [
                'mpan' => [
                  'mastercard'  => '5122600005005813',
                  'visa'        => '4604901005005823',
                  'rupay'       => '6100020005005826'
                ]
            ],
            'url'    => '/terminals',
            'method' => 'POST'
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Terminal onboarding failed on gateway',
                ],
            ],
            'status_code' => 502
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_TERMINAL_ONBOARDING_FAILED
        ],
    ],
];

