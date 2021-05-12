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

];
