<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testPayoutCreateGetsErrorForNoWhitelistedIps' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This transaction is prohibited. Contact Support for help.'
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class'               => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_IP_NOT_WHITELISTED,
            ],
        ],

    ],

    'testPayoutCreateGetsErrorForNonWhitelistedIp' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This transaction is prohibited. Contact Support for help.'
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class'               => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_IP_NOT_WHITELISTED,
            ],
        ],
    ],

    'testPayoutCreateGetsExpectedResponseForWhitelistedIps' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_fa100000000000',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
            ],
            'status_code' => 200,
        ],
    ],

    'testExpectedResponseForMerchantNotEnabledOnFeature' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_fa100000000000',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
            ],
            'status_code' => 200,
        ],
    ],


    'testExpectedResponseForMerchantOptedOut' => [
        'request' => [
            'url'     => '/payouts',
            'method'  => 'POST',
            'content' => [
                'fund_account_id' => 'fa_fa100000000000',
                'amount'          => 100,
                'mode'            => 'IMPS',
                'currency'        => 'INR',
                'account_number'  => '2224440041626905',
                'purpose'         => 'refund'
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_fa100000000000',
                'narration'       => 'Test Merchant Fund Transfer',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
            ],
            'status_code' => 200,
        ],
    ],

    'testExpectedResponseForMerchantFeatureEnabledButServiceMappingNotFound' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/payouts/purposes',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 8,
                'items'     =>  [
                    [
                        'purpose'       =>  'refund',
                        'purpose_type'  =>  'refund',
                    ],
                    [
                        'purpose'       => 'cashback',
                        'purpose_type'  => 'refund',
                    ],
                    [
                        'purpose'       => 'payout',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'salary',
                        'purpose_type'  => 'settlement',
                    ],
                    [
                        'purpose'       => 'utility bill',
                        'purpose_type'  =>  'settlement',
                    ],
                    [
                        'purpose'       => 'vendor bill',
                        'purpose_type'  =>  'settlement',
                    ],
                    [
                        'purpose'       => 'vendor advance',
                        'purpose_type'  =>  'settlement',
                    ],
                    [
                        'purpose'       => 'petty cash',
                        'purpose_type'  =>  'settlement',
                    ]
                ],
            ],
        ],
    ],

    'testForFundAccountFetchCallByPartnerOauthForNonWhitelistedIp' => [
        'request'  => [
            'url'    => '/fund_accounts',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'items' => [],
            ],
            'status_code' => 200
        ],
    ],
    'testCreatePayoutLinksGetsErrorForNoWhitelistedIps' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email' => 1,
                'send_sms' => 1
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'This transaction is prohibited. Contact Support for help.'
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class'               => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_IP_NOT_WHITELISTED,
            ],
        ],
    ],
    'testCreatePayoutLinksGetsExpectedResponseForWhitelistedIps' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email' => 1,
                'send_sms' => 1
            ]
        ],
        'response'  => [
            'content'     => [
            ],
        ],
    ],
    'testCreatePayoutLinksGetsExpectedResponseWhenMerchantIsNotBehindExperiment' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payout-links',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'description' => 'This is a test payout',
                'purpose'     => 'refund',
                'contact'     => [
                    'name'       => 'cskdsds',
                    'email'      => 'dsknlds@gmail.com',
                    'contact'    => '1231231231'
                ],
                'notes'       => ['hi' => 'hello'],
                'receipt'     => 'Test Payout Receipt',
                'send_email' => 1,
                'send_sms' => 1
            ]
        ],
        'response'  => [
            'content'     => [
            ],
        ],
    ],
];

