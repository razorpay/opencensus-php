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

    'testArchiveAndCreateNewAccount' => [
        'request'  => [
            'url'     => '/bas/archive_banking_account_dependencies',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testArchive' => [
        'request'  => [
            'url'     => '/bas/archive_banking_account_dependencies',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testArchiveRbl' => [
        'request'  => [
            'url'     => '/bas/archive_banking_account_dependencies',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testArchiveAndUnArchive' => [
        'request'  => [
            'url'     => '/bas/archive_banking_account_dependencies',
            'method'  => 'POST',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetMerchantAttributes' => [
        'request'  => [
            'url'     => '/bas/merchant_attributes/10000000000000/x_merchant_current_accounts',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id' => '10000000000000',
                    'product' => 'banking',
                    'group' => 'x_merchant_current_accounts',
                    'type' => 'ca_allocated_bank',
                    'value'=> 'RBL'
                ],
                [
                    'merchant_id' => '10000000000000',
                    'product' => 'banking',
                    'group' => 'x_merchant_current_accounts',
                    'type' => 'ca_proceeded_bank',
                    'value'=> 'RBL'
                ],
            ],
        ],
    ],


    'testCreateBankingEntitiesWithLedgerShadow' => [
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

    'testFetchMerchantDetailsInfo'    =>  [
        'request'       =>  [
            'method'    =>  'GET',
            'url'       =>  '/internal/merchants/{id}'
        ],
        'response'      =>  [
            'content'   => [
                'merchant' => [
                    'id'        => '10000000000000',
                ],
                'merchant_detail' => [
                    'contact_email' => 'test@razorpay.com',
                    'contact_mobile' => '+919876543210'
                ]
            ],
            'status_code'   =>  200
        ]
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

    'testPinCodeServiceabilityBulk' => [
        'request'  => [
            'url'     => '/bas/banking_application/check_pin_code_serviceability_bulk',
            'method'  => 'GET',
            'content' => [
                'pin_code' => '833216',
                'business_type' => 'PRIVATE_LIMITED',
                'business_category' => 'ECOMMERCE',
            ]
        ],
        'response' => [
            'content' => [
                'data' => [
                    [
                        'bank'           => 'RBL',
                        'account_type'   => '',
                        'reasons'        => ['PIN_CODE_UNSERVICEABLE'],
                        'is_serviceable' => false
                    ],
                    [
                        'bank'           => 'ICICI',
                        'account_type'   => '',
                        'reasons'        => null,
                        'is_serviceable' => true
                    ],
                ]
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

    'testSlotBookingForBankingAccountForMerchantWithClarityContext' => [
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

    'testSlotRescheduleForBankingAccount' => [
        'request'  => [
            'url'     => '/booking/slot/reschedule',
            'method'  => 'POST',
            'content' => [
                'id' => 'randomBaAccId8',
                'channel' => 'rbl',
                'merchantName' => 'Test Merchant',
                'merchantEmail' => 'test@razorpay.com',
                'phoneNumber' => '9876543210',
                'slotDateAndTime' => '17-Nov-2021 13:30:00'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSlotRescheduleForBankingAccountIfDateAndTimeOfBookingIsSame' => [
        'request'  => [
            'url'     => '/booking/slot/reschedule',
            'method'  => 'POST',
            'content' => [
                'id' => 'randomBaAccId8',
                'channel' => 'rbl',
                'merchantName' => 'Test Merchant',
                'merchantEmail' => 'test@razorpay.com',
                'phoneNumber' => '9876543210',
                'slotDateAndTime' => '17-Nov-2021 14:30:00'
            ]
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSlotRescheduleForBankingAccountIfAdditionalDetailsIsEmpty' => [
        'request'  => [
            'url'     => '/booking/slot/reschedule',
            'method'  => 'POST',
            'content' => [
                'id' => 'randomBaAccId8',
                'channel' => 'rbl',
                'merchantName' => 'Test Merchant',
                'merchantEmail' => 'test@razorpay.com',
                'phoneNumber' => '9876543210',
                'slotDateAndTime' => '17-Nov-2021 14:30:00'
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

    'testRecentAvailableSlotsForBankingAccount' => [
        'request'  => [
            'url'    => '/booking/slot/recentSlots',
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
                'status' => 'Failure',
                'ErrorDetail' => [
                    "errorReason" => 'Slot is already booked for the same date and time, it cannot be booked again'
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

    'testSendCaLeadStatusToSalesForce' => [
        'request'  => [
            'url'     => '/bas/ca_lead_status_updates_to_salesforce',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID  => '10000000000000',
                'ca_id'                 => 'bacc_10000000000000',
                'ca_type'               => 'ICICI',
                'ca_status'             => 'created'
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendCaLeadToFreshDesk' => [
        'request'  => [
            'url'     => '/bas/ca_lead_to_freshdesk',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID               => '10000000000000',
                Constants::CA_PREFERRED_PHONE        => '33322323',
                Constants::CA_PREFERRED_EMAIL        => 'abc@def.com',
                'merchant_name'                      => 'test merchant',
                'merchant_email'                     => 'test@test.com',
                'merchant_phone'                     => '929292929',
                'constitution'                       => 'PRIVATE_LIMITED',
                'pincode'                            => '332332',
                'sales_team'                         => 'SELF_SERVE',
                'account_manager_name'               => 'test_name',
                'account_manager_email'              => 'testemail@test.com',
                'account_manager_phone'              => '33332222',
            ]
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendIciciVideoKycLeadToFreshDesk' => [
        'request'  => [
            'url'     => '/bas/ca_lead_to_freshdesk',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID               => '10000000000000',
                Constants::CA_PREFERRED_PHONE        => '33322323',
                Constants::CA_PREFERRED_EMAIL        => 'abc@def.com',
                'merchant_name'                      => 'test merchant',
                'merchant_email'                     => 'test@test.com',
                'merchant_phone'                     => '929292929',
                'constitution'                       => 'PRIVATE_LIMITED',
                'pincode'                            => '332332',
                'sales_team'                         => 'SELF_SERVE',
                'account_manager_name'               => 'test_name',
                'account_manager_email'              => 'testemail@test.com',
                'account_manager_phone'              => '33332222',
                'banking_account_application_type'   => 'ICICI_VIDEO_KYC_APPLICATION'
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

    'testBasNotifyXProActivation' => [
        'request'  => [
            'url'     => '/bas/banking_accounts/notifications',
            'method'  => 'POST',
            'content' => [
                [
                    'notification_type' => 'x_pro_activation',
                    'banking_account'   => [
                        'id'                                 => '10000000000000',
                        'merchant_id'                        => '10000000000000',
                        'banking_account_activation_details' => [
                            'assignee_name'         => 'Foo',
                            'additional_details'    => [],
                            'booking_date_and_time' => 1590521524,
                            'sales_team'            => 'sme',
                        ],
                        'bank_reference_number'              => '1590521524',
                        'created_at'                         => 1590521524,
                        'pincode'                            => '560002',
                        'status'                             => 'created',
                        'sub_status'                         => null
                    ],
                    'validator_op'      => 'create_normal'
                ],
            ]
        ],
        'response' => [
            'content' => [
                [
                    'banking_account_id' => '10000000000000',
                    'success'            => true,
                    'error'              => null
                ]
            ],
        ],
    ],

    'testBasNotifyStatusChange' => [
        'request'  => [
            'url'     => '/bas/banking_accounts/notifications',
            'method'  => 'POST',
            'content' => [
                [
                    'notification_type' => 'status_change',
                    'banking_account'   => [
                        'id'                                 => '10000000000000',
                        'account_ifsc'                       => 'HDFC0003780',
                        'account_number'                     => '10000000000000',
                        'merchant_id'                        => '10000000000000',
                        'banking_account_activation_details' => [
                            'assignee_name'         => 'Foo',
                            'additional_details'    => [],
                            'booking_date_and_time' => 1590521524,
                            'contact_verified'      => 1,
                            'sales_team'            => 'sme',
                        ],
                        'bank_reference_number'              => '1590521524',
                        'created_at'                         => 1590521524,
                        'pincode'                            => '560002',
                        'status'                             => 'created',
                        'sub_status'                         => 'null',
                        'spocs'                              => [
                            [
                                'name'                      => 'foo',
                                'email'                     => 'spocs@foo.com',
                            ],
                        ],
                        'reviewers'                              => [
                            [
                                'name'                      => 'foo',
                                'email'                     => 'reviewers@foo.com',
                            ],
                        ],
                    ],
                    'banking_account_status_changed'         => true,
                    'banking_account_sub_status_changed'     => false,
                ],
            ],
        ],
        'response' => [
            'content' => [
                [
                    'banking_account_id' => '10000000000000',
                    'success'            => true,
                    'error'              => null
                ]
            ],
        ],
    ],

    'testBasNotifySubStatusChange' => [
        'request'  => [
            'url'     => '/bas/banking_accounts/notifications',
            'method'  => 'POST',
            'content' => [
                [
                    'notification_type' => 'status_change',
                    'banking_account'   => [
                        'id'                                 => '10000000000000',
                        'banking_account_activation_details' => [
                            'contact_verified'      => 0,
                        ],
                        'merchant_id'                        => '10000000000000',
                        'bank_reference_number'              => '1590521524',
                        'created_at'                         => 1590521524,
                        'pincode'                            => '560002',
                        'status'                             => 'created',
                        'sub_status'                         => 'null',
                        'spocs'                              => [
                            [
                                'name'                      => 'foo',
                                'email'                     => 'spocs@foo.com',
                            ],
                        ],
                        'reviewers'                              => [
                            [
                                'name'                      => 'foo',
                                'email'                     => 'reviewers@foo.com',
                            ],
                        ],
                    ],
                    'banking_account_status_changed'         => false,
                    'banking_account_sub_status_changed'     => true,
                ],
            ],
        ],
        'response' => [
            'content' => [
                [
                    'banking_account_id' => '10000000000000',
                    'success'            => true,
                    'error'              => null
                ]
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

    'testCreateSignatory' => [
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

    'testOnboardCapitalCorpCardForPayouts'          => [
        'request'  => [
            'url'     => '/merchant/onboardCCCForBanking',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID    => '10000000000000',
                Constants::ACCOUNT_NUMBER => '30091673424181'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDuplicateOnboardCapitalCorpCardForPayouts' => [
        'request'  => [
            'url'     => '/merchant/onboardCCCForBanking',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID    => '10000000000000',
                Constants::ACCOUNT_NUMBER => '30091673424181'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testInvalidMerchantIdOnboardCapitalCorpCardForPayouts'      => [
        'request'  => [
            'url'     => '/merchant/onboardCCCForBanking',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID    => '10034000000000',
                Constants::ACCOUNT_NUMBER => '30091673424181'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testNot14CharMerchantIdOnboardCapitalCorpCardForPayouts'    => [
        'request'  => [
            'url'     => '/merchant/onboardCCCForBanking',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID    => '10000000',
                Constants::ACCOUNT_NUMBER => '30091673424181'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "The merchant id must be 14 characters.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNot14CharAccountNumberOnboardCapitalCorpCardForPayouts' => [
        'request'  => [
            'url'     => '/merchant/onboardCCCForBanking',
            'method'  => 'POST',
            'content' => [
                Constants::MERCHANT_ID    => '10000000000000',
                Constants::ACCOUNT_NUMBER => '1244'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "The account number must be 14 characters.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testTokenizeValueViaVault' => [
        'request' => [
            'url'       => '/bas/tokenize_values',
            'method'    => 'POST',
            'content'   => [
                'secrets'   => [
                    [
                        'key'   => 'dummy-key',
                        'value' => 'dummy-value'
                    ]
                ]
            ]
        ],
        'response'  => [
            'content'   => [
                'tokenized_values'  => [
                    [
                        'key'   => 'dummy-key',
                        'token' => 'dummy-token'
                    ]
                ]
            ]
        ]
    ]
];
