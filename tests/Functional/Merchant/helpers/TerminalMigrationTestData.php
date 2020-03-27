<?php

use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use \RZP\Models\Payment\Gateway;
use RZP\Error\PublicErrorDescription;


return [
    'testAssignTerminalInternalAuthMigrateVariant' => [
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

            ],
        ],
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
];
