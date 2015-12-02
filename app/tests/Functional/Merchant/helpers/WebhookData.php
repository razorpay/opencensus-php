<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateWebhook' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://example.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'url' => 'http://example.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
                'failure_count' => 0,
            ]
        ]
    ],
];
