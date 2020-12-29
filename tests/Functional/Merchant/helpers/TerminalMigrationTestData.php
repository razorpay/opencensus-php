<?php

use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Error\PublicErrorCode;
use \RZP\Models\Payment\Gateway;
use RZP\Error\PublicErrorDescription;


return [
    'testAssignTerminalInternalAuthMigrateVariant' => [
        'request' => [
            'content' => [
                'id'                         => "EAswe1856fg349",
                'gateway'                    => 'wallet_paypal',
                'gateway_merchant_id'        => 'gateway_merchant_id',
                'type'                       =>  [
                    'direct_settlement_with_refund' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testAssignTerminalInternalAuthMigrateVariantFulcrum' => [
        'request' => [
            'content' => [
                'id'                         => "FVkV1bgreKuciM",
                'gateway'                    => 'fulcrum',
                'gateway_terminal_id'        => '1000000d',
                'gateway_merchant_id'        => '10000000000000d',
                'currency'                   => ['INR'],
                'card'                       => '1',
                'type'                       =>  [
                    'non_recurring' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAssignTerminalInternalAuthMigrateVariantPaysecure' => [
        'request' => [
            'content' => [
                'id'                         => "TVrV1teaePuciQ",
                'gateway'                    => 'paysecure',
                'gateway_acquirer'           => 'axis',
                'mode'                       => 3,
                'gateway_terminal_id'        => 'axis000d',
                'gateway_merchant_id'        => 'axis0000000000d',
                'currency'                   => ['INR'],
                'card'                       => '1',
                'status'                     => 'pending',
                'enabled'                    => '0',
            ],
        ],
        'response' => [
            'content' => [
                'gateway'   =>  'paysecure',
                'mode'      =>  3,
                'enabled'   =>  false,
                'card'      =>  true,
                'status'    =>  'pending',
            ],
        ],
    ],

    'testAssignTerminalInternalAuthMissingId' => [
        'request' => [
            'content' => [
                'gateway'                    => 'wallet_paypal',
                'gateway_merchant_id'        => 'gateway_merchant_id',
                'type'                       =>  [
                    'direct_settlement_with_refund' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id must be of length 14',
                ]
            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TERMINAL_ID,
        ]
    ],
    'testAssignTerminalInternalAuthExistingId' => [
        'request' => [
            'content' => [
                'gateway'                    => 'wallet_paypal',
                'gateway_merchant_id'        => 'gateway_merchant_id',
                'type'                       =>  [
                    'direct_settlement_with_refund' => '1',
                ],
            ],
        ]
    ],
    'testAssignTerminalTerminalServiceUpMigrateTerminalVariant' => [
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

    'testAssignTerminalTerminalServiceDownMigrateTerminalVariant' => [
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
    ],

    'testAssignTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant' => [
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
    ],

    'testAssignTerminalsServiceFailureResponseMigrateTerminalVariant' => [
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

    'testAssignTerminalControlVariant' => [
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

    'testUpdateTerminalTerminalsServiceUpMigrateTerminalVariant' => [
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

    'testUpdateTerminalTerminalsServiceDownMigrateTerminalVariant' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
            'method' => 'PUT'
        ],
    ],

    'testUpdateTerminalServiceSuccessResponseBadValuesMigrateTerminalVariant' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
            'method' => 'PUT'
        ],
        'response'  => [

        ],
    ],

    'testUpdateTerminalServiceFailureResponseMigrateTerminalVariant' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
            'method' => 'PUT'
        ],
    ],

    'testUpdateTerminalServiceSubmerchantMismatchResponseMigrateTerminalVariant' => [
        'request' => [
            'content' => [
                'toggle' => '0',
                'remarks'  => 'Disabling terminal because of some reason',
            ],
            'method' => 'PUT'
        ],
        'response'  => [

        ],
    ],


    'testUpdateTerminalControlVariant' => [
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

    'testDeleteTerminalNoPaymentTerminalsServiceUpMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalNoPaymentTerminalsServiceUpTerminalDoesntExistOnTerminalsService' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalNoPaymentTerminalsServiceDownMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalNoPaymentTerminalsServiceUpBadResponseOnTerminalFetchMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalNoPaymentControlVariant' => [
        'request' => [
            'url' => '/merchants/10abcdefghsdfs/terminals/testatomrandom',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalWithPaymentTerminalsServiceUpMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalWithPaymentTerminalsServiceDownMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalWithPaymentTerminalsServiceUpBadResponseMigrateVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testDeleteTerminalWithPaymentControlVariant' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testAdminFetchTerminalByIdTerminalServiceValidResponse' => [
        'request'    => [
            'method'    => 'GET'
        ],
        'response'   => [
            'content'   => [

            ],
        ],
    ],

    'testAdminFetchTerminalByIdTerminalServiceValidResponseProxy' => [
        'request'    => [
            'method'    => 'GET'
        ],
        'response'   => [
            'content'   => [
                'merchant_id' => '100000Razorpay',
                'procurer' => 'razorpay',
                'enabled' => true,
                'gateway' => 'axis_migs'
            ],
        ],
    ],

    'testCheckEncryptedValueTerminalServiceValidResponseProxy' => [
        'request'    => [
            'method'    => 'POST',
            'content'   => [
                'gateway_terminal_password' => 'testpassword',
                'gateway_terminal_password2' => 'testpassword',
                'gateway_secure_secret' => 'testsecret',
                'gateway_secure_secret2' => 'testsecret'
            ]
        ],
        'response'   => [
            'content'   => [

            ],
        ],
    ],

    'testFetchTerminalProxy' => [
        'request'    => [
            'method'    => 'GET',
        ],
        'response'   => [
            'content'   => [
                [
                    'merchant_id' => '10000000000000',
                    'entity' => 'terminal',
                    'procurer' => 'razorpay',
                    'enabled' => true,
                    'gateway' => 'hdfc'
                ]
            ],
        ],
    ],

    'testAdminFetchTerminalByIdTerminalServiceInvalidResponse' => [
        'request'    => [
            'method'    => 'GET'
        ],
        'response'   => [
            'content'   => [

            ],
        ],
    ],

    'testFetchTerminalsAdminAuthTerminalIdMismatch' => [
        'request' => [
          'method'  => 'GET',
          'url'     => '/merchants/10000000000000/terminals'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],
    'testFetchTerminalsAdminAuthTerminalFieldMismatch' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/merchants/10000000000000/terminals'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],
    'testFetchTerminalsAdminAuth' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/merchants/10000000000000/terminals'
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],
    'testFetchTerminalsAdminAuthProxy' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/merchants/10000000000000/terminals'
        ],
        'response' => [
            'content' => [
                "count" => 2
            ],
        ],
    ],
    'testSyncDeletedTerminals' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/terminals/sync/deleted'
        ],
        'response' => [
            'content' => [
                "count" => 7
            ],
        ],
    ],

    'testTerminalServiceProxyDeleteTerminalSubmerchant' => [
        'request' => [
            'method'  => 'DELETE',
            'url'     => '/terminals/proxy/terminal/submerchant',
            'content' => [
                'terminal_id' => '10000000000000',
                'merchant_id'  => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'data' => null
            ],
        ],
    ],

    'testTerminalServiceProxyCreateGatewayCredential' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/terminals/proxy/gateway_credentials',
            'content' => [
                'id' => '10000000000000',
                'secrets'  => ["secret1"=> "test1"],
            ],
        ],
        'response' => [
            'content' => [
                'id' => "123456789asdfg",
                "gateway_credential_id" => "12345678901234"
            ],
        ],
    ],

    'testTerminalServiceProxyCreateTerminalSubmerchant' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/terminals/proxy/terminal/submerchant',
            'content' => [
                'terminal_id' => '1000000000000t',
                'merchant_id' => '1000000000000m',
            ],
        ],
        'response' => [
            'content' => [
                'data' => [
                    'id'            => '1000000000000t',
                    'submerchants'  => ['1000000000000m'],
                ]
            ],
        ],
    ],

    'test4xxException' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/terminals/sync/deleted',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'foo',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_TERMINALS_SERVICE_ERROR',
        ]
    ],

    'test5xxException' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/terminals/sync/deleted'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The server encountered an error. The incident has been reported to admins.'
                ]
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class' => \RZP\Exception\IntegrationException::class,
            'internal_error_code' => 'SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR',
        ]
    ],
];
