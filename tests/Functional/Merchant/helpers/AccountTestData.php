<?php

namespace RZP\Tests\Functional\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testRetrieveAccount' => [
        'request'  => [
            'url'    => '/beta/accounts/acc_10000000000001',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id' => 'acc_10000000000001',
            ],
        ],
    ],

    'testRetrieveAccounts' => [
        'request'  => [
            'url'    => '/beta/accounts',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'acc_10000000000001',
                    ]
                ],
            ],
        ],
    ],

    'testRetrieveLinkedAccounts' => [
        'request'  => [
            'url'    => '/accounts',
            'method' => 'get',
            'content'   => [
                'skip'  => 0,
                'count' => 100
            ]
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'acc_10000000000001',
                    ]
                ],
            ],
        ],
    ],

    'createLinkedAccount' => [
        'request'  => [
            'url'     => '/beta/accounts',
            'method'  => 'post',
            'content' => [
                'name'            => 'Linked Account 1',
                'email'           => 'linked1@account.com',
                'tnc_accepted'    => true,
                'notes'           => [
                    'custom_account_id' => 'Qwerty123',
                    'custom_attribute'  => 'some_value',
                ],
                'account_details' => [
                    'business_name' => 'Acme solutions',
                    'business_type' => 'proprietorship',
                ],
                'bank_account' => [
                    'ifsc_code'             => 'ICIC0001206',
                    'account_number'        => '0002020000304030434',
                    'account_type'          => 'current',
                    'beneficiary_name'      => 'Test R4zorpay'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'name'               => 'Linked Account 1',
                'email'              => 'linked1@account.com',
                'entity'             => 'account',
                'live'               => true,
                'tnc_accepted'       => true,
                'managed'            => true,
                'activation_details' => [
                    'status'         => 'activated',
                    'fields_pending' => [],
                ],
                'secondary_emails'   => [
                    'transaction_report_email' => [],
                ],
                'account_details'    => [
                    'mobile'                   => null,
                    'landline'                 => null,
                    'business_name'            => 'Acme solutions',
                    'business_type'            => 'proprietorship',
                    'paymentdetails'           => null,
                    'business_model'           => null,
                    'registered_address'       => [
                        'address' => null,
                        'city'    => null,
                        'state'   => null,
                        'pin'     => null
                    ],
                    'operational_address'      => [
                        'address' => null,
                        'city'    => null,
                        'state'   => null,
                        'pin'     => null
                    ],
                    'date_established'         => null,
                    'transaction_volume'       => null,
                    'average_transaction_size' => null,
                    'kyc_details'              => [
                        'cin'                 => null,
                        'gstin'               => null,
                        'p_gstin'             => null,
                        'pan'                 => null,
                        'pan_name'            => null,
                        'promoter_pan'        => null,
                        'promoter_pan_name'   => null,
                        'business_proof_file' => null,
                        'address_proof_file'  => null
                    ]
                ],
                'notes'              => [
                    'custom_account_id' => 'Qwerty123',
                    'custom_attribute'  => 'some_value',
                ],
                'fund_transfer'      => [],
            ]
        ],
    ],

    'testCreateLinkedAccountValidationFailure' => [
        'request'   => [
            'url'     => '/beta/accounts',
            'method'  => 'post',
            'content' => [
                'name'         => 'Linked Account 1',
                'email'        => 'linked1@account.com',
                'tnc_accepted' => true
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account details field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateLinkedAccountInvalidBusinessType' => [
        'request'   => [
            'url'     => '/beta/accounts',
            'method'  => 'post',
            'content' => [
                'name'            => 'Linked Account 1',
                'email'           => 'linked1@account.com',
                'tnc_accepted'    => true,
                'account_details' => [
                    'business_name' => 'Test Acc',
                    'business_type' => 'random_incorrect',
                ],
                'bank_account'    => [
                    'ifsc_code'      => 'ICIC0001206',
                    'account_number' => '0002020000304030434',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid business type: random_incorrect',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'addSettlementDestination' => [
        'request'  => [
            'content' => [
                'ifsc_code'            => 'ICIC0001206',
                'account_number'       => '0002020000304030434',
                'beneficiary_name'     => 'Test R4zorpay',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_email'    => 'random@email.com',
                'beneficiary_mobile'   => '9988776655',
                'beneficiary_city'     => 'Kolkata',
                'beneficiary_state'    => 'WB',
                'beneficiary_country'  => 'IN',
                'beneficiary_pin'      => '123456',
            ],
            'url'     => '/accounts/acc_10000000000000/bank_accounts',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'ifsc'           => 'ICIC0001206',
                'bank_name'      => 'ICICI Bank',
                'name'           => 'Test R4zorpay',
                'account_number' => '0002020000304030434',
            ]
        ]
    ],

    'fetchSettlementDestinations' => [
        'request'  => [
            'content' => [],
            'url'     => '/accounts/acc_10000000000000/settlement_destinations',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items'  => [
                    [
                        'entity'         => 'bank_account',
                        'ifsc'           => 'ICIC0001206',
                        'bank_name'      => 'ICICI Bank',
                        'name'           => 'Test R4zorpay',
                        'account_number' => '0002020000304030434',
                    ]
                ]
            ]
        ]
    ],
];
