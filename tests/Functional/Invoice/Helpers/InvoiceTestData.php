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
                'currency' => 'INR',
                'user_id'  => 'abcdefghij1234',
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
                'line_items' => [
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
                'view_less' => true,
                'amount' => 100000,
            ],
        ],
    ],

    'testCreateInvoiceWithNewCustomerAndAddress' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'shipping_address' => [
                        'line1' => 'Line One Etc',
                        'line2' => 'Line Two Etc',
                        'city'  => 'Bangalore',
                        'state' => 'Karnataka',
                        'zipcode' => '560078',
                        'country' => 'India',
                    ],
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'currency' => 'INR',
                'type' => 'ecod',
                'user_id'  => 'abcdefghij1234',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '9999999999',
                    'customer_name' => 'test',
                ],
                'line_items' => [
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
                'view_less' => true,
                'amount' => 100000,
                'user_id'  => 'abcdefghij1234',
            ],
        ],
    ],

    'testCreateInvoiceWithExistingCustomer' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'currency' => 'INR',
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
                'line_items' => [
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
                'view_less' => true,
                'amount' => 100000
            ],
        ],
    ],

    'testCreateInvoiceWithMultipleLineItems' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
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
                ],
                'currency' => 'INR',
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
                'line_items' => [
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
                'currency' => 'INR',
                'status' => 'issued',
                'sms_status' => 'sent',
                'email_status' => 'sent',
                'view_less' => true,
                'amount' => 500000
            ],
        ],
    ],

    'testCreateInvoiceWithSmsNotifyFalseAndEmailNotifyTrue' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'sms_notify' => 0,
                'email_notify' => 1,
                'currency' => 'INR',
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
                'line_items' => [
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
                'view_less' => true,
            ],
        ],
    ],

    'testGetInvoice' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'customer_email' => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_name' => 'test',
                    'customer_address' => null,
                ],
                'line_items' => [
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ]
                ],
                'customer_id' => 'cust_100000customer',
                'short_url' => 'http://bitly.dev/2eZ11Vn',
                'notes' => [],
                'status' => 'issued',
                'sms_status' => 'sent',
                'email_status' => 'sent',
                'view_less' => true,
            ],
        ],
    ],

    'testGetInvoiceStatusAfterPayment' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/status',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMultipleInvoices' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id' => 'inv_100000invoice2',
                        'customer_id' => 'cust_100000customer',
                        'order_id' => 'order_10000000order2',
                        'line_items' => [
                            [
                                'id' => 'li_10000lineitem2',
                            ]
                        ],
                        'status' => 'issued',
                    ],
                    [
                        'id' => 'inv_1000000invoice',
                        'customer_id' => 'cust_100000customer',
                        'order_id' => 'order_100000000order',
                        'line_items' => [
                            [
                                'id' => 'li_100000lineitem',
                            ]
                        ],
                        'status' => 'issued',
                    ]
                ]
            ],
        ],
    ],

    'testGetInvoiceStatus' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/status',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'issued',
            ],
        ],
    ],

    'testGetInvoiceStatusAfterOneWeek' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/status',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice status cannot be retrieved now',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_STATUS_UNAVAILABLE,
        ],
    ],
];
