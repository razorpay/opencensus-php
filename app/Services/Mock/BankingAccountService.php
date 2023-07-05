<?php

namespace RZP\Services\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement\Channel;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway\Axis;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\BankingAccount\Gateway\Fields;
use RZP\Models\BankingAccount\Gateway\Yesbank;
use RZP\Models\BankingAccount\Gateway\Rbl as RblGateway;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\BankingAccountService\Channel as BASChannel;

class BankingAccountService
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function fetchAccountDetails(string $merchantId)
    {
        return
            [
                'id'                          => 'GvZfe7jTGCWNTO',
                'associated_account_managers' => null,
                'owner_id'                    => 'GvZfe7jTGCWNTO',
                'owner_type'                  => 'BUSINESS',
                'account_number'              => '2224440041626905',
                'status'                      => 'ACTIVE',
                'account_type'                => '',
                'partner_bank'                => '',
                'balance_id'                  => '',
                'account_currency'            => '',
                'ifsc'                        => '',
                'urn'                         => '',
                'alias_id'                    => '',
                'preference'                  => null,
                'metadata'                    => null,
                'fts_fund_account_id'         => 'GvZfe7jTGCWNTO',
            ];
    }

    public function getBusinessDetails($merchantId)
    {
        return [
            "name" => "RazorpayX",
        ];
    }

    public function getGeneratedRblCredentials(string $bankingAccountId)
    {
        if ($bankingAccountId == '1000000invalid')
        {
            return [
                'banking_account_id'       => $bankingAccountId,
                'merchant_id'              => '',
                'merchant_name'            => '',
                'email'                    => '',
                'dev_portal_password'      => '',
                'ldap_id'                  => '',
                'ldap_password'            => '',
                'upi_handle1'              => '',
                'upi_handle2'              => '',
                'upi_handle3'              => '',
                'mcc_code'                 => '',
            ];
        }

        return [
            'banking_account_id'       => $bankingAccountId,
            'merchant_id'              => 'L6NxGyvDkztFol',
            'merchant_name'            => 'TEST MERCHANT',
            'email'                    => 'x.rbl..4@razorpay.com',
            'dev_portal_password'      => 'RERPD32rhbtg',
            'ldap_id'                  => '4BK27SE1V1',
            'ldap_password'            => 'TMAYH38ymhbp',
            'upi_handle1'              => 'testUsername@rzp',
            'upi_handle2'              => 'payouts.puv27-2@rbl',
            'upi_handle3'              => 'payouts.rrp73-3@rbl',
            'mcc_code'                 => '6012',
        ];
    }

    public function generatedRblCredentials(string $bankingAccountId, $content)
    {
        return $this->getGeneratedRblCredentials($bankingAccountId);
    }

    public function getDocketPdfUrl(string $bankingAccountId, $businessCategory, $merchantName)
    {
        if ($bankingAccountId == '1000000invalid')
        {
            return null;
        }
        return 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
    }

    public function fetchBankingCredentials($merchantId, string $channel = 'icici', string $accountNumber = '1234566')
    {
        switch($channel)
        {
            case Channel::ICICI:
                //ICICI test ca credentials required for payouts testing in dark.
                return [
                    Icici\Fields::CORP_ID       => 'RAZORPAY12345',
                    Icici\Fields::CORP_USER     => 'USER12345',
                    Icici\Fields::URN           => 'URN12345',
                    Icici\Fields::CREDENTIALS   => null
                ];

            case Channel::YESBANK:
                return [
                    'id'                => 'bas20000000000',
                    'corp_id'           => '',
                    'user_id'           => '',
                    'urn'               => '',
                    'account_number'    => $accountNumber,
                    Fields::CREDENTIALS => [
                        Yesbank\Fields::AES_KEY             => 'aes123456',
                        Yesbank\Fields::APP_ID              => 'RAZORPAYX',
                        Yesbank\Fields::AUTH_PASSWORD       => 'random_pass',
                        Yesbank\Fields::AUTH_USERNAME       => 'random_user',
                        Yesbank\Fields::CLIENT_ID           => 'client_123',
                        Yesbank\Fields::CLIENT_SECRET       => 'random_pass',
                        Yesbank\Fields::CUSTOMER_ID         => 'customer123',
                        Yesbank\Fields::GATEWAY_MERCHANT_ID => 'YESB0000101',
                    ]
                ];

            case Channel::AXIS:
                return [
                    'id'                => 'bas30000000000',
                    'corp_id'           => '',
                    'user_id'           => '',
                    'urn'               => '',
                    'account_number'    => $accountNumber,
                    Fields::CREDENTIALS => [
                        Axis\Fields::ENCRYPTION_KEY => 'encryption_123',
                        Axis\Fields::ENCRYPTION_IV  => 'encryption_iv_123',
                        Axis\Fields::CLIENT_ID      => 'client_123',
                        Axis\Fields::CLIENT_SECRET  => 'client_pass',
                        Axis\Fields::CORP_CODE      => 'CORP123',
                    ]
                ];

            default:
                return [];
        }
    }

    public function fetchFtsFundAccountIdFromBas($merchantId, string $channel = 'icici', string $accountNumber = '123456')
    {
        //icici test ca fund_account_id generated by FTS
        return '12345678';
    }

    public function fetchBankingAccountId(string $balanceId)
    {
        return 'bacc_' . '30000000000888';
    }

    public function sendRequestAndProcessResponse($path, $method, $content, $headers = [])
    {
        $result = [];

        if($path === 'business' and $method === 'POST')
        {
            $result = [
                'data' => [
                    'id' => '30000000000888',
                ]
            ];
        }

        else if($path === 'admin/apply' and $method === 'POST')
        {
            $result = [
                'data' => [
                    'created_at' => '1630662462212',
                    'updated_at' => '1631102675392',
                    'business_id' => '10000000000000',
                    'banking_account_id' => '80000000000000',
                    'application_number' => '777-000011044',
                    'application_status' => 'created',
                    'bank_status' => '',
                    'workflow_version_number' => '',
                    'metadata' => [
                        'drop_off_reason' => ''
                    ],
                    'application_type' => 'ICICI_ONBOARDING_APPLICATION',
                    'sales_team' => 'X_GROWTH'
                ]
            ];
        }

        else if($path === 'poll/status/123456' and $method === 'GET')
        {
            $result = [
                'data' => [
                    'status' => 'ACTIVE',
                ]
            ];
        }

        else if($path === 'business/10000000000000/person/' and $method === 'POST')
        {
            $result = [
                'data' => [
                    'id' => '20000000000000',
                ]
            ];
        }

        else if($path === 'business/10000000000000/applications/10000000000000' and $method === 'PATCH')
        {
            $result = [
                'data' => [
                    'id' => '30000000000000',
                    'application_specific_fields' => [
                        'isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg' => 'N',
                        'isIndianFinancialInstitution'                                    => 'Y',
                        'isOwnerNotIndianCitizen'                                         => 'N',
                        'isTaxResidentOutsideIndia'                                       => 'Y',
                        'role_in_business'                                                => 'ACCOUNTANT',
                        'business_document_mapping'   => [
                            'entityProof1' => 'AADHAR',
                            'entityProof2' => 'PANCARD'
                        ],
                        'persons_document_mapping' => [
                            '20000000000000' => [
                                'addressProof' => 'AADHAAR',
                                'idProof' => 'PANCARD'
                            ]
                        ]
                    ],
                    'signatories' => [
                        0 => [
                            'person_id'      => '20000000000000',
                            'signatory_type' => 'AUTHORIZED_SIGNATORY',
                        ],
                    ],
                ],
            ];
        }

        else if($path === 'is_serviceable' and $method === 'GET')
        {
            $result = [
                'data' => [
                    'serviceable' => true,
                ]
            ];
        }

        else if($path === 'is_serviceable_bulk?business_category=ECOMMERCE&business_type=PRIVATE_LIMITED&pin_code=833216' and $method === 'GET')
        {
            $result = [
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
            ];
        }

        else if($path === 'business/10000000000000/application/10000000000000/signatory' and $method === 'POST')
        {
            $result = [
                'data' => [
                    'id' => '40000000000000',
                ]
            ];
        }

        else if($path === 'business/10000000000000/application/10000000000000/signatory/40000000000000' and $method === 'PATCH')
        {
            $result = [
                'data' => [
                    'id' => '40000000000000',
                ]
            ];
        }

        else if($path === 'business/10000000000000/person/20000000000000' and $method === 'DELETE')
        {
            $result = [
                'deleted' => true
            ];
        }

        else if($path === 'business/10000000000000/application/10000000000000/signatory/40000000000000' and $method === 'DELETE')
        {
            $result = [
                'deleted' => true
            ];
        }

        else if($path === 'business/10000000000000/applications/10000000000000' and $method === 'GET')
        {
            $result = [
                'data' => [
                    'id' => '10000000000000',
                    'application_specific_fields' => [
                        'isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg' => 'N',
                        'isIndianFinancialInstitution'                                    => 'Y',
                        'isOwnerNotIndianCitizen'                                         => 'N',
                        'isTaxResidentOutsideIndia'                                       => 'Y',
                        'role_in_business'                                                => 'ACCOUNTANT',
                        'business_document_mapping'   => [
                            'entityProof1' => 'AADHAR',
                            'entityProof2' => 'PANCARD'
                        ],
                        'persons_document_mapping' => [
                            '20000000000000' => [
                                'addressProof' => 'AADHAAR',
                                'idProof' => 'PANCARD'
                            ],
                            '50000000000000' => [
                                'addressProof' => 'AADHAAR',
                                'idProof' => 'PANCARD'
                            ]
                        ]
                    ],
                    'signatories' => [
                        0 => [
                            'person_id'      => '20000000000000',
                            'signatory_type' => 'AUTHORIZED_SIGNATORY',
                        ],
                    ],
                ],
            ];
        }

        else if($path === 'business/10000000000000/person/20000000000000' and $method === 'PATCH')
        {
            $result = [
                'data' => [
                    'id' => '20000000000000',
                ]
            ];
        }

        else if($path == 'search/wrongUrl' and $method == 'GET')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        else if($path == 'booking/slot/book' ||  $path == 'booking/slot/reschedule' and $method == 'POST')
        {
            $result = [
                'data' => [
                    'status' => 'Success',
                    'bookingDetails' => [
                        'bookingId'        => '#TE-00038',
                        'bookingStartTime' => '03-Aug-2022 11:30:00',
                        'bookingEndTime'   => '03-Aug-2022 11:45:00',
                        'assignedStaffName'=> 'Sanjana Aithal',
                        'merchantEmail'    => 'TEST@RAZORPAY.COM',
                    ],
                    'ErrorDetail' => [
                        'errorReason'   => ''
                    ]
                ]
            ];
//            Adding negative case for future reference
//            'data' => [
//                'status' => 'Failure',
//                'bookingDetails' => [
//                    'bookingId'        => '',
//                    'bookingStartTime' => '',
//                    'bookingEndTime'   => '',
//                    'assignedStaffName'=> '',
//                    'merchantEmail'    => '',
//                ],
//                'ErrorDetail' => [
//                    'errorReason'   => 'Invalid booking time'
//                ]
//            ]
        }

        else if($path == 'booking/slot/availableSlots' and $method == 'GET')
        {
            $result = [
                'data' => [
                    "data" => [
                        "10:00",
                        "10:15",
                        "10:30",
                        "10:45",
                        "11:00",
                        "11:15",
                        "11:30",
                        "11:45",
                        "12:00",
                    ]
                ]
            ];
        }

        else if($path == 'booking/slot/recentSlots' and $method == 'GET')
        {
            $result = [
                'data' => [
                    "data" => [
                        "04-Jan-2022" => [
                            "10:00",
                            "10:15",
                            "10:30",
                            "14:00",
                            "10:00"
                        ]
                    ]
                ]
            ];
        }

        else if(str_starts_with($path, 'check_serviceability') and $method === 'GET')
        {
            $queryParams = [];
            $queryString = parse_url($path, PHP_URL_QUERY);
            parse_str($queryString, $queryParams);

            $pincode = $queryParams['pincode'];

            if ($pincode == '174103')
            {
                $result = [
                    'data' => [
                        'serviceability' => [
                            [
                                'is_serviceable'        => false,
                                'partner_bank'          => 'RBL',
                                'unserviceable_reasons' => [
                                    "PIN_CODE_UNSERVICEABLE"
                                ],
                            ],
                            [
                                'is_serviceable'        => false,
                                'partner_bank'          => 'ICICI',
                                'unserviceable_reasons' => [
                                    "PIN_CODE_UNSERVICEABLE"
                                ],
                            ]
                        ],
                        'pincode_details' => [
                            'city'      => '',
                            'state'     => '',
                            'region'    => '',
                            'error'     => 'No Pincode Match Found!'
                        ]
                    ]
                ];
            }
            else
            {
                $result = [
                    'data' => [
                        'serviceability' => [
                            [
                                'is_serviceable'        => true,
                                'partner_bank'          => 'RBL',
                                'unserviceable_reasons' => null,
                            ],
                            [
                                'is_serviceable'        => false,
                                'partner_bank'          => 'ICICI',
                                'unserviceable_reasons' => [
                                    "PIN_CODE_UNSERVICEABLE"
                                ],
                            ]
                        ],
                        'pincode_details' => [
                            'city'      => 'belgaum',
                            'state'     => 'karnatka',
                            'region'    => 'south',
                            'error'     => ''
                        ]
                    ]
                ];
            }
        }

        return $result;
    }

    public function fetchActivatedDirectAccountsFromBas(MerchantEntity $merchant)
    {
        $iciciBalance = $merchant->directBankingBalances()
                                 ->where('channel', '=', BASChannel::getDirectTypeChannels())
                                 ->first();

        $ba = null;

        if(empty($iciciBalance) === false)
        {
            $ba = new Entity();

            $input = [
                'channel'        => 'icici',
                'account_type'   => 'direct',
                'account_number' => $iciciBalance->getAccountNumber(),
            ];

            $ba->build($input);

            $ba->setId('30000000000888');

            $ba->setBasCaStatus('activated');

            $ba->merchant()->associate($merchant);

            $ba->balance()->associate($iciciBalance);
        }

        return $ba;
    }

    public function getBankingAccountApplicationResponse($count = 10)
    {
        $compositeApplication = [
            'business' => [
              'id' => 'LVFoXUXt8aLGQt',
              'created_at' => 1679637455661,
              'updated_at' => 1682055361823,
              'AssociatedPeople' => null,
              'AssociatedDocuments' => null,
              'merchant_id' => '10000000000000',
              'name' => 'Z-AXIS GROUP OF INDUSTRIES',
              'email_id' => 'accountlinking00@test.com',
              'mobile_number' => '7652344566',
              'date_of_incorporation' => '',
              'country_of_incorporation' => '',
              'pan_number' => 'BESPA1234K',
              'form_60_number' => '',
              'gstin' => '22AAAAA0000A1Z5',
              'constitution' => 'ONE_PERSON_COMPANY',
              'nature_of_business' => '',
              'industry_type' => 'WELL',
              'category' => '',
              'sector' => '',
              'sub_sector' => '',
              'source_of_funding' => '',
              'bsr' => '',
              'average_annual_turnover' => '',
              'preferred_mailing_address' => '',
              'preferred_mailing_address_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => '',
                'address_state' => '',
                'address_country' => '',
                'address_pin_code' => '',
                'address_contact_number' => '',
                'address_email_id' => '',
                'address_region' => ''
              ],
              'registered_address' => 'eastdelhi, delhi, 110092',
              'registered_address_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => 'eastdelhi',
                'address_state' => 'delhi',
                'address_country' => '',
                'address_pin_code' => '110093',
                'address_contact_number' => '',
                'address_email_id' => '',
                'address_region' => 'north'
              ],
              'nominee' => [
                'name' => '',
                'address' => '',
                'relationship' => '',
                'age' => 0
              ],
              'minor_nominee' => [
                'name' => '',
                'address' => '',
                'relationship' => '',
                'age' => 0
              ],
              'region' => null,
              'associated_people' => null,
              'associated_documents' => null
            ],
            'person' => [
              'id' => 'LVFoXUXt8aLGQt',
              'created_at' => 0,
              'updated_at' => 1682055361833,
              'associated_business_id' => 'LVFoXUXt8aLGQt',
              'AssociatedDocuments' => null,
              'title' => '',
              'first_name' => 'aditya',
              'middle_name' => '',
              'last_name' => '',
              'email_id' => 'yaxisgroupofindustries@gmail.com',
              'phone_number' => '+919560569604',
              'religion' => '',
              'religion_category' => '',
              'father_name' => '',
              'spouse_name' => '',
              'mother_name' => '',
              'maiden_name' => '',
              'date_of_birth' => '',
              'marital_status' => '',
              'gender' => '',
              'nationality' => '',
              'resident_of_india' => false,
              'fatca_crs' => [
                'tax_identification_number' => '',
                'country_of_jurisdiction_of_residence' => '',
                'city_of_birth' => '',
                'country_of_birth' => ''
              ],
              'permanent_residential_address' => '',
              'permanent_residential_address_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => '',
                'address_state' => '',
                'address_country' => '',
                'address_pin_code' => '',
                'address_contact_number' => '',
                'address_email_id' => ''
              ],
              'current_residential_address' => '',
              'current_residential_address_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => '',
                'address_state' => '',
                'address_country' => '',
                'address_pin_code' => '',
                'address_contact_number' => '',
                'address_email_id' => ''
              ],
              'preferred_mailing_address' => '',
              'preferred_mailing_address_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => '',
                'address_state' => '',
                'address_country' => '',
                'address_pin_code' => '',
                'address_contact_number' => '',
                'address_email_id' => ''
              ],
              'address_outside_india' => '',
              'address_outside_india_details' => [
                'address_house_number' => '',
                'address_building_name' => '',
                'address_street_name' => '',
                'address_locality' => '',
                'address_landmark' => '',
                'address_city' => '',
                'address_state' => '',
                'address_country' => '',
                'address_pin_code' => '',
                'address_contact_number' => '',
                'address_email_id' => ''
              ],
              'education_qualification' => '',
              'employment_type' => '',
              'employed_with' => '',
              'occupation' => '',
              'source_of_income' => '',
              'gross_annual_income' => '',
              'type_of_company' => '',
              'industry_type' => '',
              'role_in_business' => 'Proprietor',
              'contact_verified' => false
            ],
            'banking_account' => [
              'id' => 'JuLWj2OnFAcg72',
              'created_at' => 1655375595507,
              'updated_at' => 1682055361850,
              'beneficiary_name' => '',
              'beneficiary_email' => '',
              'beneficiary_mobile' => '',
              'associated_account_managers' => null,
              'business_id' => 'LVFoXUXt8aLGQt',
              'account_number' => '401509080395',
              'account_type' => '',
              'partner_bank' => '',
              'balance_id' => '',
              'fts_fund_account_id' => '',
              'account_currency' => '',
              'ifsc' => 'RATN0000281',
              'urn' => '',
              'alias_id' => '',
              'status' => 'IN_PROGRESS',
              'preference' => null,
              'beneficiary_details' => [
                'email' => 'SENDMAIL4ADIL@GMAIL.COM',
                'mobile' => '+91(0)9560569604',
                'city' => 'NEWDE',
                'state' => 'DLI',
                'country' => 'IN',
                'address1' => 'G F PLOT NO 12 KH NO 24 19 B 557',
                'address2' => 'MAIN 33 FUTA ROAD RAJIV NAGAR',
                'address3' => 'MANDOLI EXTN NEAR BUDH BAZAR DELHI'
              ],
              'metadata' => [
                'bank_account_open_date' => 1678873350,
                'stp_fcrm_date' => '',
                'stp_sr_number' => '',
                'stp_sr_status' => '',
                'stp_closed_date' => '',
                'stp_remarks' => '123',
                'stp_connected_banking' => '',
                'stp_t3_date' => '',
                'stp_helpdesk_sr' => '',
                'stp_helpdesk_sr_status' => '',
                'stp_rzp_intervention' => ''
              ]
            ],
            'banking_account_application' => [
              'id' => 'JuLWj2OnFAcg72',
              'created_at' => 1658041903068,
              'updated_at' => 1682055361849,
              'business_id' => 'LVFoXUXt8aLGQt',
              'merchant_id' => '10000000000000',
              'application_id' => 'JuLWj2OnFAcg72',
              'banking_account_id' => 'Ji8Osy16V60siZ',
              'application_number' => '203128886',
              'application_status' => 'picked',
              'sub_status' => 'none',
              'bank_status' => '',
              'bank_sub_status' => '',
              'workflow_version_number' => '',
              'combined_application_status' => '',
              'application_tracking_id' => '40123',
              'application_type' => 'RBL_ONBOARDING_APPLICATION',
              'person_details' => [
                'first_name' => '',
                'email_id' => '',
                'phone_number' => '',
                'role_in_business' => ''
              ],
              'pincode' => '',
              'application_specific_fields' => [
                'EntityProofDocuments' => [
                  [
                    'document_type' => 'gst_certificate',
                    'file_id' => 'file_LOckRD3Auj6ksw'
                  ]
                ]
              ],
              'sales_team' => 'sme',
              'metadata' => [
                'additional_details' => [
                  'sales_pitch_completed' => 1,
                  'calendly_slot_booking_completed' => 1,
                  'entity_mismatch_status' => 'entity_name_mismatch',
                  'green_channel' => true,
                  'is_documents_walkthrough_complete' => 0,
                  'booking_id' => '12341',
                  'booking_date_and_time' => 1678873350,
                  'cin' => 'CIN',
                  'gstin' => '07AYMPA5163E2ZL',
                  'llpin' => '',
                  'skip_dwt' => 1,
                  'dwt_completed_timestamp' => '1678873350',
                  'dwt_scheduled_timestamp' => '1678873350',
                  'mid_office_poc_name' => null,
                  'docket_delivered_date' => null,
                  'docket_estimated_delivery_date' => null,
                  'docket_requested_date' => '',
                  'courier_tracking_id' => '',
                  'courier_service_name' => '',
                  'gstin_prefilled_address' => 1,
                  'api_onboarded_date' => null,
                  'api_onboarding_login_date' => null,
                  'application_initiated_from' => 'X_DASHBOARD',
                  'account_opening_webhook_date' => 1678873350,
                  'agree_to_allocated_bank_and_amb' => 1,
                  'feet_on_street' => true,
                  'skip_mid_office_call' => true,
                  'appointment_source' => 'sales',
                  'sent_docket_automatically' => false,
                  'reasons_to_not_send_docket' => [
                    'PoE Not Verified',
                    'Entity Name Mismatch',
                    'Entity Type Mismatch',
                    'Unexpected State Change Log',
                    'Application with Duplicate Merchant Name'
                  ],
                  'docket_not_delivered_reason' => 'Wrong Setup Form',
                  'dwt_response' => [
                    'cc_od' => 0,
                    'stamp_available' => 1,
                    'agree_to_20k_ICV' => 1,
                    'proof_of_entity_available' => 1,
                    'document_for_poa_available' => 'COI',
                    'proof_of_address_available' => 1
                  ],
                  'business_details' => [
                    'model' => 'Fabric, Needlework, Piece Goods, and Sewing Stores',
                    'category' => 'ECOMMERCE',
                    'sub_category' => 'fabric_and_sewing_stores'
                  ],
                  'proof_of_entity' => [
                    'source' => 'gstin',
                    'status' => 'verified'
                  ],
                  'proof_of_address' => [
                    'source' => 'gstin',
                    'status' => 'verified'
                  ],
                  'verified_addresses' => [
                    [
                      'source' => 'gstin',
                      'address' => 'B-557, G/F PLOT NO-12 KH NO-24/19, MAIN 33FUTA ROAD RAJIV NAGAR MANDOLI EXTN NEAR BUDH BAZAR, DELHI, North East Delhi, Delhi, 110093',
                      'addressDetails' => [
                        'address_house_number' => null,
                        'address_building_name' => null,
                        'address_street_name' => null,
                        'address_locality' => null,
                        'address_landmark' => null,
                        'address_city' => null,
                        'address_state' => null,
                        'address_country' => null,
                        'address_pin_code' => '110093',
                        'address_contact_number' => null,
                        'address_email_id' => null
                      ]
                    ]
                  ],
                  'verified_constitutions' => [
                    [
                      'source' => 'gstin',
                      'constitution' => 'PUBLIC_LIMITED'
                    ]
                  ],
                  'rbl_new_onboarding_flow_declarations' => [
                    'available_at_preferred_address_to_collect_docs' => 1,
                    'seal_available' => 1,
                    'signatories_available_at_preferred_address' => 1,
                    'signboard_available' => 1
                  ]
                ],
                'drop_off_reason' => '',
                'ca_channel' => '',
                'campaign_id' => '',
                'lead_sent_to_bank_date' => '1682947444',
                'date_on_which_1st_appointment_was_fixed' => '',
                'docs_collected_date' => '',
                'case_initiation_date' => '',
                'account_opened_date' => '',
                'multi_location' => '',
                'contacted_by_sales' => false,
                'follow_up_date' => '',
                'is_stp_doc_collected' => '',
                'is_account_number_change' => '',
                'ops_follow_up_date' => '',
                'fresh_desk_ticket_created' => false,
                'comment' => 'Some comment',
                'initial_cheque_value' => 20000,
                'verification_date' => '',
                'is_allowed_on_partner_lms' => true,
                'account_open_date' => 0,
                'account_login_date' => 0,
                'declaration_step' => 1
              ],
              'registration_status' => '',
              'average_monthly_balance' => 20000,
              'expected_monthly_gmv' => 500000,
              'bank_account_type' => 'business_plus',
              'assignee_team' => 'ops'
            ],
            'partner_bank_application' => [
              'id' => 'JuLWj2OnFAcg73',
              'banking_account_application_id' => 'JuLWj2OnFAcg72',
              'branch_code' => '213',
              'lead_details' => [
                'lead_ir_number' => 'IR 01234',
                'assigned_rm_date' => 0,
                'case_login_different_locations' => false,
                'office_different_locations' => true,
                'bank_poc_assigned_date' => 1678352209,
                'bank_due_date' => 1678905000,
                'revived_lead' => false
              ],
              'rm_details' => [
                'rm_name' => 'Pankaj Mishra',
                'rm_phone_number' => '9315383526',
                'rm_employee_code' => '12345',
                'rm_assignment_type' => 'pcarm',
                'pcarm_manager_name' => 'Avijit Shrivastava'
              ],
              'doc_collection_details' => [
                'ip_cheque_value' => 20000,
                'doc_collection_date' => 1678300200,
                'api_docs_delay_reason' => 'That\'s how we roll.',
                'customer_appointment_date' => 1678386700,
                'customer_appointment_booking_date' => 1682055139,
                'api_docs_received_with_ca_docs' => true
              ],
              'account_opening_details' => [
                'sr_number' => 'SR_NUMBER',
                'account_open_date' => 0,
                'account_opening_ftnr' => true,
                'account_opening_ftnr_reasons' => 'AO Negative List/Compliance/Legal/CIBIL',
                'account_opening_ir_number' => 'IR00022515189',
                'account_opening_ir_close_date' => 1678352209,
                'account_opening_tat_exception' => false,
                'account_opening_tat_exception_reason' => 'compliance Issue'
              ],
              'api_onboarding_details' => [
                'api_ir_number' => 'API_IR_1234',
                'ldap_id_mail_date' => 1678352209,
                'api_ir_closed_date' => 1678352209,
                'api_onboarding_ftnr' => true,
                'api_onboarding_ftnr_reasons' => 'Reason 1, Reason 2',
                'api_onboarding_tat_exception' => false,
                'api_onboarding_tat_exception_reason' => 'This time for Africa'
              ],
              'account_activation_details' => [
                'drop_off_date' => 0,
                'rzp_ca_activated_date' => 1678352209,
                'upi_credential_received_date' => 1678352209,
                'upi_credential_not_done_remarks' => 'Something'
              ],
              'auxiliary_details' => [
                'promo_code' => 'RZPAY',
                'revised_declaration' => false,
                'lead_referred_by_rbl_staff' => true,
                'aof_shared_with_mo' => false,
                'aof_not_shared_reason' => 'Already Login',
                'aof_shared_discrepancy' => '',
                'wa_message_sent_date' => 1678352209,
                'wa_message_response_date' => 1678352209,
                'first_calling_time' => '5 to 6',
                'ca_beyond_tat' => false,
                'ca_beyond_tat_dependency' => '',
                'ca_service_first_query' => '1.on rrt high risk rating by compliance is not mentioned. APPLICANT FOUND IN NEGATIVE LIST ODG452595087230310202422178-ADIL',
                'second_calling_time' => null,
                'lead_ir_status' => null,
                'api_service_first_query' => null,
                'api_beyond_tat' => null,
                'api_beyond_tat_dependency' => null
              ],
              'created_at' => 1680600430098,
              'updated_at' => 1682055361853
            ],
            'account_managers' => [
              'ops_poc' => [
                'id' => 'LXdkSkoN4qea1i',
                'created_at' => 1680158423081,
                'updated_at' => 1682055361854,
                'rzp_admin_id' => 'JuLWj2NfGSjRu7',
                'team' => 'RAZORPAY_OPS',
                'name' => 'Ops person',
                'phone_number' => '8989898989',
                'email' => 'ops.person@razorpay.com',
                'booking_count' => 0
              ],
              'sales_poc' => [
                'id' => 'LXdkSkoN4qea1j',
                'created_at' => 1680158423081,
                'updated_at' => 1682055361854,
                'rzp_admin_id' => 'JuLWj2NfGSjRu8',
                'team' => 'RAZORPAY_SALES',
                'name' => 'Sales person',
                'phone_number' => '8989898988',
                'email' => 'sales.person@razorpay.com',
                'booking_count' => 0
              ],
              'bank_poc' => [
                'id' => 'LXdkSkoN4qea1k',
                'created_at' => 1680158423081,
                'updated_at' => 1682055361854,
                'rzp_admin_id' => 'JuLWj2NfGSjRu9',
                'team' => 'RBL_POC',
                'name' => 'Bank POC person',
                'phone_number' => '8989898980',
                'email' => 'rbl.poc@rblbank.com',
                'booking_count' => 0
              ],
              'ops_mx_poc' => [
                'id' => 'LXdkSkoN4qea1l',
                'created_at' => 1680158423081,
                'updated_at' => 1682055361854,
                'rzp_admin_id' => 'JuLWj2NfGSjRv8',
                'team' => 'OPS_MX_POC',
                'name' => 'Ops MX POC',
                'phone_number' => '8989898978',
                'email' => 'ops_mx.poc@razorpay.com',
                'booking_count' => 0
              ]
            ],
            'credentials' => [
              'bank_reference_number' => '',
              'auth_username' => '',
              'auth_password' => '',
              'corp_id' => '',
              'client_id' => '',
              'client_secret' => '',
              'email' => '',
              'dev_portal_password' => ''
            ],
            'tat_details' => [
              'verification_completion_date' => 1682055139,
              'doc_collection_completion_date' => 1678300200,
              'account_opening_completion_date' => 1682681775,
              'api_onboarding_completion_date' => 1678352209,
              'account_activation_completion_date' => 1678352209,
              'upi_activation_completion_date' => 1678352209,
              'verification_tat' => 741,
              'doc_collection_tat' => 0,
              'account_opening_tat' => 881,
              'api_onboarding_tat' => 333009,
              'account_activation_tat' => 0,
              'upi_activation_tat' => 0,
              'customer_onboarding_tat' => 14
            ],
            'latest_comment' => 'Bank\'s Inernal Comment',
        ];

        $list = [];

        for ($i = 0; $i < $count; $i++)
        {
            $list[] = $compositeApplication;
        }

        return [
            'data' => $list
        ];
    }

    public function fetchSearchLeads(array $input)
    {
        $count = empty($input['count']) === false ? $input['count'] : 1;
        $skip = empty($input['skip']) === false ? $input['skip'] : 0;

        if ($skip >= 100) {
            return [];
        }

        $response = $this->getBankingAccountApplicationResponse($count);

        return $response['data'];
    }

    public function fetchRblApplications(array $input)
    {
        $compositeApplications = $this->fetchSearchLeads($input);

        return array_map(function($compositeApplication) {
            return $compositeApplication['banking_account_application'];
        }, $compositeApplications);
    }

    public function fetchRblApplicationsForPartnerLms(array $input)
    {
        return $this->fetchSearchLeads($input);
    }

    public function getApplicationForRblPartnerLms(string $businessId, string $applicationId)
    {
        $response = $this->getBankingAccountApplicationResponse(1);

        return $response['data'][0];
    }

    public function activateRblAccount(string $businessId, string $applicationId)
    {
        $response = $this->getBankingAccountApplicationResponse(1);

        $bankingAccount = $response['data'][0];

        $bankingAccount['banking_account_application']['application_status'] = 'activated';
        $bankingAccount['banking_account_application']['sub_status'] = 'upi_creds_pending';

        return $bankingAccount;
    }

    public function createBusinessOnBas(array $input) : array
    {
        return array_merge(
            [
                'id' => 'Le5mr3Cd8iwuvy',
            ], $input);
    }

    public function createRblOnboardingApplicationOnBas(string $businessId, array $input) : array
    {
        return [
            'id'                    => 'Le8uzhRdJoqH3o',
            'business_id'           => $businessId,
            'application_status'    => 'created',
            'application_type'      => 'RBL_ONBOARDING_APPLICATION',
            'sales_team'            => 'SELF_SERVE',
            'person_details'        => [
                'first_name'            => 'Merchant Name',
                'email_id'              => 'test-abc@email.com',
                'phone_number'          => '9876543210',
                'role_in_business'      => 'Founder'
            ],
            'metadata'              => [
                'additional_details'    => [
                    'application_initiated_from'    => 'X_DASHBOARD',
                    'gstin_prefilled_address'       => 1,
                ],
            ]
        ];
    }

    public function getRblCompositeApplication(string $id): array
    {
        $response = $this->getBankingAccountApplicationResponse(1);

        return $response['data'][0];
    }

    public function getBusinessId($merchantId)
    {
        return '';
    }

    public function patchRBLApplicationComposite(string $applicationIdOrReferenceNumber, array $input)
    {
        $dummyDetails = [
            'business' => [
                'id' => 'LVFoXUXt8aLGQt',
            ],
            'person' => [
                'email_id' => 'yaxisgroupofindustries@gmail.com',
            ],
            'banking_account' => [

                'beneficiary_details' => [
                    'email' => 'SENDMAIL4ADIL@GMAIL.COM',
                    'mobile' => '+91(0)9560569604',
                    'city' => 'NEWDE',
                    'state' => 'DLI',
                    'country' => 'IN',
                    'address1' => 'G F PLOT NO 12 KH NO 24 19 B 557',
                    'address2' => 'MAIN 33 FUTA ROAD RAJIV NAGAR',
                    'address3' => 'MANDOLI EXTN NEAR BUDH BAZAR DELHI'
                ],
            ],
            'banking_account_application' => [
                'id' => 'JuLWj2OnFAcg72',
                'created_at' => 1658041903068,
                'updated_at' => 1681107398949,
                'business_id' => 'LVFoXUXt8aLGQt',
                'metadata' => [
                    'additional_details' => [
                        'sales_pitch_completed' => 1,
                        'calendly_slot_booking_completed' => 1,
                        'entity_mismatch_status' => 'entity_name_mismatch',
                        'green_channel' => true,
                        'is_documents_walkthrough_complete' => 0,
                        'booking_id' => '12341',
                    ],
                ],
            ],
            'partner_bank_application' => [
                'id' => 'JuLWj2OnFAcg73',
                'banking_account_application_id' => '',
                'branch_code' => '213',
            ],
            'account_managers' => [
                'sales_poc' => [
                    'rzp_admin_id' => '12345',
                    'user_id' => '',
                ],
                'ops_poc' => [
                    'rzp_admin_id' => '12346',
                    'user_id' => '',
                ],
                'bank_poc' => [
                    'rzp_admin_id' => '12347',
                    'user_id' => '',
                ],
                'ops_mx_poc' => [
                    'rzp_admin_id' => '12348',
                    'user_id' => '',
                ],
            ],
            'credentials' => [
                'banking_account_id' => 'Ji8Osy16V60siZ',
                'merchant_id' => 'Ji8Osy16V60siX',
                'merchant_name' => 'TEST MERCHANT',
                'email' => 'email',
                'dev_portal_password' => 'dev_portal_password',
                'auth_username' => 'ldap_id',
                'auth_password' => 'ldap_password',
                'upi_handle1' => '',
                'upi_handle2' => '',
                'upi_handle3' => '',
                'mcc_code' => ''
            ],
            'last_comment_shared_by_bank' => ''
        ];

        return $dummyDetails;
    }

    public function assignBankPocForRblPartnerLms(string $businessId, string $applicationId, array $input)
    {
        return $this->getApplicationForRblPartnerLms($businessId, $applicationId);
    }

    public function processRblAccountOpeningWebhook(array $input)
    {
        if ($input['RZPAlertNotiReq']['Body']['RZP_Ref No'] == '00000')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [], "Application does not exist");
        }

        $response = [
            RblGateway\Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                RblGateway\Fields::HEADER => [
                    RblGateway\Fields::TRAN_ID => '12345',
                ],
                RblGateway\Fields::BODY   =>[
                    RblGateway\Fields::STATUS => 'Success'
                ]
            ],
        ];
        return $response;
    }

    public function bulkAssignAccountManagerForRbl(array $basInput): array
    {
        $ids = $basInput['banking_account_ids'];

        $failedItems = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($ids as $id) {
            if (str_starts_with($id, 'wrongCurAccId'))
            {
                array_push($failedItems, [
                    'id'        => 'bacc_' . $id,
                    'error'     => 'The id provided does not exist'
                ]);
                $failureCount++;
            }
            else
            {
                $successCount++;
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failureCount,
            'failed_items' => $failedItems,
        ];
    }
}
