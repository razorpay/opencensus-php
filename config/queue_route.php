<?php

// TODO:
// - Add comments!

return [
    'webhook' => [
        'test' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_TEST_QUEUE'),
            ],
        ],
        'live' => [
            'payment' => [
                'authorized'        => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'captured'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'failed'            => env('AWS_WEBHOOKS_FAILURE_QUEUE'),
                'dispute' => [
                    'created'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                ],
            ],
            'order' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'invoice' => [
                'paid'              => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'partially_paid'    => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'vpa' => [
                'edited'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'p2p' => [
                'created'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'rejected'          => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'transferred'       => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'subscription' => [
                'activated'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'charged'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'pending'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'halted'            => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'expired'           => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'cancelled'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
                'completed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
            'settlement' => [
                'processed'         => env('AWS_WEBHOOKS_LIVE_QUEUE'),
            ],
        ],
    ],
    'dashboard' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'es' => [
        'test'       => env('AWS_ES_SYNC_QUEUE'),
        'live'       => env('AWS_ES_SYNC_QUEUE'),
    ],
    'reports' => [
        'test'       => env('AWS_REPORTS_QUEUE'),
        'live'       => env('AWS_REPORTS_QUEUE'),
    ],
    'merchant_invoice' => [
        'test'       => env('AWS_INVOICE_REPORTS_QUEUE'),
        'live'       => env('AWS_INVOICE_REPORTS_QUEUE'),
    ],
    'invoice' => [
        'test'       => env('AWS_INVOICE_EMAILS_QUEUE'),
        'live'       => env('AWS_INVOICE_EMAILS_QUEUE'),
    ],
    'batch' => [
        'test'       => env('AWS_BATCH_QUEUE'),
        'live'       => env('AWS_BATCH_QUEUE'),
    ],
    'capture' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
    'gateway_file' => [
        'test'       => env('AWS_GENERAL_TEST_QUEUE'),
        'live'       => env('AWS_GENERAL_LIVE_QUEUE'),
    ],
];
