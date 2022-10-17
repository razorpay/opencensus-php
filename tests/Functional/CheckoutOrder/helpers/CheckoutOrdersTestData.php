<?php
use RZP\Gateway\Upi\Icici\Fields;

return [
    'testCreateCheckoutOrder' => [
        'checkout_id' => 'KGyqftFxvElLNy',
        'closed_at' => NULL,
        'close_reason' => NULL,
        'invoice_id' => NULL,
        'order_id' => NULL,
        'status' => "active",
        'qr_code' => [
            'entity' => "qr_code",
            'name' => NULL,
            'usage' => "single_use",
            'type' => "upi_qr",
            'image_url' => NULL,
            'payment_amount' => 4000,
            'status' => "active",
            'description' => "Test Checkout Order",
            'fixed_amount' => TRUE,
            'payments_amount_received' => 0,
            'payments_count_received' => 0,
            'notes' => [
                'purpose' => "Test UPI QR code notes",
            ],
            'customer_id' => NULL,
            'tax_invoice' => [],
        ],
        'request' => [
            'method' => "GET",
        ],
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
            Fields::PAYER_NAME          => 'Batman',
            Fields::PAYER_VA            => 'random@icici',
            Fields::PAYER_AMOUNT        => '40.00',
            Fields::TXN_STATUS          => 'SUCCESS',
            Fields::TXN_INIT_DATE       => '20200601085714',
            Fields::TXN_COMPLETION_DATE => '20200601085715',
            Fields::RESPONSE_CODE       => '',
        ],
    ],
];
