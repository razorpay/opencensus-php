<?php

use RZP\Error\ErrorCode;

return [
    'testCreateMerchantEmails' => [
        'request'  => [
            'content' => [
                'type'   => 'refund',
                'email'  => 'cvhg@gmail.com,abc@gmail.com',
                'phone'  => '9732097320',
                'policy' => 'tech',
                'url'    => 'https://razorpay.com'
            ],
            'url'     => '/merchants/{$id}/additionalemail',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'type'   => 'refund',
                'email'  => 'cvhg@gmail.com,abc@gmail.com',
                'phone'  => '9732097320',
                'policy' => 'tech',
                'url'    => 'https://razorpay.com'
            ],
        ],
    ],

    'testFetchMerchantEmails' => [
        'request'  => [
            'url'    => '/merchants/{$id}/additionalemail',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "items" => [
                    [
                        'type'   => 'chargeback',
                        'email'  => 'cvhg@gmail.com,abc@gmail.com',
                        'phone'  => '9732097320',
                        'policy' => 'tech',
                        'url'    => 'https://razorpay.com'
                    ],
                    [
                        'type'   => 'refund',
                        'email'  => 'cvhg@gmail.com,abc@gmail.com',
                        'phone'  => '9732097320',
                        'policy' => 'tech',
                        'url'    => 'https://razorpay.com'
                    ]
                ]
            ],
        ],
    ],

    'testDeleteMerchantEmails' => [
        'request'  => [
            'url'    => '/merchants/{$id}/additionalemail/{$type}',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                true
            ],
        ],
    ],

    'testFetchMerchantEmailsByType' => [
        'request'  => [
            'url'    => '/merchants/{$id}/additionalemail/{$type}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'type'   => 'refund',
                'email'  => 'cvhg@gmail.com,abc@gmail.com',
                'phone'  => '9732097320',
                'policy' => 'tech',
                'url'    => 'https://razorpay.com'
            ],
        ],
    ],

    'testFetchEmailAndTypeNotExists' => [
        'request'   => [
            'url'    => '/merchants/{$id}/additionalemail/{$type}',
            'method' => 'GET'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_EMAIL_DOES_NOT_EXIST,
        ],
    ],

    'saveMerchantSupportPhoneDetails' => [
        'request'  => [
            'content' => [
                'phone'  => '9732097321',
            ],
            'url'     => '/proxy/merchants/supportdetails',
            'method'  => 'PUT'
        ],
        'response' => [
            'content' => [
                'type'   => 'support',
                'phone'  => '9732097321',
            ],
        ],
    ],

    'testGetMerchantSupportDetailsPresentSuccess' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/proxy/merchants/supportdetails',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'type'  => 'support',
                'email' => 'abcd@razorpay.com',
                'phone' => '9876543210',
                'url'   => 'https://www.abcd.com'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMerchantSupportDetailsNotPresentSuccess' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/proxy/merchants/supportdetails',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],
];
