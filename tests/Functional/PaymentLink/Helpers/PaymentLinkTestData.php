<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreatePaymentLink' => [
        'request'  => [
            'url'     => '/payment_links',
            'method'  => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
                'notes'         => ['Sample notes'],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
            ],
        ],
    ],

    'testFetchPaymentLink' => [
        'request'  => [
            'url'     => '/payment_links/%s',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'amount'        => 100000,
                'currency'      => 'INR',
                'title'         => 'Sample title',
                'description'   => 'Sample description',
            ],
        ],
    ],

    'testFetchPaymentLinks' => [
        'request'  => [
            'url'     => '/payment_links',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'receipt'       => '00000000000001',
                        'amount'        => 100000,
                        'currency'      => 'INR',
                        'title'         => 'Sample title',
                        'description'   => 'Sample description',
                    ],
                ],
            ],
        ],
    ],

    'testUpdatePaymentLink' => [
        'request' => [
            'url'     => '/payment_links/%s',
            'method'  => 'patch',
            'content' => [
                'receipt'       => '00000000000002',
                'title'         => 'Sample test title',
                'description'   => 'Sample test description',
                'notes'         => ['Sample test notes'],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000002',
                'title'         => 'Sample test title',
                'description'   => 'Sample test description',
            ],
        ],
    ],

    'testFetchPaymentLinkPayments' => [
        'request'  => [
            'url'     => '/payment_links/%s/payments',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'items' => [],
            ],
        ],
    ],
];
