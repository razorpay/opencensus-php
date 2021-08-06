<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateTnC' => [
        'request' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
            ],
            'url'    => '/products/tnc/',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active'
            ]
        ]
    ],
    'testUpdateTnC' => [
        'request' => [
            'content' => [
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active'
            ],
            'url'    => '/products/tnc/{id}',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active'
            ]
        ]
    ],
    'testGetTncById' => [
        'request' => [
            'url'    => '/products/tnc/{id}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active'
            ]
        ]
    ]
];
