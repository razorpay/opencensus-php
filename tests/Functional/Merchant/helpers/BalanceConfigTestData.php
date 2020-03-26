<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Balance\BalanceConfig;

return [

    'testCreateBalanceConfigInvalidAutoSmallerNegativeLimit' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 400000,
                'negative_limit_manual'              => 60000,
                'type'                                => 'primary',
                'negative_transaction_flows'         => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The negative limit auto must be between 500000 and 50000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePrimaryBalanceConfig' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 5000000,
                'negative_limit_manual'              => 5000000,
                'type'                                => 'primary',
                'negative_transaction_flows'         => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                    'balance_id'                    => '100abc000abc00',
                    'type'                          => 'primary',
                    'negative_transaction_flows'   => ['transfer', 'refund', 'payment'],
                    'negative_limit_auto'           => 5000000,
                    'negative_limit_manual'         => 5000000
            ],
            'status_code' => 201
        ]
    ],

    'testCreateBankingBalanceConfig' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'          => 5000000,
                'negative_limit_manual'        => 4000000,
                'type'                          => 'banking',
                'negative_transaction_flows'   => ['payout'],
            ]
        ],
        'response' => [
            'content' => [
                        'balance_id'                    => '100def000def00',
                        'type'                          => 'banking',
                        'negative_transaction_flows'   => ['payout'],
                        'negative_limit_auto'           => 5000000,
                        'negative_limit_manual'         => 4000000
            ],
            'status_code' => 201
        ]
    ],

    'testCreateBalanceConfigInvalidGreaterNegativeLimit' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 5000000,
                'negative_limit_manual'              => 60000000,
                'type'                                => 'primary',
                'negative_transaction_flows'         => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'negative_limit_manual can not be greater than negative_limit_auto',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePrimaryBalanceConfigInvalidNegativeTransactionFlow' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'               => 5000000,
                'negative_limit_manual'             => 4000000,
                'type'                               => 'primary',
                'negative_transaction_flows'        => ['payout'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Flow [payout] is not in the allowed flows list.'.
                    ' Allowed flows for balance type primary are [payment,transfer,refund,adjustment]',

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePrimaryBalanceConfigNoNegativeTransactionFlow' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 5000000,
                'type'                                => 'primary',
            ]
        ],
        'response' => [
            'content' => [
                        'balance_id'                    => '100abc000abc00',
                        'type'                          => 'primary',
                        'negative_transaction_flows'   => ['payment'],
                        'negative_limit_auto'           => 5000000,
                        'negative_limit_manual'           => 0,
            ],
            'status_code' => 201,
        ],
    ],

    'testCreateBalanceConfigAlreadyExists' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 5000000,
                'negative_limit_manual'              => 6000000,
                'type'                                => 'primary',
                'negative_transaction_flows'         => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The balance id has already been taken.'
                    ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBalanceForNonExistingBalance' => [
        'request'  => [
            'url'       => '/balance_configs/100ghi000ghi00',
            'method'    => 'POST',
            'content'   => [
                'negative_limit_auto'                => 5000000,
                'negative_limit_manual'              => 6000000,
                'type'                                => 'primary',
                'negative_transaction_flows'         => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                        'code'          => ErrorCode::BAD_REQUEST_ERROR,
                        'description'   => 'Balance does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BALANCE_DOES_NOT_EXIST,
        ],

    ],

    'testGetBalanceConfigByConfigId' => [
        'request'  => [
            'url'    => '/balance_configs/100op000op00op',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'                            => '100op000op00op',
                'balance_id'                    => '100mn000mn00mn',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['refund'],
                'negative_limit_auto'          => 5000000,
                'negative_limit_manual'        => 5000000
            ],
            'status_code' => 200
        ]
    ],

    'testGetBalanceConfigsByMerchantId' => [
        'request'  => [
            'url'    => '/balance_configs',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'id'                            => '100op000op00op',
                        'balance_id'                    => '100mn000mn00mn',
                        'type'                          => 'primary',
                        'negative_transaction_flows'   => ['refund'],
                        'negative_limit_manual'        => 5000000,
                        'negative_limit_auto'          => 5000000
                    ],
                    '1' => [
                        'id'                            => '100st000st00st',
                        'balance_id'                    => '100qr000qr00qr',
                        'type'                          => 'banking',
                        'negative_transaction_flows'   => ['payout'],
                        'negative_limit_auto'        => 0,
                        'negative_limit_manual'        => 5000000
                    ]
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testGetBalanceConfigsByMerchantIdLiveMode' => [
        'request'  => [
            'url'    => '/balance_configs',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'type'                   => 'primary',
                        'negative_limit_auto'   => 0,
                        'negative_limit_manual' => 0,
                    ]
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testGetBalanceConfigsByMerchantIdLiveModeActivatedMerchant' => [
        'request'  => [
            'url'    => '/balance_configs',
            'method' => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    '0' => [
                        'id'                            => '100op000op00op',
                        'balance_id'                    => '100mn000mn00mn',
                        'type'                          => 'primary',
                        'negative_transaction_flows'   => ['refund'],
                        'negative_limit_manual'        => 5000000,
                        'negative_limit_auto'          => 5000000
                    ],
                    '1' => [
                        'id'                             => '100st000st00st',
                        'balance_id'                    => '100qr000qr00qr',
                        'type'                          => 'banking',
                        'negative_transaction_flows'   => ['payout'],
                        'negative_limit_auto'          => 0,
                        'negative_limit_manual'        => 5000000
                    ]
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testEditBalanceConfig' => [
        'request'  => [
            'url'       => '/balance_configs/100yz000yz00yz',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'         => 6000000,
                'type'                           => 'primary',
                'negative_transaction_flows'    => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100stu000stu00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer', 'refund', 'payment'],
                'negative_limit_auto'          => 5000000,
                'negative_limit_manual'        => 6000000
            ],
            'status_code' => 200
        ]
    ],

    'testEditBalanceConfigForZeroAutoAndManualLimits' => [
        'request'  => [
            'url'       => '/balance_configs/100yz000yz00yz',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'         => 6000000,
                'type'                           => 'primary',
                'negative_transaction_flows'    => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100stu000stu00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer', 'refund', 'payment'],
                'negative_limit_auto'          => 0,
                'negative_limit_manual'        => 6000000
            ],
            'status_code' => 200
        ]
    ],

    'testEditBalanceConfigForDifferentBalanceType' => [
        'request'  => [
            'url'       => '/balance_configs/100ab000ab00ab',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'         => 6000000,
                'type'                           => 'banking',
                'negative_transaction_flows'    => ['transfer', 'refund'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Flow [transfer,refund] is not in the allowed flows list.'.
                        ' Allowed flows for balance type banking are [payout,adjustment]',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBalanceConfigInvalidNegativeLimit' => [
        'request'  => [
            'url'       => '/balance_configs/100yz000yz00yz',
            'method'    => 'PATCH',
            'content'   => [
                'type'                       => 'primary',
                'negative_limit_manual'     => 60000000,
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The negative limit manual must be less than or equal to 50000000',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBalanceConfigInvalidNegativeTransactionFlowForPrimary' => [
        'request'  => [
            'url'       => '/balance_configs/100yz000yz00yz',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'         => 500000,
                'type'                           => 'primary',
                'negative_transaction_flows'    => ['payout'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Flow [payout] is not in the allowed flows list.'.
                        ' Allowed flows for balance type primary are [payment,transfer,refund,adjustment]',

                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBalanceConfigInvalidNegativeTransactionFlowForBanking' => [
        'request'  => [
            'url'       => '/balance_configs/100ab000ab00ab',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'           => 5000000,
                'type'                             => 'banking',
                'negative_transaction_flows'      => ['transfer'],
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Negative Flow [transfer] is not in the allowed flows list.'.
                        ' Allowed flows for balance type banking are [payout,adjustment]',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditBalanceConfigForRemoveNegativeTransactionFlows' => [
        'request'  => [
            'url'       => '/balance_configs/100yz000yz00yz',
            'method'    => 'PATCH',
            'content'   => [
                'negative_limit_manual'         => 6000000,
                'type'                           => 'primary',
                'negative_transaction_flows'    => ['transfer'],
            ]
        ],
        'response' => [
            'content' => [
                'id'                            => '100yz000yz00yz',
                'balance_id'                    => '100stu000stu00',
                'type'                          => 'primary',
                'negative_transaction_flows'   => ['transfer', 'payment'],
                'negative_limit_auto'          => 5000000,
                'negative_limit_manual'        => 6000000
            ],
            'status_code' => 200
        ]
    ],

    'testFetchSharedBankingBalances' => [
        'request'  => [
            'url'     => '/admin_balances?type=banking&account_type=shared',
            'method'  => 'GET',
            'content' => [],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'entity' => "collection",
                'count'  => 1,
                'items'  => [
                    [
                        'type'            => "banking",
                        'currency'        => "INR",
                        'name'            => null,
                        'balance'         => 100000,
                        'credits'         => 0,
                        'fee_credits'     => 0,
                        'refund_credits'  => 0,
                        'account_number'  => "2224440041626906",
                        'account_type'    => "shared",
                        'channel'         => null,
                        'last_fetched_at' => null,
                    ]
                ]
            ]
        ]
    ]
];
