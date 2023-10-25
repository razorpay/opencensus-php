<?php

use RZP\Exception\BadRequestException;
return [
    'testAddBankAccountLrsEducationFlow' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'CITIUS33CHI',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city' => 'New York',
                'beneficiary_country' => 'US',
                'beneficiary_pin' => '123456',
                'notes' => [
                    'bank_name'             => 'Citi',
                    'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                    'bank_sort_code'        => '200415',
                ]
            ]
        ]
    ],

    'testEditBankAccountLrsEducationFlow' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030431',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'CITIUS33CHI',
                'account_number' => '0002020000304030431',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city' => 'New York',
                'beneficiary_country' => 'US',
                'beneficiary_pin' => '123456',
                'notes' => [
                    'bank_name'             => 'Citi',
                    'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                    'bank_sort_code'        => '200415',
                ]
            ]
        ]
    ],

    'testBankAccountLrsEducationFlowWithoutAdmin' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED',
            'class'               => BadRequestException::class,
        ]
    ],

    'testAddBankAccountLrsTravelFlow' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'CITIUS33CHI',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city' => 'New York',
                'beneficiary_country' => 'US',
                'beneficiary_pin' => '123456',
                'notes' => [
                    'bank_name'             => 'Citi',
                    'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                    'bank_sort_code'        => '200415',
                ]
            ]
        ]
    ],

    'testEditBankAccountLrsTravelFlow' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030431',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'CITIUS33CHI',
                'account_number' => '0002020000304030431',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city' => 'New York',
                'beneficiary_country' => 'US',
                'beneficiary_pin' => '123456',
                'notes' => [
                    'bank_name'             => 'Citi',
                    'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                    'bank_sort_code'        => '200415',
                ]
            ]
        ]
    ],

    'testBankAccountLrsTravelFlowWithoutAdmin' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi',
                'iban'                  => 'GB29 NWBK 6016 1331 9268 19',
                'bank_sort_code'        => '200415',
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED',
            'class'               => BadRequestException::class,
        ]
    ],

    'testAddBankAccountLRSSettlement' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi'
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'CITIUS33CHI',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay:',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city' => 'New York',
                'beneficiary_country' => 'US',
                'beneficiary_pin' => '123456',
                'notes' => [
                    'bank_name' => 'Citi',
                ]
            ]
        ]
    ],

    'testEditBankAccountLRSSettlement' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030431',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi'
            ],
            'url' => '/merchants/bank_account/update',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED',
            'class'               => BadRequestException::class,
        ]
    ],

    'testBankAccountLRSSettlementWithoutAdmin' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'CITIUS33CHI',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay:',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4' => 'address 4',
                'beneficiary_city'      => 'New York',
                'beneficiary_country'   => 'US',
                'beneficiary_pin'       => '123456',
                'type'                  => 'org_settlement',
                'bank_name'             => 'Citi'
            ],
            'url' => '/merchants/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'internal_error_code' => 'BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED',
            'class'               => BadRequestException::class,
        ]
    ],

    'testFetchPXBDocuments' => [
        'request'  => [
            'url'       => '/merchant/pxb/documents',
            'method'    => 'GET',
            'content' => [
                'payment_ids' => ['x1weraESD3o3uA', 'y2weraESD3o3uB'],
            ],
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],
];
