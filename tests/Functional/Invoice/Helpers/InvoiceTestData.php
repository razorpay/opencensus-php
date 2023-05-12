<?php

namespace RZP\Tests\Functional\Invoice;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [

    // ------------------------------------------------------------
    // Creation of Invoice
    // ------------------------------------------------------------

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
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],
    'testCreateInvoiceWithPartnerAuth' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],
    'testCreateInvoiceWithBatchIdInHeader' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],

    'testCreateInvoiceLinkWithIdempotentId' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer'        => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'type'            => 'link',
                'view_less'       => 1,
                'amount'          => 100,
                'currency'        => 'INR',
                'description'     => 'Any Description about paymentLink',
                'partial_payment' => '0',
                'idempotency_key' => 'B24Y8gjypHOVOm'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => null,
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'           => 'issued',
                'sms_status'       => 'pending',
                'email_status'     => 'pending',
                'view_less'        => true,
                'amount'           => 100,
                'currency'         => 'INR',
                'payment_id'       => null,
                'type'             => 'link',
                'idempotency_key'  => 'B24Y8gjypHOVOm'
            ],
        ],
    ],

    'testCreateInvoiceLinkWithIdempotentIdAndGetTheResponse' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer'        => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'type'            => 'link',
                'view_less'       => 1,
                'amount'          => 100,
                'currency'        => 'INR',
                'description'     => 'Any Description about paymentLink',
                'partial_payment' => '0',
                'idempotency_key' => 'B24Y8gjypHOVOm'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => '1',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'status'           => 'issued',
                'sms_status'       => 'sent',
                'email_status'     => 'sent',
                'view_less'        => true,
                'amount'           => 100000,
                'currency'         => 'INR',
                'payment_id'       => null,
                'type'             => 'link',
                'idempotency_key'  => 'B24Y8gjypHOVOm'
            ],
        ],
    ],

    'testCreateInvoiceWithNewCustomerAndAddress' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'invoice_number' => 'inv# xyz',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'billing_address' => [
                        'line1'   => 'Line One Etc',
                        'line2'   => 'Line Two Etc',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'zipcode' => '560078',
                        'country' => 'India',
                    ],
                    'shipping_address' => [
                        'line1'   => 'Shipping Line One Etc',
                        'line2'   => 'Shipping Line Two Etc',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'zipcode' => '560080',
                        'country' => 'India',
                    ],
                ],
                'line_items'    => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'type'        => 'invoice',
                    ]
                ],
                'currency' => 'INR',
                'date' => null,
                'type' => 'ecod',
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => 'inv# xyz',
                'invoice_number'   => 'inv# xyz',
                'customer_details' => [
                    'name'            => 'test',
                    'email'           => 'test@razorpay.com',
                    'contact'         => '9999999999',
                    'billing_address' => [
                        'type'    => 'billing_address',
                        'primary' => true,
                        'line1'   => 'Line One Etc',
                        'line2'   => 'Line Two Etc',
                        'zipcode' => '560078',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                    'shipping_address' => [
                        'type'    => 'shipping_address',
                        'primary' => true,
                        'line1'   => 'Shipping Line One Etc',
                        'line2'   => 'Shipping Line Two Etc',
                        'zipcode' => '560080',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'date'         => null,
                'type'         => 'ecod',
                'view_less'    => true,
                'amount'       => 100000,
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
                'date' => 1480666664,
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
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
                'sms_status' => 'pending',
                'email_status' => 'pending',
                'date' => 1480666664,
                'view_less' => true,
                'amount' => 100000
            ],
        ],
    ],

    'testCreateBulkInvoices' => [
        'request' => [
            'url'     => '/invoices/bulk',
            'method'  => 'post',
            'content' => [
                [
                    'receipt'    => '00000000000001',
                    'customer'   => [
                        'email'   => 'test1@razorpay.com',
                        'contact' => '9999999999',
                        'name'    => 'test1',
                        'gstin'   => '29ABCDE1234L1Z1',
                    ],
                    'line_items' => [
                        [
                            'name'        => 'Some item name',
                            'description' => 'Some item description',
                            'amount'      => 100000,
                            'hsn_code'    => '00110022'
                        ],
                    ],
                ],
                [
                    'receipt'    => '00000000000002',
                    'customer'   => [
                        'email'   => 'test2@razorpay.com',
                        'contact' => '9999999998',
                        'name'    => 'test2',
                        'gstin'   => '29ABCDE1234L1Z2',
                    ],
                    'line_items' => [
                        [
                            'name'        => 'Some item name',
                            'description' => 'Some item description',
                            'amount'      => 100000,
                            'hsn_code'    => '00110022'
                        ],
                    ],
                ]
            ]
        ],
        'response' => [
            'content'     => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'receipt'          => '00000000000001',
                        'customer_details' => [
                            'email'   => 'test1@razorpay.com',
                            'contact' => '9999999999',
                            'name'    => 'test1',
                            'gstin'   => '29ABCDE1234L1Z1',
                        ],
                        'line_items'       => [
                            [
                                'name'        => 'Some item name',
                                'description' => 'Some item description',
                                'amount'      => 100000,
                                'quantity'    => 1,
                                'type'        => 'invoice',
                                'hsn_code'    => '00110022'
                            ]
                        ],
                        'status'           => 'issued',
                        'sms_status'       => 'pending',
                        'email_status'     => 'pending',
                        'view_less'        => true,
                        'amount'           => 100000,
                        'currency'         => 'INR',
                        'payment_id'       => null,
                        'type'             => 'invoice',
                    ],
                    [
                        'receipt'          => '00000000000002',
                        'customer_details' => [
                            'email'   => 'test2@razorpay.com',
                            'contact' => '9999999998',
                            'name'    => 'test2',
                            'gstin'   => '29ABCDE1234L1Z2',
                        ],
                        'line_items'       => [
                            [
                                'name'        => 'Some item name',
                                'description' => 'Some item description',
                                'amount'      => 100000,
                                'quantity'    => 1,
                                'type'        => 'invoice',
                                'hsn_code'    => '00110022'
                            ]
                        ],
                        'status'           => 'issued',
                        'sms_status'       => 'pending',
                        'email_status'     => 'pending',
                        'view_less'        => true,
                        'amount'           => 100000,
                        'currency'         => 'INR',
                        'payment_id'       => null,
                        'type'             => 'invoice',
                    ],
                ],
            ],
        ],
    ],

    'testCreateInvoiceWithCustomerIdAndDetails' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'customer'    => [
                    'name' => 'test',
                ],
                'line_items'  => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                    ]
                ],
                'currency' => 'INR',
                'date'     => 1480666664,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either of customer_id or customer must be sent in input',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithDefinedDisplayName' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id'       => 'cust_100000customer',
                'line_items'        => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ]
                ],
                'supply_state_code' => '29',
                'currency'          => 'INR',
                'date'              => 1480666664,
            ],
        ],
        'response' => [
            'content' => [
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'date'         => 1480666664,
                'view_less'    => true,
                'amount'       => 100000
            ],
        ],
    ],


    'testCreateInvoiceWithNestedCustomerIdAndDetails' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer'   => [
                    'id'    => 'cust_100001customer',
                    'name'  => 'Test Override',
                    'email' => 'testoverride@razorpay.com',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ]
                ],
                'currency'   => 'INR',
                'date'       => 1480666664,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id'      => 'cust_100001customer',
                'customer_details' => [
                    'id'      => 'cust_100001customer',
                    'name'    => 'Test Override',
                    'email'   => 'testoverride@razorpay.com',
                    'contact' => '1234567890',
                ],
                'status'           => 'issued',
            ],
        ],
    ],

    'testCreateLinkWithSource' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'      => 100,
                'description' => 'Sample Description',
                'type'        => 'link',
                'source'      => 'seller_app',
            ],
        ],
        'response' => [
            'content' => [
                'line_items'   => [],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100,
            ],
        ],
    ],

    'testCreateInvoiceWithInternationalCurrencyTax' => [
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
                        'currency'      => 'USD',
                        'tax_rate'      => 120,
                    ],
                    [
                        'name'          => 'Another item',
                        'description'   => 'Another description',
                        'unit_amount'   => 200000,
                        'quantity'      => 2,
                        'currency'      => 'USD',
                        'tax_rate'      => 120,
                    ]
                ],
                'currency'    => 'USD',
                'description' => 'Just an invoice summary',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'unit_amount' => 100000,
                        'quantity'    => 1,
                        'currency'    => 'USD',
                        'tax_rate'    => null,
                    ],
                    [
                        'name'        => 'Another item',
                        'description' => 'Another description',
                        'amount'      => 200000,
                        'unit_amount' => 200000,
                        'quantity'    => 2,
                        'currency'    => 'USD',
                        'tax_rate'    => null,
                    ]
                ],
                'currency'     => 'USD',
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 500000,
                'description'  => 'Just an invoice summary',
            ],
        ],
    ],

    'testCreateInvoiceWithInternationalCurrency' => [
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
                        'currency'      => 'USD',
                    ],
                    [
                        'name'          => 'Another item',
                        'description'   => 'Another description',
                        'unit_amount'   => 200000,
                        'quantity'      => 2,
                        'currency'      => 'USD',
                    ]
                ],
                'currency'    => 'USD',
                'description' => 'Just an invoice summary',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'unit_amount' => 100000,
                        'quantity'    => 1,
                        'currency'    => 'USD',
                    ],
                    [
                        'name'        => 'Another item',
                        'description' => 'Another description',
                        'amount'      => 200000,
                        'unit_amount' => 200000,
                        'quantity'    => 2,
                        'currency'    => 'USD',
                    ]
                ],
                'currency'     => 'USD',
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 500000,
                'description'  => 'Just an invoice summary',
            ],
        ],
    ],

    'testCreateLinkWithExpiryRequiredFeature' => [
        'request'  => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'      => 100,
                'description' => 'Sample Description',
                'type'        => 'link',
                'source'      => 'seller_app',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateLinkWithInvalidSource' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'      => 100,
                'description' => 'Sample Description',
                'type'        => 'link',
                'source'      => 'random_app',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid source: random_app',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateLinkWithoutReceipt' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'      => 100,
                'description' => 'Sample Description',
                'type'        => 'link',
                'source'      => 'seller_app',
            ],
        ],
        'response' => [
            'content' => [
                'receipt' => null,
            ],
        ],
    ],

    'testCreateLinkWithTooLargeAmount' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                // Max allowed amount for test merchant is 50000000.
                'amount'      => 60000000,
                'description' => 'Sample Description',
                'type'        => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice amount exceeds maximum payment amount allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateLinkReminderEnable' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'amount'      => '12300',
                'description' => 'test',
                'type'        => 'link',
                'customer'    =>[
                        'contact' => '1234567890',
                        'email'   => 'abc@abc.com'
                    ],
                'reminder_enable' => true
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testCreateLinkReminderDisable' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'amount'      => '12300',
                'description' => 'test',
                'type'        => 'link',
                'customer'    =>[
                    'contact' => '1234567890',
                    'email'   => 'abc@abc.com'
                ],
                'reminder_enable' => false
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testCreateLinkReminderFieldNotThere' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'amount'      => '12300',
                'description' => 'test',
                'type'        => 'link',
                'customer'    =>[
                    'contact' => '1234567890',
                    'email'   => 'abc@abc.com'
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testCreateLinkCustomerContactEmailNullOldMerchantFlagDisabled' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'        => 1234,
                'description'   => 'Sample Description',
                'type'          => 'link',
                'customer'      => [],
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateLinkCustomerContactEmailNullOldMerchantFlagEnabled' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'        => 1234,
                'description'   => 'Sample Description',
                'type'          => 'link',
                'customer'      => [],
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testCreateLinkCustomerContactEmailNullNewMerchant' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'amount'        => 1234,
                'description'   => 'Sample Description',
                'type'          => 'link',
                'customer'      => [],
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
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
                        'unit_amount'   => 200000,
                        'quantity'      => 2,
                    ]
                ],
                'currency'    => 'INR',
                'description' => 'Just an invoice summary',
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'unit_amount' => 100000,
                        'quantity'    => 1,
                    ],
                    [
                        'name'        => 'Another item',
                        'description' => 'Another description',
                        'amount'      => 200000,
                        'unit_amount' => 200000,
                        'quantity'    => 2,
                    ]
                ],
                'currency'     => 'INR',
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 500000,
                'description'  => 'Just an invoice summary',
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
                    'description' => 'Currency of all items should be the same as of the invoice.',
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
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'line_items' => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'quantity'      => 5,
                        'tax_rate'      => 120,
                    ],
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'tax_rate'    => null,
                    ],
                ],
                'currency'     => 'INR',
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 600000
            ],
        ],
    ],

    'testCreateInvoiceWithUsingInactiveItem' => [
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Item cannot be used as it is inactive',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ITEM_INACTIVE,
        ],
    ],

    'testCreateInvoiceWithItemOfTypeNonInvoice' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'line_items'  => [
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'invoice can only use item of one of following types: invoice',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
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
                'email_status' => 'pending',
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
                    'email'   => null,
                    'contact' => null,
                    'name'    => null,
                ],
                'line_items'           => [],
                'status'               => 'draft',
                'sms_status'           => 'pending',
                'email_status'         => 'pending',
                'view_less'            => true,
                'type'                 => 'invoice',
                'amount'               => null,
                'currency'             => 'INR',
                'description'          => null,
                'notes'                => [],
                'comment'              => null,
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
                'line_items'     => [
                    [
                        'name'   => 'Aweseome',
                        'amount' => 1000
                    ]
                ],
                'customer'       => [
                    'name'       => 'Abc Def'
                ],
                'comment'        => 'Thank you for giving us a chance to serve you.',
                'draft'          => '1'
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_details' => [
                    'name'    => 'Abc Def',
                    'email'   => null,
                    'contact' => null,
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
                'amount'           => 1000,
                'description'      => 'Abc def',
                'notes'            => [],
                'comment'        => 'Thank you for giving us a chance to serve you.',
                'currency'         => 'INR',
                'short_url'        => null,
                'view_less'        => true,
                'type'             => 'invoice',
            ],
        ],
    ],

    'testCreateDraftInvoiceWithLineItemsAndMaxAllowedAmount' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'content' => [
                'line_items' => [
                    [
                        'name'     => 'Line item #1',
                        'amount'   => 10000000,
                        'quantity' => 4,
                    ],
                    [
                        'name'     => 'Line item #2',
                        'amount'   => 15000000,
                        'quantity' => 2,
                    ],
                ],
                'type' => 'invoice',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice amount exceeds maximum payment amount allowed.',
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
                    'name'       => 'Abc Def',
                    'email'      => 'test@rzp.com',
                ],
                'draft'          => '0'
            ],
        ],
        'response' => [
            'content' => [
                'entity'               => 'invoice',
                'receipt'              => null,
                'customer_details'     => [
                    'name'    => 'Abc Def',
                    'email'   => 'test@rzp.com',
                    'contact' => null,
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
                'sms_status'           => 'pending',
                'email_status'         => 'pending',
                'amount'               => 1000,
                'description'          => null,
                'notes'                => [],
                'currency'             => 'INR',
                'view_less'            => true,
                'type'                 => 'invoice',
            ]
        ]
    ],

    'testCreateIssuedInvoiceForMYMerchant' => [
        'request' => [
            'url'       => '/invoices',
            'method'    => 'post',
            'content'   => [
                'line_items'     => [
                    [
                        'name'   => 'Abc Def',
                        'amount' => 1000,
                        'currency'=> 'MYR',

                    ]
                ],
                'customer'       => [
                    'name'       => 'Abc Def',
                    'email'      => 'test@rzp.com',
                ],
                'draft'          => '0',
                'currency'       => 'MYR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'               => 'invoice',
                'receipt'              => null,
                'customer_details'     => [
                    'name'    => 'Abc Def',
                    'email'   => 'test@rzp.com',
                    'contact' => null,
                ],
                'line_items'           => [
                    [
                        'quantity'         => 1,
                        'name'             => 'Abc Def',
                        'description'      => null,
                        'amount'           => 1000,
                        'currency'         => 'MYR'
                    ]
                ],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'sms_status'           => 'pending',
                'email_status'         => 'pending',
                'amount'               => 1000,
                'description'          => null,
                'notes'                => [],
                'currency'             => 'MYR',
                'view_less'            => true,
                'type'                 => 'invoice',
            ]
        ]
    ],

    'testCreateDraftInvoiceForMYMerchant' => [
    'request' => [
        'url'       => '/invoices',
        'method'    => 'post',
        'content'   => [
            'line_items'     => [
                [
                    'name'   => 'Abc Def',
                    'amount' => 1000,
                    'currency'=> 'MYR',

                ]
            ],
            'customer'       => [
                'name'       => 'Abc Def',
                'email'      => 'test@rzp.com',
            ],
            'draft'          => '1',
            'currency'       => 'MYR',
        ],
    ],
    'response' => [
        'content' => [
            'entity'               => 'invoice',
            'receipt'              => null,
            'customer_details'     => [
                'name'    => 'Abc Def',
                'email'   => 'test@rzp.com',
                'contact' => null,
            ],
            'line_items'           => [
                [
                    'quantity'         => 1,
                    'name'             => 'Abc Def',
                    'description'      => null,
                    'amount'           => 1000,
                    'currency'         => 'MYR'
                ]
            ],
            'payment_id'           => null,
            'status'               => 'draft',
            'paid_at'              => null,
            'sms_status'           => 'pending',
            'email_status'         => 'pending',
            'amount'               => 1000,
            'description'          => null,
            'notes'                => [],
            'currency'             => 'MYR',
            'view_less'            => true,
            'type'                 => 'invoice',
        ]
    ]
],

    'testCreateInvoiceWithReceiptMandatoryFailure' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'currency'    => 'INR',
                'line_items'  => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Receipt is a required field and must be set',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithDuplicateReceiptFails' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'receipt'     => '00000000000001',
                'currency'    => 'INR',
                'line_items'  => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'receipt must be unique for each item : 00000000000001',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithDuplicateReceiptSucceedsIfAllowed' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'receipt'     => '00000000000001',
                'currency'    => 'INR',
                'line_items'  => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'invoice',
                'receipt' => '00000000000001',
            ],
        ],
    ],

    'testCreateInvoiceWithDuplicateReceiptSucceeds' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'receipt'     => '00000000000001',
                'currency'    => 'INR',
                'line_items'  => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'invoice',
                'receipt' => '00000000000001',
            ],
        ],
    ],

    'testCreateDraftLinkWithAmountAndDesc' => [
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
                'description'   => 'For special service',
                'draft'         => '1',
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'line_items'   => [],
                'status'       => 'draft',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 1000,
                'description'  => 'For special service',
                'currency'     => 'INR',
                'payment_id'   => null,
            ],
        ],
    ],

    'testCreateIssuedLinkWithAmountAndDesc' => [
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
                'description'   => 'For special service',
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'line_items'   => [],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 1000,
                'description'  => 'For special service',
                'currency'     => 'INR',
                'payment_id'   => null,
            ],
        ],
    ],

    'testCreateDraftLinkWithAmount' => [
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
                'draft'         => '1',
                'type'          => 'link'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'line_items'   => [],
                'status'       => 'draft',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 1000,
                'description'  => null,
                'currency'     => 'INR',
                'payment_id'   => null,
            ],
        ],
    ],

    'testCreateIssuedLinkWithAmount' => [
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
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'description is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateIssuedLinkWithoutLineItemsAmount' => [
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
                'description'   => 'Just an invoice summary',
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount cannot be empty.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateDraftLinkWithLineItemsAndAmount' => [
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
                'description'   => 'For some special service',
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount should not be sent if line_items are being sent in the input.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithNullCurrency' => [
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
                'currency'      => null,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The currency field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithAmount' => [
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
                'currency'      => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount can be only sent for ecod or link types.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithAmountIntCurrency' => [
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
                'amount'        => 5,
                'currency'      => 'USD',
                'type'          => 'link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount must be atleast USD 0.10',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceWithBadExpiredBy' => [
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
                'currency'      => 'INR',
                'expire_by'     => 1484512480,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateInvoiceAndAssertEsSync' => [
        'request' => [
            'url'    => '/invoices',
            'method' => 'post',
            'content' => [
                'customer' => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'type' => 'invoice',
                'line_items' => [
                    [
                        'name'   => 'Sample Item',
                        'amount' => 100,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => null,
                'status'           => 'issued',
                'description'      => null,
                'notes'            => [],
                'type'             => 'invoice',
                'payment_id'       => null,
            ],
        ]
    ],

    // ------------------------------------------------------------
    // Updation of invoice
    // ------------------------------------------------------------

    'testUpdateDraftInvoiceWithAmount' => [
        'request'   => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'amount'            => 1000,
                'supply_state_code' => null,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount can be only sent for ecod or link types.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceWithBasicFields' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'invoice_number' => 'inv_receipt_0001',
                'sms_notify'   => '0',
                'email_notify' => '0',
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
                'invoice_number'       => 'inv_receipt_0001',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'             => null,
                'line_items'           => [
                    [
                        'id'          => 'li_100000lineitem',
                        'quantity'    => 1,
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'currency'    => 'INR',
                    ],
                ],
                'payment_id'           => null,
                'status'               => 'draft',
                'issued_at'            => null,
                'paid_at'              => null,
                'sms_status'           => null,
                'email_status'         => null,
                'date'                 => 1480506888,
                'terms'                => 'Updated terms & conditions',
                'amount'               => 100000,
                'description'          => null,
                'notes'                => [
                    'new_key'          => 'new_value'
                ],
                'currency'             => 'INR',
                'short_url'            => null,
                'view_less'            => true,
                'type'                 => 'invoice',
            ]
        ]
    ],

    'testUpdateDraftInvoiceAndIssue' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'terms'        => 'Updated terms & conditions',
                'draft'        => '0',
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_id'          => 'cust_100000customer',
                'customer_details'     => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'line_items'           => [
                    [
                        'id'          => 'li_100000lineitem',
                        'quantity'    => 1,
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'currency'    => 'INR',
                    ],
                ],
                'status'               => 'issued',
                'terms'                => 'Updated terms & conditions',
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithBasicFieldsAndLineItems' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'sms_notify'   => '0',
                'email_notify' => '0',
                'date'         => 1480506888,
                'terms'        => 'Updated terms & conditions',
                'notes'        => [
                    'new_key'  => 'new_value'
                ],
                'line_items'   => [
                    [
                        'name'     => 'Very new item',
                        'amount'   => 100,
                        'quantity' => 3,
                    ],
                    [
                        'name'     => 'Very new item 2',
                        'amount'   => 200,
                        'quantity' => 4,
                    ],
                    [
                        'id'       => 'li_100000lineitem',
                        'quantity' => 2
                    ],
                    [
                        'id'     => 'li_100001lineitem',
                        'name'   => 'Very new item 3',
                        'amount' => 500
                    ],
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
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'             => null,
                'line_items'           => [
                    [
                        'id'          => 'li_100000lineitem',
                        'quantity'    => 2,
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'currency'    => 'INR',
                    ],
                    [
                        'id'          => 'li_100001lineitem',
                        'quantity'    => 1,
                        'name'        => 'Very new item 3',
                        'description' => 'Some item description',
                        'amount'      => 500,
                        'currency'    => 'INR',
                    ],
                    [
                        'quantity'    => 3,
                        'name'        => 'Very new item',
                        'description' => null,
                        'amount'      => 100,
                        'currency'    => 'INR',
                    ],
                    [
                        'quantity'    => 4,
                        'name'        => 'Very new item 2',
                        'description' => null,
                        'amount'      => 200,
                        'currency'    => 'INR',
                    ],
                ],
                'payment_id'           => null,
                'status'               => 'draft',
                'issued_at'            => null,
                'paid_at'              => null,
                'sms_status'           => null,
                'email_status'         => null,
                'date'                 => 1480506888,
                'terms'                => 'Updated terms & conditions',
                'amount'               => 201600,
                'description'          => null,
                'notes'                => [
                    'new_key'          => 'new_value'
                ],
                'currency'             => 'INR',
                'short_url'            => null,
                'view_less'            => true,
                'type'                 => 'invoice',
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithLineItemsTooLargeAmount' => [
        'request' => [
            'url'    => '/invoices/inv_1000000invoice',
            'method' => 'patch',
            'content' => [
                'line_items' => [
                    [
                        'name' => 'Costly line item',
                        'quantity' => 1,
                        'amount' => 60000000,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice amount exceeds maximum payment amount allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceAmountWhenLineItemsExists' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'amount'       => 100,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount cannot be updated if Payment Link has line_items',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceWithCustomerId' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
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
                    'name'    => 'test 2',
                    'email'   => 'test2@razorpay.com',
                    'contact' => null,
                ],
                'status'               => 'draft',
            ]
        ]
    ],

    'testUpdateDraftInvoiceWithCustomerDetails' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'customer'  => [
                    'name'  => 'new customer',
                    'email' => null,
                    'gstin' => '29CFZPR4093Q1ZA',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'receipt'          => 'inv_receipt_0001',
                'status'           => 'draft',
                // On update of basic attributes, customer reference will be intact, only local copy gets updated
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'name'            => 'new customer',
                    'email'           => null,
                    'contact'         => '1234567890',
                    'gstin'           => '29CFZPR4093Q1ZA',
                    'billing_address' => null,
                ],
            ],
        ],
    ],

    'testUpdateDraftInvoiceWithCustomerIdAndDetails' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'customer'    => [
                    'name' => 'test',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Either of customer_id or customer must be sent in input',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceWithNestedCustomerIdAndDetails' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer'   => [
                    'id'    => 'cust_100000customer',
                    'name'  => 'Test Override',
                    'email' => 'testoverride@razorpay.com',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                    ]
                ],
                'currency'   => 'INR',
                'date'       => 1480666664,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'id'      => 'cust_100000customer',
                    'name'    => 'Test Override',
                    'email'   => 'testoverride@razorpay.com',
                    'contact' => '1234567890',
                ],
                'status'           => 'issued',
            ],
        ],
    ],

    'testUpdateDraftInvoiceWithCustomerBillingAddressId' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'  => 'inv_receipt_0001',
                'customer' => [
                    'name'               => 'new customer',
                    'email'              => 'new@razorpay.com',
                    'billing_address_id' => 'addr_1000000address',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'receipt'              => 'inv_receipt_0001',
                'customer_details'     => [
                    'name'            => 'new customer',
                    'email'           => 'new@razorpay.com',
                    'contact'         => '1234567890',
                    'billing_address' => [
                        'id'      => 'addr_1000000address',
                        'type'    => 'billing_address',
                        'primary' => false,
                        'line1'   => 'some line one',
                        'line2'   => 'some line two',
                        'zipcode' => '560078',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                ],
                'status'               => 'draft',
            ],
        ],
    ],

    'testUpdateDraftInvoiceWithCustomerBillingAndShippingAddressIds' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'receipt'  => 'inv_receipt_0001',
                'customer' => [
                    'name'                => 'new customer',
                    'email'               => 'new@razorpay.com',
                    'billing_address_id'  => 'addr_1000000address',
                    'shipping_address_id' => 'addr_1000001address',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'receipt'          => 'inv_receipt_0001',
                'customer_details' => [
                    'name'             => 'new customer',
                    'email'            => 'new@razorpay.com',
                    'contact'          => '1234567890',
                    'billing_address'  => [
                        'id'      => 'addr_1000000address',
                        'type'    => 'billing_address',
                        'primary' => false,
                        'line1'   => 'some line one',
                        'line2'   => 'some line two',
                        'zipcode' => '560078',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                    'shipping_address' => [
                        'id'      => 'addr_1000001address',
                        'type'    => 'shipping_address',
                        'primary' => false,
                        'line1'   => 'some line one',
                        'line2'   => 'some line two',
                        'zipcode' => '560080',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                ],
                'status'           => 'draft',
            ],
        ],
    ],

    'testUpdateDraftInvoiceWithSameBillingAndShippingAddressIds' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'receipt'  => 'inv_receipt_0001',
                'customer' => [
                    'name'                => 'new customer',
                    'email'               => 'new@razorpay.com',
                    'billing_address_id'  => 'addr_1000000address',
                    'shipping_address_id' => 'addr_1000000address',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'receipt'          => 'inv_receipt_0001',
                'customer_details' => [
                    'name'             => 'new customer',
                    'email'            => 'new@razorpay.com',
                    'contact'          => '1234567890',
                    'billing_address'  => [
                        'id'      => 'addr_1000000address',
                        'type'    => 'billing_address',
                        'primary' => false,
                        'line1'   => 'some line one',
                        'line2'   => 'some line two',
                        'zipcode' => '560078',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                    'shipping_address' => [
                        'id'      => 'addr_1000000address',
                        'type'    => 'billing_address',
                        'primary' => false,
                        'line1'   => 'some line one',
                        'line2'   => 'some line two',
                        'zipcode' => '560078',
                        'city'    => 'Bangalore',
                        'state'   => 'Karnataka',
                        'country' => 'in',
                    ],
                ],
                'status'           => 'draft',
            ],
        ],
    ],

    'testUpdateDraftInvoiceExpireBy' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1518220800,
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'inv_1000000invoice',
                'status'    => 'draft',
                'expire_by' => 1518220800,
            ]
        ],
    ],

    'testUpdateDraftInvoiceInvalidExpireBy' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1517443199,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateIssuedInvoiceExpireBy' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1518220800,
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'inv_1000000invoice',
                'status'    => 'issued',
                'expire_by' => 1518220800,
            ]
        ],
    ],

    'testUpdateIssuedInvoiceInvalidExpireBy' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1517443199,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePartiallyPaidInvoiceExpireBy' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1518220800,
            ],
        ],
        'response' => [
            'content' => [
                'id'        => 'inv_1000000invoice',
                'status'    => 'partially_paid',
                'expire_by' => 1518220800,
            ]
        ],
    ],

    'testUpdatePartiallyPaidInvoiceInvalidExpireBy' => [
        'request'   => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'expire_by' => 1517443199,
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'expire_by should be at least 15 minutes after current time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDraftInvoiceWithInvalidCustomerBillingAddressId' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
                'customer'  => [
                    'name'               => 'new customer',
                    'email'              => 'new@razorpay.com',
                    'billing_address_id' => 'addr_1000001address',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No db records found.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testUpdateDraftInvoiceUnsetCustomer' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'customer_id' => null,
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'customer_id'      => null,
                'customer_details' => [
                    'id'      => null,
                    'name'    => null,
                    'email'   => null,
                    'contact' => null,
                ],
                'status' => 'draft',
            ],
        ],
    ],

    'testUpdateDraftInvoiceUnsetCustomerWithNestedCustomerId' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'customer' => [
                    'id' => null,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'customer_id'      => null,
                'customer_details' => [
                    'name'    => null,
                    'email'   => null,
                    'contact' => null,
                ],
                'status'           => 'draft',
            ],
        ],
    ],

    'testUpdateIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
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
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'             => 'order_100000000order',
                'line_items'           => [],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'date'                 => null,
                'terms'                => 'Updated terms & conditions',
                'amount'               => 100000,
                'description'          => null,
                'notes'                => [
                    'new_key'          => 'new_value'
                ],
            ]
        ]
    ],

    'testUpdateIssuedInvoiceWithOrderAttributes' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'         => 'inv_receipt_0001',
                'partial_payment' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'id'              => 'inv_1000000invoice',
                'entity'          => 'invoice',
                'receipt'         => 'inv_receipt_0001',
                'partial_payment' => true,
            ]
        ]
    ],

    'testUpdateIssuedInvoiceWithExtraFields' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt'      => 'inv_receipt_0001',
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

    'testUpdateInvoiceAndAssertEsSync' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'receipt' => 'inv_receipt_0001',
                'terms'   => 'Updated terms & conditions',
                'notes'   => [
                    'key' => 'new value',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'      => 'inv_1000000invoice',
                'entity'  => 'invoice',
                'receipt' => 'inv_receipt_0001',
                'status'  => 'draft',
                'terms'   => 'Updated terms & conditions',
                'notes'   => [
                    'key' => 'new value',
                ],
            ]
        ]
    ],

    'testUpdateInvoiceAndAssertEsNoSync' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'patch',
            'content'   => [
                'expire_by' => 1910268762,
            ],
        ],
        'response' => [
            'content' => [
                'id'                   => 'inv_1000000invoice',
                'entity'               => 'invoice',
                'status'               => 'draft',
                'expire_by'            => 1910268762,
                'notes'                => [],
            ]
        ]
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
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
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
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'payment_id'           => null,
                'status'               => 'issued',
                'paid_at'              => null,
                'description'          => null,
            ]
        ]
    ],

    'testIssueInvoiceWithoutLineItems' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/issue',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'line_items is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testIssueInvoiceWithoutCustomer' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/issue',
            'method'    => 'post',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'customer is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testDeletePaidInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice',
            'method'    => 'delete',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in paid status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddLineItemToInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    'name'        => 'Item 1',
                    'description' => 'Item 1 Description',
                    'quantity'    => 10,
                    'amount'      => 200,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
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

    'testAddManyLineItemsToInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    'name'        => 'Item 1',
                    'description' => 'Item 1 Description',
                    'quantity'    => 10,
                    'amount'      => 100,
                ],
                [
                    'name'        => 'Item 2',
                    'description' => 'Item 2 Description',
                    'quantity'    => 10,
                    'amount'      => 200,
                ],
                [
                    'name'        => 'Item 3',
                    'description' => 'Item 3 Description',
                    'quantity'    => 10,
                    'amount'      => 300,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 10,
                        'name'             => 'Item 1',
                        'description'      => 'Item 1 Description',
                        'amount'           => 100,
                        'currency'         => 'INR'
                    ],
                    [
                        'quantity'         => 10,
                        'name'             => 'Item 2',
                        'description'      => 'Item 2 Description',
                        'amount'           => 200,
                        'currency'         => 'INR'
                    ],
                    [
                        'quantity'         => 10,
                        'name'             => 'Item 3',
                        'description'      => 'Item 3 Description',
                        'amount'           => 300,
                        'currency'         => 'INR'
                    ],
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 6000,
                'currency'         => 'INR',
            ]
        ]
    ],

    'testAddTooManyLineItemsToInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    'name'     => 'Very new item',
                    'amount'   => 100,
                ],
                [
                    'name'     => 'Very new item 2',
                    'amount'   => 200,
                    'quantity' => 2,
                ],
                [
                    'name'     => 'Very new item 3',
                    'amount'   => 300,
                    'quantity' => 2,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The invoice may not have more than 20 items in total.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddManyLineItemsToInvoiceWithBadData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    'name'        => 'Item 1',
                    'description' => 'Item 1 Description',
                    'quantity'    => 10,
                    'amount'      => 100,
                ],
                [
                    'name'        => 'Item 2',
                    'description' => 'Item 2 Description',
                    'quantity'    => 10,
                    'amount'      => 200,
                ],
                [
                    'name'        => 'Item 3',
                    'description' => 'Item 3 Description',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required when item id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddLineItemToInvoiceWithBadData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    'name'        => 'Item 1',
                    'description' => 'Item 1 Description',
                    'quantity'    => 10,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount field is required when item id is not present.',
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
            'method'    => 'patch',
            'content'   => [
                'quantity'    => 10,
                'description' => 'Some different description from item template'
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'invoice',
                'receipt'          => null,
                'customer_id'      => 'cust_100000customer',
                'customer_details' => [
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 10,
                        'name'             => 'Some item name',
                        'description'      => 'Some different description from item template',
                        'amount'           => 100000,
                        'currency'         => 'INR'
                    ]
                ],
                'payment_id'       => null,
                'status'           => 'draft',
                'amount'           => 1000000,
                'currency'         => 'INR',
            ]
        ]
    ],


    'testUpdateLineItemOfInvoiceWithExistingItem' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'patch',
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
                    'name'    => 'test',
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                ],
                'order_id'         => null,
                'line_items'       => [
                    [
                        'quantity'         => 5,
                        'name'             => 'A different item',
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
            'method'    => 'patch',
            'content'   => [
                'quantity'    => 5,
                'name'        => 'New item',
                'currency'    => 'USD',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Currency of all items should be the same as of the invoice.',
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
            'content' => [
                'id'         => 'inv_1000000invoice',
                'entity'     => 'invoice',
                'line_items' => [],
                'type'       => 'invoice',
                'view_less'  => true,
                'notes'      => [],
                'status'     => 'draft',
                'amount'     => null,
                'currency'   => 'INR',
            ]
        ]
    ],

    'testRemoveManyLineItemsOfInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/bulk',
            'method'    => 'delete',
            'content'   => [
                'ids' => [
                    'li_100000lineitem',
                    'li_100001lineitem',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'inv_1000000invoice',
                'entity'           => 'invoice',
                'line_items' => [
                    [
                        'id'       => 'li_100002lineitem',
                        'quantity' => 1,
                        'name'     => 'Some item name',
                        'amount'   => 100000,
                        'currency' => 'INR',
                    ]
                ],
                'type'             => 'invoice',
                'view_less'        => true,
                'notes'            => [],
                'status'           => 'draft',
                'amount'           => 100000,
                'currency'         => 'INR',
            ]
        ]
    ],

    'testRemoveManyLineItemsOfInvoiceWithBadData' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/bulk',
            'method'    => 'delete',
            'content'   => [
                'ids' => [
                    'li_100000lineitem',
                    'li_100001lineitem',
                    'li_10000Xlineitem',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more of the ids provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_IDS,
        ],
    ],

    'testAddLineItemsToIssuedInvoice' => [
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
                    'description' => 'Operation not allowed for Invoice in issued status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddManyLineItemsToIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items',
            'method'    => 'post',
            'content'   => [
                [
                    [
                        'name'        => 'Item 1',
                        'description' => 'Item 1 Description',
                        'quantity'    => 10,
                        'amount'      => 100,
                    ],
                    [
                        'name'        => 'Item 2',
                        'description' => 'Item 2 Description',
                        'quantity'    => 10,
                        'amount'      => 200,
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in issued status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateLineItemOfIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/li_100000lineitem',
            'method'    => 'patch',
            'content'   => [
                'quantity'    => 100,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in issued status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'description' => 'Operation not allowed for Invoice in issued status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRemoveManyLineItemsOfIssuedInvoice' => [
        'request' => [
            'url'       => '/invoices/inv_1000000invoice/line_items/bulk',
            'method'    => 'delete',
            'content'   => [
                'ids' => [
                    'li_100000lineitem',
                    'li_100001lineitem',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in issued status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testSendNotificationWithEmailMode' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify/email',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ],
    ],

    'testSendNotificationWithSmsModeForDraftInvoice' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/notify/invalid',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in draft status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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
                    'description' => 'invalid is not a valid communication medium.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSendNotificationWithEmailModeByPrivateAuthRoute' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice/notify_by/email',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ],
    ],

    // ------------------------------------------------------------
    // Get invoice
    // ------------------------------------------------------------

    'testGetInvoice' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
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
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                            'name'    => 'test',
                        ],
                        'line_items'       => [
                            [
                                'id'            => 'li_100002lineitem',
                                'name'          => 'Some item name',
                                'description'   => 'Some item description',
                                'amount'        => 100000,
                                'unit_amount'   => 100000,
                                'gross_amount'  => 100000,
                                'tax_amount'    => 0,
                                'net_amount'    => 100000,
                                'currency'      => 'INR',
                                'tax_inclusive' => false,
                                'unit'          => null,
                                'quantity'      => 1,
                                'taxes'         => [],
                            ],
                        ],
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

    'testGetInvoiceByOrderAndPayment' => [
        'request' => [
            'url'     => '/admin/invoice',
            'method'  => 'get',
            'content' => [
                'order_id'   => 'order_100000000order',
                'payment_id' => 'pay_1000000payment',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 0,
            ],
        ],
    ],

    'testGetInvoiceWithPayments' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice',
            'method'  => 'get',
            'content' => [
                'expand'   => [
                    'payments',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'             => 'inv_1000000invoice',
                'entity'         => 'invoice',
                'customer_id'    => 'cust_100000customer',
                'customer_details' => [
                    'name'            => 'test',
                    'email'           => 'test@razorpay.com',
                    'contact'         => '1234567890',
                    'billing_address' => null,
                ],
                'order_id'       => 'order_100000000order',
                'line_items'     => [],
                'payments'       => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'entity'            => 'payment',
                            'amount'            => 100000,
                            'currency'          => 'INR',
                            'status'            => 'captured',
                            'order_id'          => 'order_100000000order',
                            'invoice_id'        => 'inv_1000000invoice',
                            'international'     => false,
                            'method'            => 'card',
                            'amount_refunded'   => 0,
                            'refund_status'     => null,
                            'captured'          => true,
                            'description'       => 'random description',
                            'bank'              => null,
                            'wallet'            => null,
                            'vpa'               => null,
                            'email'             => 'a@b.com',
                            'contact'           => '+919918899029',
                            'notes'             => [
                                'merchant_order_id' => 'random order id',
                            ],
                            'fee'               => 2000,
                            'error_code'        => null,
                            'error_description' => null,
                            'acquirer_data'     => [],
                            'tax'               => 0,
                        ],
                    ],
                ],
                'status'         => 'paid',
                'amount'         => 100000,
                'amount_paid'    => 100000,
                'amount_due'     => 0,
                'currency'       => 'INR',
                'type'           => 'invoice',
            ],
        ],
    ],

    'testGetInvoiceWithPaymentsCard' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice',
            'method'  => 'get',
            'content' => [
                'expand'   => [
                    'payments.card',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'             => 'inv_1000000invoice',
                'entity'         => 'invoice',
                'customer_id'    => 'cust_100000customer',
                'customer_details' => [
                    'name'            => 'test',
                    'email'           => 'test@razorpay.com',
                    'contact'         => '1234567890',
                    'billing_address' => null,
                ],
                'order_id'       => 'order_100000000order',
                'line_items'     => [],
                'payments'       => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'entity'            => 'payment',
                            'amount'            => 100000,
                            'currency'          => 'INR',
                            'status'            => 'captured',
                            'order_id'          => 'order_100000000order',
                            'invoice_id'        => 'inv_1000000invoice',
                            'international'     => false,
                            'method'            => 'card',
                            'amount_refunded'   => 0,
                            'refund_status'     => null,
                            'captured'          => true,
                            'description'       => 'random description',
                            'bank'              => null,
                            'wallet'            => null,
                            'vpa'               => null,
                            'email'             => 'a@b.com',
                            'contact'           => '+919918899029',
                            'notes'             => [
                                'merchant_order_id' => 'random order id',
                            ],
                            'fee'               => 2000,
                            'error_code'        => null,
                            'error_description' => null,
                            'acquirer_data'     => [],
                            'tax'               => 0,
                            'card' => [
                                'entity'        => 'card',
                                'name'          => '',
                                'last4'         => '3335',
                                'network'       => 'Visa',
                                'type'          => 'credit',
                                'issuer'        => 'HDFC',
                                'international' => false,
                                'emi'           => true
                            ]
                        ],
                    ],
                ],
                'status'         => 'paid',
                'amount'         => 100000,
                'amount_paid'    => 100000,
                'amount_due'     => 0,
                'currency'       => 'INR',
                'type'           => 'invoice',
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

    'testGetMultipleInvoicesWithPayments' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [
                'expand' => [
                    'payments',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id'          => 'inv_100000invoice2',
                        'customer_id' => 'cust_100000customer',
                        'order_id'    => 'order_10000000order2',
                        'line_items'  => [],
                        'status'      => 'paid',
                        'payments'    => [
                            'entity' => 'collection',
                            'count'  => 1,
                            'items'  => [
                                [
                                    'entity'            => 'payment',
                                    'amount'            => 100000,
                                    'currency'          => 'INR',
                                    'status'            => 'captured',
                                    'order_id'          => 'order_10000000order2',
                                    'invoice_id'        => 'inv_100000invoice2',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id'          => 'inv_1000000invoice',
                        'customer_id' => 'cust_100000customer',
                        'order_id'    => 'order_100000000order',
                        'line_items'  => [],
                        'status'      => 'issued',
                        'payments'    => [],
                    ],
                ]
            ],
        ],
    ],

    'testGetMultipleInvoicesByTypes' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'get',
            'content' => [
                'types' => ['link', 'ecod'],
            ],
        ],
        'response' => [
            'content' => [
                'count' => 3,
                'items' => [
                    [
                        'id'   => 'inv_1000003invoice',
                        'type' => 'ecod',
                        'supply_state_code' => '29',
                    ],
                    [
                        'id'   => 'inv_1000002invoice',
                        'type' => 'ecod',
                        'supply_state_code' => '29',
                    ],
                    [
                        'id'   => 'inv_1000001invoice',
                        'type' => 'link',
                        'supply_state_code' => '29',
                    ],
                ]
            ],
        ],
    ],

    'testGetMultipleInvoicesOnlyEsFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'customer_name' => 'tes',
                'notes'         => 'info',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id'               => 'inv_1000000invoice',
                        'customer_details' => [
                            'name'    => 'test',
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                        ],
                        //
                        // Needs to have few fields which are not in es.
                        //
                        'sms_status'   => 'pending',
                        'email_status' => 'pending',
                        'amount'       => null,
                    ],
                    [
                        'id'               => 'inv_1000001invoice',
                        'customer_details' => [
                            'name'    => 'test',
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                        ],
                        'sms_status'   => 'pending',
                        'email_status' => 'pending',
                        'amount'       => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByQ' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'q'     => 'sampl',
                'skip'  => 10,
                'count' => 5,
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id'       => 'inv_1000000invoice',
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildAndFrom' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'receipt' => 'rec',
                'skip'    => 20,
                'count'   => 100,
                'from'    => 1498634126,
            ],
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildFromAndTo' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'receipt' => 'rec',
                'skip'    => 20,
                'count'   => 100,
                'from'    => 1498634126,
                'to'      => 1498644126,
            ],
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetMultipleInvoicesOnlyMysqlFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer',
                'user_id'     => '1000000000user',
                'skip'        => 0,
                'count'       => 100,
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id'          => 'inv_1000002invoice',
                        'customer_id' => 'cust_100000customer',
                        'user_id'     => '1000000000user',
                    ],
                    [
                        'id'          => 'inv_1000000invoice',
                        'customer_id' => 'cust_100000customer',
                        'user_id'     => '1000000000user',
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByOnlyCommonFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'status' => 'issued',
                'type'   => 'link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMultipleInvoicesByCommonAndMysqlFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'status'     => 'issued',
                'payment_id' => 'pay_1000000payment',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMultipleInvoicesByCommonAndEsFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'type'    => 'link',
                'status'  => 'issued',
                'receipt' => 'xyz',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetMultipleInvoicesMixedFields' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'type'        => 'link',
                'customer_id' => 'cust_100000customer',
                'receipt'     => 'xyz',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'customer_id not expected with other params sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetMultipleInvoicesSearchHitsOnly' => [
        'request' => [
            'url'     => '/invoices',
            'method'  => 'get',
            'content' => [
                'customer_name' => 'tes',
                'search_hits'   => '1',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 2,
                'items' => [
                    [
                        'id'               => 'inv_1000000invoice',
                        'customer_details' => [
                            'name'    => 'test',
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                        ],
                    ],
                    [
                        'id'               => 'inv_1000001invoice',
                        'customer_details' => [
                            'name'    => 'test',
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                        ],
                    ],
                ],
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

    'testGetInvoicesLineItemsWithTaxableAmount' => [
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
                                'taxable_amount' => 100000,
                            ]
                        ]
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

    'testPayExpiredInvoice' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice is not payable in expired status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayDeletedInvoice' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice is not payable as it is deleted.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelInvoice' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice/cancel',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                    ]
                ],
                'customer_id'  => 'cust_100000customer',
                'short_url'    => 'http://bitly.dev/2eZ11Vn',
                'notes'        => [],
                'status'       => 'cancelled',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'view_less'    => true,
            ],
        ],
    ],

    'testCancelPaymentInProgressInvoice' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invoice cannot be cancelled as payment for it has happened',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelPaidInvoice' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/cancel',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Operation not allowed for Invoice in paid status.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCancelInvoiceWithFailedPayment' => [
        'request' => [
            'url'     => '/invoices/inv_1000000invoice/cancel',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'name'    => 'test',
                ],
                'customer_id'  => 'cust_100000customer',
                'short_url'    => 'http://bitly.dev/2eZ11Vn',
                'notes'        => [],
                'status'       => 'cancelled',
                'sms_status'   => 'sent',
                'email_status' => 'sent',
                'view_less'    => true,
            ],
        ],
    ],

    'testUpdateExpiredInvoiceNotes' => [
        'request'  => [
            'url'     => '/invoices/inv_1000000invoice',
            'method'  => 'patch',
            'content' => [
                'notes' => [
                    'key2' => 'value2'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'inv_1000000invoice',
                'status' => 'expired',
                'notes'  => [
                    'key2' => 'value2'
                ],
            ]
        ],
    ],

    'testExpireInvoices' => [
        'request' => [
            'url'     => '/invoices/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_invoices_count' => 3,
                'failed_invoice_ids'   => [
                    '1000005invoice',
                ],
            ],
        ],
    ],

    'testIssueInvoiceByBatchId' => [
        'request' => [
            'url'     => '/invoices/batch/batch_00000000000001/issue',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testInvoiceExpiredWebhook' => [
        'request' => [
            'url'     => '/invoices/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_invoices_count' => 1,
                'failed_invoice_ids'   => [],
                // 'time_taken'           => '1 secs',
            ],
        ],
    ],

    'testInvoiceExpiredWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'invoice.expired',
        'contains' => [
            'invoice',
        ],
        'payload'  => [
            'invoice' => [
                'entity' => [
                    'id'               => 'inv_1000000invoice',
                    'entity'           => 'invoice',
                    'receipt'          => null,
                    'invoice_number'   => null,
                    'customer_id'      => 'cust_100000customer',
                    'customer_details' => [
                        'name'             => 'test',
                        'email'            => 'test@razorpay.com',
                        'contact'          => '1234567890',
                        'billing_address'  => null,
                        'customer_name'    => 'test',
                        'customer_email'   => 'test@razorpay.com',
                        'customer_contact' => '1234567890',
                    ],
                    'order_id'              => 'order_100000000order',
                    'payment_id'            => null,
                    'status'                => 'expired',
                    // 'expire_by'             => 1505201091,
                    // 'issued_at'             => 1505088000,
                    'paid_at'               => null,
                    'cancelled_at'          => null,
                    // 'expired_at'            => 1505201092,
                    'sms_status'            => 'sent',
                    'email_status'          => 'sent',
                    'date'                  => null,
                    'terms'                 => null,
                    'partial_payment'       => false,
                    'gross_amount'          => 100000,
                    'tax_amount'            => 0,
                    'amount'                => 100000,
                    'amount_paid'           => 0,
                    'amount_due'            => 100000,
                    'currency'              => 'INR',
                    'description'           => null,
                    'notes'                 => [],
                    'comment'               => null,
                    'short_url'             => 'http://bitly.dev/2eZ11Vn',
                    'view_less'             => true,
                    'billing_start'         => null,
                    'billing_end'           => null,
                    'type'                  => 'invoice',
                    'group_taxes_discounts' => false,
                    'user_id'               => null,
                    // 'created_at'            => 1505201092,
                ],
            ],
        ],
        // 'created_at' => 1505201092,
    ],

    'testInvoicePartiallyPaidWebhookEventData' => [
        'entity'   => 'event',
        'event'    => 'invoice.partially_paid',
        'contains' => [
            'payment',
            'order',
            'invoice',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 60000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 60000,
                    'amount_due'  => 40000,
                    'currency'    => 'INR',
                    'status'      => 'attempted',
                    'attempts'    => 1,
                ],
            ],
            'invoice' => [
                'entity' => [
                    'id'              => 'inv_1000000invoice',
                    'entity'          => 'invoice',
                    'order_id'        => 'order_100000000order',
                    'status'          => 'partially_paid',
                    'partial_payment' => true,
                    'gross_amount'    => 100000,
                    'tax_amount'      => 0,
                    'amount'          => 100000,
                    'amount_paid'     => 60000,
                    'amount_due'      => 40000,
                    'currency'        => 'INR',
                ],
            ],
        ],
    ],

    'testInvoiceMultiplePartiallyPaidWebhooksEventData1' => [
        'entity'   => 'event',
        'event'    => 'invoice.partially_paid',
        'contains' => [
            'payment',
            'order',
            'invoice',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 60000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 60000,
                    'amount_due'  => 40000,
                    'currency'    => 'INR',
                    'status'      => 'attempted',
                    'attempts'    => 1,
                ],
            ],
            'invoice' => [
                'entity' => [
                    'id'              => 'inv_1000000invoice',
                    'entity'          => 'invoice',
                    'order_id'        => 'order_100000000order',
                    'status'          => 'partially_paid',
                    'partial_payment' => true,
                    'gross_amount'    => 100000,
                    'tax_amount'      => 0,
                    'amount'          => 100000,
                    'amount_paid'     => 60000,
                    'amount_due'      => 40000,
                    'currency'        => 'INR',
                ],
            ],
        ],
    ],

    'testInvoiceMultiplePartiallyPaidWebhooksEventData2' => [
        'entity'   => 'event',
        'event'    => 'order.paid',
        'contains' => [
            'payment',
            'order',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 40000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 100000,
                    'amount_due'  => 0,
                    'currency'    => 'INR',
                    'status'      => 'paid',
                    'attempts'    => 2,
                ],
            ],
        ],
    ],

    'testInvoiceMultiplePartiallyPaidWebhooksEventData3' => [
        'entity'   => 'event',
        'event'    => 'invoice.paid',
        'contains' => [
            'payment',
            'order',
            'invoice',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 40000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 100000,
                    'amount_due'  => 0,
                    'currency'    => 'INR',
                    'status'      => 'paid',
                    'attempts'    => 2,
                ],
            ],
            'invoice' => [
                'entity' => [
                    'id'              => 'inv_1000000invoice',
                    'entity'          => 'invoice',
                    'order_id'        => 'order_100000000order',
                    'status'          => 'paid',
                    'partial_payment' => true,
                    'gross_amount'    => 100000,
                    'tax_amount'      => 0,
                    'amount'          => 100000,
                    'amount_paid'     => 100000,
                    'amount_due'      => 0,
                    'currency'        => 'INR',
                ],
            ],
        ],
    ],

    'testInvoicePaidAndOrderPaidWebhooksEventData1' => [
        'entity'   => 'event',
        'event'    => 'order.paid',
        'contains' => [
            'payment',
            'order',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 100000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 100000,
                    'amount_due'  => 0,
                    'currency'    => 'INR',
                    'status'      => 'paid',
                    'attempts'    => 1,
                ],
            ],
        ],
    ],

    'testInvoicePaidAndOrderPaidWebhooksEventData2' => [
        'entity'   => 'event',
        'event'    => 'invoice.paid',
        'contains' => [
            'payment',
            'order',
            'invoice',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 100000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 100000,
                    'amount_due'  => 0,
                    'currency'    => 'INR',
                    'status'      => 'paid',
                    'attempts'    => 1,
                ],
            ],
            'invoice' => [
                'entity' => [
                    'id'              => 'inv_1000000invoice',
                    'entity'          => 'invoice',
                    'order_id'        => 'order_100000000order',
                    'status'          => 'paid',
                    'partial_payment' => false,
                    'gross_amount'    => 100000,
                    'tax_amount'      => 0,
                    'amount'          => 100000,
                    'amount_paid'     => 100000,
                    'amount_due'      => 0,
                    'currency'        => 'INR',
                ],
            ],
        ],
    ],

    'testInvoiceNotifyForBatch' => [
        'request' => [
            'url'       => '/invoices/batch/batch_00000000000001/notify',
            'method'    => 'put',
            'content'   => [
                'sms_notify'    => 1,
                'email_notify'  => 1,
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testInvoiceSmsNotifyForBatch' => [
        'request' => [
            'url'       => '/invoices/batch/batch_00000000000001/notify',
            'method'    => 'put',
            'content'   => [
                'sms_notify'    => 1,
                'email_notify'  => 0,
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testInvoiceNotifyForBatchInputData'  => [
        'attributes' => [
            [
                'invoiceAttributes' => [
                    'id'                    => '1000001invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000001order',
                    'status'                => 'issued',
                    'email_status'          => null,
                    'sms_status'            => null,
                ],
                'orderAttributes'   => [
                    'id'                    => '100000001order'
                ],
            ],
            [
                'invoiceAttributes' => [
                    'id'                    => '1000002invoice',
                    'batch_id'              => '00000000000001',
                    'order_id'              => '100000002order',
                    'status'                => 'issued',
                    'email_status'          => null,
                    'sms_status'            => null,
                ],
                'orderAttributes'   => [
                    'id'                    => '100000002order'
                ],
            ],
        ],
    ],

    'testGetInvoiceDetailsForCheckout' => [
        'request' => [
            'url'       => '/internal/invoices/checkout',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content'     => [
                'invoice' => [
                    'url' => 'http://bitly.dev/2eZ11Vn',
                    'amount' => 100000,
                ],
                'order' => [
                    'partial_payment' => false,
                    'amount' => 1000000,
                    'currency' => 'INR',
                    'amount_paid' => 0,
                    'amount_due' => 1000000,
                    'first_payment_min_amount' => null,
                ],
                'customer' => [
                    'id' => 'cust_100000customer',
                    'entity' => 'customer',
                    'name' => 'test',
                    'email' => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'gstin' => null,
                    'notes' => [],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    // ----------------------------------------------------------------------
    // Expectations for ES

    'testGetInvoiceByReceiptExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'receipt' => [
                                    'query'                =>'00000000000002',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetInvoiceByReceiptExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000002invoice',
                ]
            ],
        ],
    ],

    'testGetMultipleInvoicesOnlyEsFieldsExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'customer_name' => [
                                    'query'                =>'tes',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'info',
                                ],
                            ],
                        ]
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                        ],
                                ],
                            ],
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesOnlyEsFieldsExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000000invoice',
                ],
                [
                    '_id' => '1000001invoice',
                ]
            ],
        ],
    ],

    'testGetMultipleInvoicesByQExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 10,
            'size'    => 5,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query'  => 'sampl',
                                'type'   => 'best_fields',
                                'fields' => [
                                    'receipt',
                                    'customer_name',
                                    'customer_contact',
                                    'customer_email',
                                    'description',
                                    'terms',
                                    'notes.value',
                                ],
                                'boost'                => 1,
                                'minimum_should_match' => '75%',
                                'lenient'              => true
                            ],
                        ]
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByQExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id' => '1000000invoice',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesSearchHitsOnlyExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => true,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'customer_name' => [
                                    'query'                =>'tes',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesSearchHitsOnlyExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_source' => [
                        'id'               => '1000000invoice',
                        'customer_email'   => 'test@razorpay.com',
                        'customer_contact' => '1234567890',
                        'customer_name'    => 'test',
                        'receipt'          => null,
                    ],
                ],
                [
                    '_source' => [
                        'id'               => '1000001invoice',
                        'customer_email'   => 'test@razorpay.com',
                        'customer_contact' => '1234567890',
                        'customer_name'    => 'test',
                        'receipt'          => null,
                    ],
                ]
            ],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildAndFromExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 20,
            'size'    => 100,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'receipt' => [
                                    'query'                =>'rec',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'range' => [
                                        'created_at' => [
                                            'gte' => 1498634126,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildAndFromExpectedSearchResponse' => [
        'hits' => [
            'hits' => [],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildFromAndToExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 20,
            'size'    => 100,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'receipt' => [
                                    'query'                =>'rec',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'range' => [
                                        'created_at' => [
                                            'gte' => 1498634126,
                                            'lte' => 1498644126,
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByEsFeildFromAndToExpectedSearchResponse' => [
        'hits' => [
            'hits' => [],
        ],
    ],

    'testGetMultipleInvoicesByCommonAndEsFieldsExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                [
                                    'exists' => [
                                        'field' => 'entity_type',
                                    ],
                                ],
                            ],
                            'must' => [
                                [
                                    'term' => [
                                        'type' => [
                                            'value' => 'link',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'status' => [
                                            'value' => 'issued',
                                        ],
                                    ],
                                ],
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'must' => [
                        [
                            'match' => [
                                'receipt' => [
                                    'query'                =>'xyz',
                                    'boost'                => 2,
                                    'minimum_should_match' => '75%',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleInvoicesByCommonAndEsFieldsExpectedSearchResponse' => [
        'hits' => [
            'hits' => [],
        ],
    ],

    'expectedUpsertIndexParams' => [
        //
        // Commented fields are dynamic and needs to be asserted in other ways,
        // but have left here (commented) to denote the presence.
        //
        'body' => [
            [
                'index' => [
                    '_index' => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
                    '_type'  => env('ES_ENTITY_TYPE_PREFIX').'invoice_test',
                    // '_id'    => '7KoRT3qkc1KGFb',
                ],
            ],
            [
                // 'id'               => '7KoRT3qkc1KGFb',
                'receipt'          => null,
                // 'order_id'         => '7KoRT8ar0gbHb7',
                'merchant_id'      => '10000000000000',
                'customer_name'    => 'test',
                'customer_email'   => 'test@razorpay.com',
                'customer_contact' => '1234567890',
                'description'      => null,
                'terms'            => null,
            ],
        ],
    ],

    'testUpdateBillingPeriod' => [
        'request' => [
            'url' => '/invoices/inv_1000000invoice/update_billing_period',
            'method' => 'patch',
            'content' => [
                'billing_start' => 1557305769,
                'billing_end'   => 1557386719,
            ],
        ],
        'response' => [
            'content' => [
                'billing_start' => 1557305769,
                'billing_end'   => 1557386719,
            ],
        ],
    ],

    'testCreateInvoiceLinkWithAutoReminders' => [
        'request'  => [
            'url'     => '/invoices',
            'method'  => 'post',
            'content' => [
                'customer'        => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'type'            => 'link',
                'view_less'       => 1,
                'amount'          => 100,
                'currency'        => 'INR',
                'description'     => 'Any Description about paymentLink',
                'partial_payment' => '0',
                'idempotency_key' => 'B24Y8gjypHOVOm'
            ],
        ],
        'response' => [
            'content' => [
                'receipt'          => null,
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'status'           => 'issued',
                'sms_status'       => 'pending',
                'email_status'     => 'pending',
                'view_less'        => true,
                'amount'           => 100,
                'currency'         => 'INR',
                'payment_id'       => null,
                'type'             => 'link',
                'idempotency_key'  => 'B24Y8gjypHOVOm'
            ],
        ],
    ],

    'testInvoiceSoftDelete' => [
        'request' => [
            'url' => '/invoices/delete',
            'method' => 'delete',
            'content' => [
                'hours' => 24,
                'merchant_ids' => ['100000Razorpay']
            ]
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'total_invoices_count' => 2,
                'failed_invoice_ids' => []
            ]
        ]
    ],

    'testCreateSendEmailForPaymentLinkService' => [
        'request'  => [
            'url'     => '/invoices/send_email',
            'method'  => 'post',
            'content' => [
                'invoice' => [
                    'id' => '30000000000000',
                ],
                'to' => 'r@g.c',
                'subject' => 'erhewhjhjrewjer',
                'view' => 'emails.invoice.customer.expiring',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateSendEmailForPaymentLinkServiceWithIntentUrl' => [
        'request'  => [
            'url'     => '/invoices/send_email',
            'method'  => 'post',
            'content' => [
                'invoice' => [
                    'id' => '30000000000000',
                    'status' => 'created',
                    'type' => 'payment_link',
                    'short_url' => 'http://bitly.dev/2eZ11Vn',
                    'amount_paid' => 0,
                    'type_label' => 'payment_link',
                    'amount_formatted' => 500,
                    'currency' => 'INR',
                    'receipt' => 'mamachandamama',
                    'amount' => 500,
                    'customer_details' => [
                        'customer_email'   => 'test@razorpay.com',
                        'customer_contact' => '9999999999',
                        'customer_name'    => 'test',
                    ],
                    'description'  => 'For special service',
                    'partial_payment' => 0,
                ],
                'to' => 'r@g.c',
                'subject' => 'erhewhjhjrewjer',
                'view' => 'emails.invoice.customer.notification_pl_v2',
                'intent_url' => "something works here",
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testSwitchPlVersionToV2' => [
        'request'  => [
            'url'     => '/payment_links_switch_versions',
            'method'  => 'post',
            'content' => [
                'switch_to' => 'v2',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testFetchIssuedLinkOlderThanSixMonths' => [
        'request' => [
            'url' => 'url_to_be_replaced',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'receipt'       => '00000000000001',
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                ],
                'line_items'   => [],
                'status'       => 'issued',
                'view_less'    => true,
                'amount'       => 1000,
                'description'  => 'For special service',
                'currency'     => 'INR',
                'payment_id'   => null,
            ]
        ]
    ],
    'testFetchCancelledAndExpiredLinkOlderThanSixMonths' => [
        'request' => [
            'url' => 'url_to_be_replaced',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],
    // ----------------------------------------------------------------------
];
