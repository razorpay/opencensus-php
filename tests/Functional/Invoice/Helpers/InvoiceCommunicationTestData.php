<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testSmsAndEmailNotify' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
            ],
        ],
    ],

    'testSmsNotifyNull' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'sms_notify'    => '0',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => null,
                'email_status' => 'pending',
            ],
        ],
    ],

    'testNotifyWithNoCustomerEmail' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'sms_notify'    => '1',
                'email_notify'  => '1',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => null,
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
            ],
        ],
    ],

    'testNotifyWithNoEmailNoContact' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'email_notify'  => '0',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => null,
                    'contact' => null,
                    'name'    => 'test',
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => null,
            ],
        ],
    ],
];
