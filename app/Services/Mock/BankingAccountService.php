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
}
