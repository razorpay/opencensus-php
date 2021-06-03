<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankingAccountService\Constants;

return [
    'testCreateBankingEntities' => [
        'request'  => [
            'url'     => '/bas/merchant/10000000000000/banking_accounts',
            'method'  => 'POST',
            'content' => [
                Constants::ACCOUNT_NUMBER => '12345678903833',
                Constants::CHANNEL        => 'icici',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateBankingEntitiesAndAddPayoutFeature' => [
        'request'  => [
            'url'     => '/bas/merchant/10000000000000/banking_accounts',
            'method'  => 'POST',
            'content' => [
                Constants::ACCOUNT_NUMBER => '12345678903833',
                Constants::CHANNEL        => 'icici',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateBusinessId' => [
        'request'  => [
            'url'     => '/merchant/banking_application/business/',
            'method'  => 'POST',
            'content' => [
                'name' => 'Razorpay',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCron' => [
        'request'  => [
            'url'     => '/bas/banking_application/cron/poll/status/123456',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testVendorPaymentCompositeExpands' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/vendor-payments/composite-expands',
            'content' => [
                'user_ids'         => ['10000000000000'],
                'fund_account_ids' => ['fa_D6Z9Jfir2egAUD'],
                'contact_ids'      => ['cont_Dsp92d4N1Mmm6Q'],
                'payout_ids'       => ['pout_DuuYxmO7Yegu3x'],
                'merchant_ids'     => ['10000000000000'],
            ],
        ],
        'response' => [
            'content' => [
                'merchants'     => [],
                'users'         => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'   => '10000000000000',
                            'name' => 'test-me'
                        ]
                    ]
                ],
                'fund_accounts' => [
                    'fa_D6Z9Jfir2egAUT' => [
                        'id'           => 'fa_D6Z9Jfir2egAUT',
                        'account_type' => 'bank_account'
                    ],
                    'fa_D6Z9Jfir2egAUD' => [
                        'id'           => 'fa_D6Z9Jfir2egAUD',
                        'account_type' => 'bank_account'
                    ]
                ],
                'contacts'      => [
                    'cont_Dsp92d4N1Mmm6Q' => [
                        'id'   => 'cont_Dsp92d4N1Mmm6Q',
                        'name' => 'test_contact'
                    ]
                ],
                'payouts'       => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'                 => 'pout_DuuYxmO7Yegu3x',
                            'fund_account_id'    => 'fa_D6Z9Jfir2egAUT',
                            'fund_account'       => [
                                'id'      => 'fa_D6Z9Jfir2egAUT',
                                'contact' => [
                                    'id'   => 'cont_Dsp92d4N1Mmm6Q',
                                    'name' => 'test_contact'
                                ]
                            ],
                            'banking_account_id' => 'bacc_10000000000011',
                        ]
                    ]
                ]
            ]
        ]
    ],

    'testBusinessApplicationSignatories' => [
        'request'  => [
            'url'     => '/merchant/banking_application/business/10000000000000/applications/10000000000000',
            'method'  => 'PATCH',
            'content' => [
                'application_specific_fields' => [
                    'isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg' => 'N',
                    'isIndianFinancialInstitution'                                    => 'Y',
                    'isOwnerNotIndianCitizen'                                         => 'N',
                    'isTaxResidentOutsideIndia'                                       => 'Y',
                    'role_in_business'                                                => 'ACCOUNTANT',
                    'business_document_mapping' => [
                        'entityProof1' => 'AADHAR',
                        'entityProof2' => 'PANCARD'
                    ]
                ],
                'signatories'                 => [
                    'person'         => [
                        'first_name'                            => 'asd',
                        'last_name'                             => 'asd',
                        'nationality'                           => 'BRITISH OVERSEAS TERRITORY',
                        'date_of_birth'                         => '2021-05-06T06:30:00.000Z',
                        'gender'                                => 'Male',
                        'marital_status'                        => 'Single',
                        'father_name'                           => 'asdasd',
                        'mother_name'                           => 'asdasda',
                        'current_residential_address_details'   => [
                            'address_building_name' => 'asd',
                            'address_street_name'   => '',
                            'address_landmark'      => '',
                            'address_locality'      => '',
                            'address_pin_code'      => '560036',
                            'address_city'          => 'KURTUMGARH',
                            'address_state'         => 'GOA',
                        ],
                        'permanent_residential_address_details' => [
                            'address_building_name' => 'asd',
                            'address_street_name'   => '',
                            'address_landmark'      => '',
                            'address_locality'      => '',
                            'address_pin_code'      => '560036',
                            'address_city'          => 'KURTUMGARH',
                            'address_state'         => 'GOA',
                        ],
                        'role_in_business'                      => 'ACCOUNTANT',
                    ],
                    'signatory_type' => 'AUTHORIZED_SIGNATORY',
                    'document' => [
                        'idProof' => 'PANCARD',
                        'addressProof' => 'AADHAAR',
                    ]
                ],
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBusinessApplicationSignatoriesWithDocCollectionDetails' => [
        'request'  => [
            'url'     => '/merchant/banking_application/business/10000000000000/applications/10000000000000',
            'method'  => 'PATCH',
            'content' => [
                'application_specific_fields' => [
                    'isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg' => 'N',
                    'isIndianFinancialInstitution'                                    => 'Y',
                    'isOwnerNotIndianCitizen'                                         => 'N',
                    'isTaxResidentOutsideIndia'                                       => 'Y',
                    'role_in_business'                                                => 'ACCOUNTANT',
                    'business_document_mapping' => [
                        'entityProof1' => 'AADHAR',
                        'entityProof2' => 'PANCARD'
                    ]
                ],
                'signatories'                 => [
                    'person'         => [
                        'first_name'                            => 'asd',
                        'last_name'                             => 'asd',
                        'nationality'                           => 'BRITISH OVERSEAS TERRITORY',
                        'date_of_birth'                         => '2021-05-06T06:30:00.000Z',
                        'gender'                                => 'Male',
                        'marital_status'                        => 'Single',
                        'father_name'                           => 'asdasd',
                        'mother_name'                           => 'asdasda',
                        'current_residential_address_details'   => [
                            'address_building_name' => 'asd',
                            'address_street_name'   => '',
                            'address_landmark'      => '',
                            'address_locality'      => '',
                            'address_pin_code'      => '560036',
                            'address_city'          => 'KURTUMGARH',
                            'address_state'         => 'GOA',
                        ],
                        'permanent_residential_address_details' => [
                            'address_building_name' => 'asd',
                            'address_street_name'   => '',
                            'address_landmark'      => '',
                            'address_locality'      => '',
                            'address_pin_code'      => '560036',
                            'address_city'          => 'KURTUMGARH',
                            'address_state'         => 'GOA',
                        ],
                        'role_in_business'                      => 'ACCOUNTANT',
                    ],
                    'signatory_type' => 'AUTHORIZED_SIGNATORY',
                    'document' => [
                        'idProof' => 'PANCARD',
                        'addressProof' => 'AADHAAR',
                    ]
                ],
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
