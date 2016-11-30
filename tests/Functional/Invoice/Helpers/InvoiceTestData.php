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
                'receipt'       => '00000000000001',
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
                'user_id'  => 'abcdefghij1234',
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
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
                'currency' => 'INR',
                'payment_id' => null,
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
                'receipt'       => null,
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
                //'user_id'  => 'abcdefghij1234',
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

    'testCreateInvoiceWithMultipleLineItemsAndDifferentCurrency' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'line_items'    => [
                    [
                        'item_id'       => 'item_1000000000item',
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Currency of all items should be same as of the invoice itself',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithMultipleLineItemsAndUsingExistingItem' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'line_items'    => [
                    [
                        'item_id'       => 'item_1000000000item',
                        'quantity'      => 5,
                    ],
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ],
                ],
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details'     => [
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_name'    => 'test',
                    'customer_address' => null,
                ],
                'line_items' => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'quantity'      => 5,
                    ],
                    [
                        'name' => 'Some item name',
                        'description' => 'Some item description',
                        'amount' => 100000,
                        'quantity' => 1,
                    ],
                ],
                'currency'     => 'INR',
                'status'       => 'issued',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'view_less'    => true,
                'amount'       => 600000
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

    'testCreateDraftInvoiceWithNoData' => [
        'request' => [
            'url'       => '/invoices',
            'method'    => 'post',
            'content'   => [
                'draft' => '1'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'              => null,
                'customer_details'     => [
                    'customer_email'   => null,
                    'customer_contact' => null,
                    'customer_name'    => null,
                    'customer_address' => null,
                ],
                'line_items'           => [],
                'status'               => 'draft',
                'sms_status'           => 'pending',
                'email_status'         => 'pending',
                'view_less'            => true,
                'type'                 => null,
                'amount'               => 0,
                'currency'             => 'INR',
                'description'          => null,
                'short_url'            => null,
                'payment_id'           => null,
                'order_id'             => null,
                'payment_id'           => null,
                'issued_at'            => null,
            ],
        ],
    ],

    'testCreateDraftInvoiceWithSomeData' => [
        'request' => [
            'url'       => '/invoices',
            'method'    => 'post',
            'content'   => [
                'description'    => 'Abc def',
                'amount'         => 100,
                'line_items'     => [
                    [
                        'name'   => 'Aweseome',
                        'amount' => 1000
                    ]
                ],
                'customer'       => [
                    'name'       => 'Abc Def'
                ],
                'draft'          => '1'
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_details' => [
                'customer_name'    => 'Abc Def',
                'customer_email'   => null,
                'customer_contact' => null,
                'customer_address' => null
                ],
                'order_id'         => null,
                'line_items'       => [
                [
                    'quantity'       => 1,
                    'name'           => 'Aweseome',
                    'description'    => null,
                    'amount'         => 1000,
                    'currency'       => 'INR'
                ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'issued_at'        => null,
                'paid_at'          => null,
                'sms_status'       => 'pending',
                'email_status'     => 'pending',
                'date'             => null,
                'amount'           => 1000,
                'description'      => 'Abc def',
                'notes'            => [],
                'currency'         => 'INR',
                'short_url'        => null,
                'view_less'        => true,
                'type'             => null,
            ],
        ],
    ],

    'testCreateDraftInvoiceAndView' => [
        'request' => [
            'url'       => '/t/inv_1000000invoice',
            'method'    => 'get',
            'content'   => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice with id inv_1000000invoice is not issued yet',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices',
            'method'    => 'post',
            'content'   => [
                'line_items'     => [
                    [
                        'name'   => 'Abc Def',
                        'amount' => 1000
                    ]
                ],
                'customer'       => [
                    'name'       => 'Abc Def'
                ],
                'draft'          => '0'
            ],
        ],
        'response' => [
            'content' => [
                'entity'               => 'invoice',
                'receipt'              => null,
                'customer_details'     => [
                    'customer_name'    => 'Abc Def',
                    'customer_email'   => null,
                    'customer_contact' => null,
                    'customer_address' => null
                ],
                'line_items'           => [
                [
                    'quantity'         => 1,
                    'name'             => 'Abc Def',
                    'description'      => null,
                    'amount'           => 1000,
                    'currency'         => 'INR'
                ]
                ],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'sms_status'           => null,
                'email_status'         => null,
                'date'                 => null,
                'amount'               => 1000,
                'description'          => null,
                'notes'                => [],
                'currency'             => 'INR',
                'view_less'            => true,
                'type'                 => null,
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithBasicFields' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'sms_notify'   => '0',
                'email_notify' => '0',
                'date'         => 1480506888,
                'terms'        => 'Updated terms & conditions',
                'notes'        => [
                    'new_key'  => 'new_value'
                ],
                'amount'       => 100001,
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null,
                ],
                'order_id'             => null,
                'line_items'           => [],
                'payment_id'           => null,
                'status'               => 'draft',
                'issued_at'            => null,
                'paid_at'              => null,
                'sms_status'           => null,
                'email_status'         => null,
                'date'                 => 1480506888,
                'terms'                => 'Updated terms & conditions',
                'amount'               => 100001,
                'description'          => null,
                'notes'                => [
                    'new_key'          => 'new_value'
                ],
                'currency'             => 'INR',
                'short_url'            => null,
                'view_less'            => true,
                'type'                 => null,
            ]
        ]
    ],

    'testUpdateDraftInvoiceAmountWhenLineItemsExists' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'amount'       => 100,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount cannot be updated if line_items present',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceWithLineItems' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'line_items' => [
                    'name' => 'Abc Def',
                    'amount' => 100
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'line_items is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testUpdateDraftInvoiceWithCustomerId' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'customer_id'  => 'cust_100001customer',
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_id'          => 'cust_100001customer',
                'customer_details'     => [
                    'customer_name'    => 'test 2',
                    'customer_email'   => 'test2@razorpay.com',
                    'customer_contact' => null,
                    'customer_address' => null,
                ],
                'status'               => 'draft',
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithCustomerDetails' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'customer'  => [
                    'name'  => 'new customer',
                    'email' => 'new@razorpay.com'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_details'     => [
                    'customer_name'    => 'new customer',
                    'customer_email'   => 'new@razorpay.com',
                    'customer_contact' => null,
                    'customer_address' => null,
                ],
                'status'               => 'draft',
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithCustomerIdAndDetails' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'customer_id'  => 'cust_100000customer',
                'customer'  => [
                    'name'  => 'new customer',
                    'email' => 'new@razorpay.com'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Expecting either customer_id or customer details',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'date'         => 1480506888,
                'terms'        => 'Updated terms & conditions',
                'notes'        => [
                    'new_key'  => 'new_value'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null,
                ],
                'order_id'             => 'order_100000000order',
                'line_items'           => [],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'date'                 => 1480506888,
                'terms'                => 'Updated terms & conditions',
                'amount'               => 100000,
                'description'          => null,
                'notes'                => [
                    'new_key'          => 'new_value'
                ],
            ]
        ]
    ],

    'testUpdateIssuedInvoiceWithExtraFields' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'put',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'date'         => 1480506888,
                'terms'        => 'Updated terms & conditions',
                'notes'        => [
                    'new_key'  => 'new_value'
                ],
                'customer_id'  => 'cust_100000customer',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'customer_id is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testIssueInvoiceWithAmountAndDesc' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/issue',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null,
                ],
                'line_items'           => [],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'description'          => 'For test item',
            ]
        ]
    ],

    'testIssueInvoiceWithLineItems' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/issue',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null,
                ],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'description'          => null,
            ]
        ]
    ],

    'testIssueInvoiceWithFailingData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/issue',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice can not be issued, Provide either line_items or amount, description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_ISSUE_NOT_ALLOWED,
        ],
    ],

    'testDeleteDraftInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeleteIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for the status invoice is in',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_OPERATION_NOT_ALLOWED,
        ],
    ],

    'testAddLineItemToInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                'name'        => 'Item 1',
                'description' => 'Item 1 Description',
                'quantity'    => 10,
                'amount'      => 200,
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 10,
                        'name'             => 'Item 1',
                        'description'      => 'Item 1 Description',
                        'amount'           => 200,
                        'currency'         => 'INR'
                    ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 2000,
                'currency'         => 'INR',
            ]
        ]
    ],

    'testAddLineItemToInvoiceWithBadData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                'name'        => 'Item 1',
                'description' => 'Item 1 Description',
                'quantity'    => 10,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateLineItemOfInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'put',
            'content'   => [
                'quantity'    => 1000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 1000,
                        'name'             => 'Some item name',
                        'description'      => 'Some item description',
                        'amount'           => 100000,
                        'currency'         => 'INR'
                    ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 100000000,
                'currency'         => 'INR',
            ]
        ]
    ],

    'testUpdateLineItemOfInvoiceWithNewItemData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'put',
            'content'   => [
                'name'        => 'Item New',
                'description' => 'Item New Description',
                'quantity'    => 10,
                'amount'      => 2000,
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 10,
                        'name'             => 'Item New',
                        'description'      => 'Item New Description',
                        'amount'           => 2000,
                        'currency'         => 'INR'
                    ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 20000,
                'currency'         => 'INR',
            ]
        ]
    ],


    'testUpdateLineItemOfInvoiceWithExistingItem' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'put',
            'content'   => [
                'item_id'     => 'item_1000000001item',
                'quantity'    => 5,
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'customer_name'    => 'test',
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '1234567890',
                    'customer_address' => null
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 5,
                        'name'             => 'Some item name',
                        'description'      => 'Some item description',
                        'amount'           => 5000,
                        'currency'         => 'INR'
                    ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 25000,
                'currency'         => 'INR',
            ]
        ]
    ],

    'testUpdateLineItemOfInvoiceWithBadData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'put',
            'content'   => [
                'quantity'    => 5,
                'name'        => 'New item'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRemoveLineItemOfInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testAddLineItemToIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                'name'        => 'Item 1',
                'description' => 'Item 1 Description',
                'quantity'    => 10,
                'amount'      => 200,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for the status invoice is in',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_OPERATION_NOT_ALLOWED,
        ],
    ],

    'testUpdateLineItemOfIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'put',
            'content'   => [
                'quantity'    => 100,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for the status invoice is in',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_OPERATION_NOT_ALLOWED,
        ],
    ],

    'testRemoveLineItemOfIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for the status invoice is in',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_OPERATION_NOT_ALLOWED,
        ],
    ],

    'testCreateInvoiceWithDuplicateMerchantRefId' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id'     => 'cust_100000customer',
                'receipt'         => '00000000000001',
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Duplicate value for receipt in invoice',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DUPLICATE_INVOICE_RECEIPT,
        ],
    ],

    'testCreateInvoiceWithoutLineItemsWithAmountAndDesc' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'amount'        => 1000,
                'description'   => 'For special service'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'customer_email'   => 'test@razorpay.com',
                    'customer_contact' => '9999999999',
                    'customer_name'    => 'test',
                    'customer_address' => null,
                ],
                'line_items'   => [],
                'status'       => 'issued',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'view_less'    => true,
                'amount'       => 1000,
                'description'  => 'For special service',
                'currency'     => 'INR',
                'payment_id'   => null,
            ],
        ],
    ],

    'testCreateInvoiceWithoutLineItemsWithAmount' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'amount'        => 1000,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The description field is required when amount is present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithoutLineItemsAmountAndDesc' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Provide either line_items or amount, description.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithLineItemsAmountAndDesc' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                ],
                'line_items'    => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                    ]
                ],
                'amount'        => 1000,
                'description'   => 'For some special service'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Provide either line_items or amount, description.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testGetInvoiceByReceipt' => [
        'request' => [
            'url' => '/invoices',
            'method'  => 'get',
            'content' => [
                'receipt' => '00000000000002'
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id'               => 'inv_1000002invoice',
                        'receipt'          => '00000000000002',
                        'customer_id'      => 'cust_100000customer',
                        'customer_details' => [
                            'customer_email'   => 'test@razorpay.com',
                            'customer_contact' => '1234567890',
                            'customer_name'    => 'test',
                            'customer_address' => null,
                        ],
                        'line_items'       => [],
                        'customer_id'      => 'cust_100000customer',
                        'short_url'        => 'http://bitly.dev/2eZ11Vn',
                        'notes'            => [],
                        'status'           => 'issued',
                        'sms_status'       => 'sent',
                        'email_status'     => 'sent',
                        'view_less'        => true,
                    ],
                ],
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

    'testGetInvoicesOfCapturedPaymentId' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id' => 'inv_1000000invoice',
                        'customer_id' => 'cust_100000customer',
                        'order_id' => 'order_100000000order',
                        'line_items' => [
                            [
                                'id' => 'li_100000lineitem',
                            ]
                        ],
                        'status' => 'paid',
                    ]
                ]
            ],
        ],
    ],

    'testGetInvoicesAfterCreatingMultipleInvoicesAndPaying' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'status' => 'paid',
                        'customer_id' => 'cust_100000customer',
                    ],
                ],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice status cannot be retrieved now',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVOICE_STATUS_UNAVAILABLE,
        ],
    ],

    'testSendNotificationWithSmsMode' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify/sms',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ],
    ],

    'testSendNotificationWithInvalidMode' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify/invalid',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'invalid is not a valid communication medium',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
