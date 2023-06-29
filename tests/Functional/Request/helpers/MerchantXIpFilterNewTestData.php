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
                'count'     => 7,
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
];
