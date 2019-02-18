<?php


return [
    'testCountForSubscriptionIdWithNoInvoice' => [
        'request' => [
            'url'     => '/invoices-count',
            'method'  => 'GET',
            'content' => [
                'subscription_id'  => '1000000subscri'
            ]
        ],
        'response' => [
            'content' => [
                'count' => 0
            ]
        ]
    ],

    'testCountForSubscriptionIdWithInvoice' => [
        'request' => [
            'url'     => '/invoices-count',
            'method'  => 'GET',
            'content' => [
                'subscription_id'  => '1000000subscri'
            ]
        ],
        'response' => [
            'content' => [
                'count' => 1
            ]
        ]
    ]

];
