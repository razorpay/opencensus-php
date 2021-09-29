<?php

use RZP\Gateway\Upi\Icici\Fields;

return [
    'testCreateBharatQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'bharat_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateUpiQrCode' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => false,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testCreateUpiQrCodeFixedAmount' => [
        'name'           => 'Test QR Code',
        'description'    => 'QR code for tests',
        'usage'          => 'multiple_use',
        'type'           => 'upi_qr',
        'fixed_amount'   => true,
        'notes'          => [
            'a' => 'b',
        ],
    ],

    'testFetchQrCodeByCustomerId' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'         => 'Test QR Code',
                'description'  => 'QR code for tests',
                'type'         => 'upi_qr',
                'fixed_amount' => false,
                'customer_id'  => 'cust_100000customer'
            ]
        ]
    ],

    'testFetchQrCodeById' => [
        'name'         => 'Test QR Code',
        'description'  => 'QR code for tests',
        'type'         => 'upi_qr',
        'fixed_amount' => false,
        'customer_id'  => 'cust_100000customer'
    ],

    'testProcessIciciQrPayment' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],


    'processIciciQrPaymentWithDifferentAmountUtil' => [
        'url'     => '/payment/callback/bharatqr/upi_icici',
        'method'  => 'post',
        'content' => [
            Fields::MERCHANT_ID         => 'abcd_bharat_qr',
            Fields::SUBMERCHANT_ID      => '78965412',
            Fields::TERMINAL_ID         => '5411',
            Fields::BANK_RRN            => '015306767323',
            Fields::MERCHANT_TRAN_ID    => 'Havdshc12Dacftqrv2',
            Fields::PAYER_NAME          => 'Ria Garg',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],

    'testReminderCallback' => [
        'base_url'        => '/reminders/send/test/qr_code/qr_code/',
        'expected_status' => 'closed'
    ],


    'testFetchPaymentsForQrCode' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'entity'            => 'payment',
                'amount'            => 4000,
                'currency'          => 'INR',
                'status'            => 'captured',
                'order_id'          => null,
                'invoice_id'        => null,
                'method'            => 'upi',
                'amount_refunded'   => 0,
                'refund_status'     => null,
                'captured'          => true,
                'description'       => 'QRv2 Payment',
                'email'             => null,
                'contact'           => null,
                'error_code'        => null,
                'error_description' => null,
            ]
        ],
    ],

    'testFetchQrCodeByCustomerEmail' => [
        'entity' => 'collection',
        'count'  => 1,
        'items'  => [
            [
                'name'         => 'Test QR Code',
                'description'  => 'QR code for tests',
                'usage'        => 'multiple_use',
                'type'         => 'bharat_qr',
                'fixed_amount' => false,
                'customer_id'  => 'cust_100000customer',
            ],
        ],
    ],

    'tax_invoice' => [
        'number'         => 'INV001',
        'date'           => 1589994898,
        'customer_name'  => 'Abc xyz',
        'business_gstin' => '06AABCU9603R1ZR',
        'gst_amount'     => 4010,
        'cess_amount'    => 200,
        'supply_type'    => 'intrastate'
    ]
];
