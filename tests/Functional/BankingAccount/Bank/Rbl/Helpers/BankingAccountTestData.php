<?php

use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount;
use RZP\Error\PublicErrorCode;

return [
    'testCreateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceablePincode' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '899090',
            ],
        ],
        'response' => [
            'content' => [
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithEmptyPincode' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The pincode field is required when channel is rbl.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBankingAccountWithInvalidBank' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid channel: TEST',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSuccessBankAccountInfoNotification' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'FORACID'           => '309002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Success'
                    ]
                ]
            ],
        ],
    ],

    'testFailedBankAccountInfoNotification' => [
        'request'  => [
            'url'     => '/banking_accounts/webhooks/account_info/rbl',
            'method'  => 'POST',
            'content' => [
                'RZPAlertNotiReq' => [
                    'Body' => [
                        'FORACID'           => '309002180853',
                        'ACCT_NAME'         => 'INTERNET BANKING CA',
                        'CIF_ID'            => 'CIF_ID',
                        'ACTIVATION_DATE'   => '22-MAY-2019',
                        'REF_NUM_1'         => '15597',
                        'IFSC'              => 'HDFC0000090',
                        'ADDR_1'            => 'RAM NAGAR',
                        'ADDR_2'            => 'ADARSHA LANE',
                        'ADDR_3'            => '.',
                        'CITY'              => 'MUMBAI',
                        'STATE'             => 'MAHARASH',
                        'COUNTRY'           => 'INDIA',
                        'PINCODE'           => '123456',
                        'PHONE_NUM'         => '9899807189',
                        'EMAIL_ID'          => 'test@gmail.com'
                    ],
                    'Header' => [
                        'TranID' => '12345'
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'RZPAlertNotiRes' => [
                    'Header' => [
                        'TranID' => '12345'
                    ],
                    'Body' => [
                        'Status' => 'Failure'
                    ]
                ]
            ],
        ],
    ],

    'testUpdateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_account',
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
                BankingAccount\Entity::BANK_INTERNAL_STATUS => BankingAccount\Gateway\Rbl\Status::CLOSED
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                BankingAccount\Entity::STATUS => BankingAccount\Status::PROCESSED,
            ],
        ],
    ],
];
