<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\BankingAccountService\Constants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

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

    'testInitiateBVSValidationForPersonalPan' => [
        'request'  => [
            'url'     => '/bas/bvs_validation',
            'method'  => 'POST',
            'content' => [
                Constant::ARTEFACT_TYPE     => Constant::PERSONAL_PAN,
                Constant::OWNER_TYPE        => Constant::BAS_DOCUMENT,
                Constant::OWNER_ID          => 'D6Z9Jfir2egAUT',
                Constant::DETAILS           => [
                    Constant::NAME          => 'Sample',
                    Constant::PAN_NUMBER    => 'RZP3W2345L'
                ],
            ]
        ],
        'response' => [
            'content' => [
                Constant::OWNER_TYPE        => Constant::BAS_DOCUMENT,
                Constant::OWNER_ID          => 'D6Z9Jfir2egAUT',
                Entity::VALIDATION_STATUS   => BvsValidationConstants::INITIATED
            ],
        ],
    ],

    'testInitiateBVSValidationForBusinessPan' => [
        'request'  => [
            'url'     => '/bas/bvs_validation',
            'method'  => 'POST',
            'content' => [
                Constant::ARTEFACT_TYPE     => Constant::BUSINESS_PAN,
                Constant::OWNER_TYPE        => Constant::BAS_DOCUMENT,
                Constant::OWNER_ID          => 'D6Z9Jfir2egAUT',
                Constant::DETAILS           => [
                    Constant::NAME          => 'Sample',
                    Constant::PAN_NUMBER    => 'RZP3W2345L'
                ],
            ]
        ],
        'response' => [
            'content' => [
                Constant::OWNER_TYPE        => Constant::BAS_DOCUMENT,
                Constant::OWNER_ID          => 'D6Z9Jfir2egAUT',
                Entity::VALIDATION_STATUS   => BvsValidationConstants::INITIATED
            ],
        ],
    ],

    'testCreateBankingEntitiesAndAddPayoutFeatureAndAllowHasKeyAccess' => [
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

    'testCreateBusinessWithIndividualConstitution' => [
        'request'  => [
            'url'     => '/merchant/banking_application/business/',
            'method'  => 'POST',
            'content' => [
                'name'          => 'Razorpay',
                'constitution'  => 'individual',
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

    'testLmsAll' => [
        'request'  => [
            'url'     => '/bas/lms/business/',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testBusinessIdAssigmentInLMSWhileApplyToBankingAccount' => [
        'request'  => [
            'url'     => '/bas/lms/admin/apply',
            'method'  => 'POST',
            'content' => [
                'application_type' => 'ICICI_ONBOARDING_APPLICATION',
                'merchant_id' => '10000000000000',
                'pincode' => '324010',
                'sales_team' => 'X_GROWTH'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLmsErrorFromBas' => [
        'request'  => [
            'url'     => '/bas/lms/search/wrongUrl/',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLmsOps' => [
        'request'  => [
            'url'     => '/bas/lms_ops/business/',
            'method'  => 'POST',
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
                            'banking_account_id' => 'bacc_30000000000888',
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

    'testFetchMerchantInfo' => [
        'request'  => [
            'url'     => '/merchants_internal/{id}',
            'method'  => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'id'     => '10000000000000',
                'email'  => 'test@razorpay.com',
                'org_id' => '100000razorpay',
            ],
        ],
    ],

    'testPinCodeServiceabilityForIcici' => [
        'request'  => [
            'url'     => '/bas/banking_application/check_pin_code_serviceability',
            'method'  => 'GET',
            'content' => [
                'pincode' => '345231',
                'business_type' => 'PRIVATE_LIMITED',
                'application_type' => 'ICICI_ONBOARDING_APPLICATION',
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSlotBookingForBankingAccount' => [
        'request'  => [
            'url'     => '/booking/slot/book',
            'method'  => 'POST',
            'content' => [
                'id' => 'randomBaAccId8',
                'channel' => 'rbl',
                'merchantName' => 'Test Merchant',
                'merchantEmail' => 'test@razorpay.com',
                'phoneNumber' => '9876543210',
                'slotDateAndTime' => '17-Nov-2021 11:30:00'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAvailableSlotsForBankingAccount' => [
        'request'  => [
            'url'    => '/booking/slot/availableSlots?currentDate=17-Nov-2021',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSlotBookingForBankingAccountIfThatSlotIsAlreadyBooked' => [
        'request'  => [
            'url'     => '/booking/slot/book',
            'method'  => 'POST',
            'content' => [
                'id' => 'randomBaAccId8',
                'channel' => 'rbl',
                'merchantName' => 'Test Merchant',
                'merchantEmail' => 'test@razorpay.com',
                'phoneNumber' => '9876543210',
                'slotDateAndTime' => '17-Nov-2021 11:30:00'
            ]
        ],
        'response' => [
            'content' => [
                'bookingDetails' => null,
                'status' => 'failure',
                'ErrorDetail' => [
                    "errorReason" => 'Slot is already booked for the same date and time,
                                          it cannot be booked again'
                ],
            ],
        ],
    ],

    'testDeleteSignatory' => [
        'request'  => [
            'url'     => '/merchant/banking_application/business/10000000000000/applications/10000000000000/person/20000000000000/signatory/40000000000000',
            'method'  => 'DELETE',
            'content' => []
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testSendCaLeadToSalesForce' => [
        'request'  => [
            'url'     => '/bas/ca_lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
                Constants::SOURCE             => 'X-CA-Unified',
                Constants::MERCHANT_ID        => '10000000000000',
                Constants::CA_PREFERRED_PHONE => '',
                Constants::CA_PARTNER_BANK    => 'ICICI',
                Constants::CA_PREFERRED_EMAIL => '',
                Constants::PRODUCT_NAME       => 'Current_Account',
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendRblApplicationInProgressLeadsToSalesForce' => [
        'request'  => [
            'url'     => '/cron/rbl/lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendRblCreatedLeadsFilledNotSubmittedWithin24hrs' => [
        'request'  => [
            'url'     => '/cron/rbl/lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendRblCreatedLeadsFilledNotSubmittedWithin24hrsForNitro' => [
        'request'  => [
            'url'     => '/cron/rbl/lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendRblApplicationLeadsToSalesForce' => [
        'request'  => [
            'url'     => '/cron/rbl/lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendRblApplicationLeadsHavingXSMEStateToSalesForce' => [
        'request'  => [
            'url'     => '/cron/rbl/lead_to_salesforce',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testUpdateSignatory' => [
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
                    "person_id" => "20000000000000",
                    "signatory_id" => "40000000000000",
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
