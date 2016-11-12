<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateInvoiceWithNewCustomer' => [
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
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '9999999999',
                    'customer_name' => 'test',
                    'customer_address' => null,
                ],
                'line_items_details' => [
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ]
                ],
                'status' => 'issued',
                'sms_status' => 'sent',
                'email_status' => 'sent',
                'view_less' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithExistingCustomer' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'  => [
                    'id' => 'cust_100000customer',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_name' => 'test',
                    'customer_address' => null,
                ],
                'line_items_details' => [
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ]
                ],
                'status' => 'issued',
                'sms_status' => 'sent',
                'email_status' => 'sent',
                'view_less' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithMultipleLineItems' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'  => [
                    'id' => 'cust_100000customer',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ],
                    [
                        'name'          => 'Another item',
                        'description'   => 'Another description',
                        'amount'        => 200000,
                        'quantity'      => 2,
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_name' => 'test',
                    'customer_address' => null,
                ],
                'line_items_details' => [
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ],
                    [
                        'name'          => 'Another item',
                        'description'   => 'Another description',
                        'amount'        => 200000,
                        'quantity'      => 2,
                    ]
                ],
                'status' => 'issued',
                'sms_status' => 'sent',
                'email_status' => 'sent',
                'view_less' => false,
            ],
        ],
    ],

    'testCreateInvoiceWithSmsNotifyFalseAndEmailNotifyTrue' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'  => [
                    'id' => 'cust_100000customer',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'sms_notify' => 0,
                'email_notify' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_name' => 'test',
                    'customer_address' => null,
                ],
                'line_items_details' => [
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ]
                ],
                'status' => 'issued',
                'sms_status' => null,
                'email_status' => 'sent',
                'view_less' => false,
            ],
        ],
    ],
];


// 'id' => string (18) "inv_6g2e9y5me09eSB"
//     'entity' => string (7) "invoice"
//     'customer_id' => string (19) "cust_6g2e9xRai0LaSz"
//     'order_id' => string (14) "6g2e9uq11E2pE7"
//     'customer_details' => array (4) [
// 'customer_name' => string (4) "test"
//         'customer_email' => string (17) "test@razorpay.com"
//         'customer_contact' => string (10) "9999999999"
//         'customer_address' => NULL
//     ]
//     'line_items_details' => array (1) [
// array (7) [
// 'id' => string (17) "li_6g2e9tTkm0e3Ee"
//             'invoice_id' => string (14) "6g2e9y5me09eSB"
//             'name' => string (14) "Some item name"
//             'description' => string (21) "Some item description"
//             'amount' => integer 100000
//             'quantity' => integer 1
//             'created_at' => integer 1478936528
//         ]
//     ]
//     'status' => string (6) "issued"
//     'due_by' => integer 1484120528
//     'scheduled_at' => integer 1478936528
//     'sms_status' => string (4) "sent"
//     'email_status' => string (4) "sent"
//     'notes' => array (0)
//     'short_url' => string (21) "http://bit.ly/ohbtpl5"
//     'view_less' => bool FALSE
//     'created_at' => integer 1478936528
