<?php

namespace RZP\Tests\Functional\Request;

return [

    //
    // List of public routes name and corresponding path for which we trigger the keyless test and assert
    // that keyless layer sets proper merchant id.
    //

    'public_x_entity_id_routes' => [
        [
            'name'   => 'payment_callback_post',
            'path'   => 'payments/pay_1000000payment/callback/hash',
        ],
        [
            'name'   => 'payment_callback_get',
            'path'   => 'payments/pay_1000000payment/callback/hash',
        ],
        [
            'name'   => 'payment_get_status',
            'path'   => 'payments/pay_1000000payment/status',
        ],
        [
            'name'   => 'payment_otp_submit',
            'path'   => 'payments/pay_1000000payment/otp_submit/hash',
        ],
        [
            'name'   => 'payment_otp_resend',
            'path'   => 'payments/pay_1000000payment/otp_resend',
        ],
        [
            'name'   => 'payment_topup_ajax',
            'path'   => 'payments/pay_1000000payment/topup/ajax',
        ],
        [
            'name'   => 'payment_topup_post',
            'path'   => 'payments/pay_1000000payment/topup',
        ],
        [
            'name'   => 'payment_redirect_callback',
            'path'   => 'payments/pay_1000000payment/redirect_callback',
        ],
        [
            'name'   => 'payment_cancel',
            'path'   => 'payments/pay_1000000payment/cancel',
        ],
        [
            'name'   => 'payment_add_metadata',
            'path'   => 'payments/pay_1000000payment/metadata',
        ],
        [
            'name'   => 'customer_create_token_public',
            'path'   => 'customers/cust_110000customer/tokens/public',
        ],
        [
            'name'   => 'invoice_send_notification',
            'path'   => 'invoices/inv_1000000invoice/notify/sms',
        ],
        [
            'name'   => 'invoice_get_status',
            'path'   => 'invoices/inv_1000000invoice/status',
        ],
        [
            'name'   => 'invoice_get_pdf',
            'path'   => 'invoices/inv_1000000invoice/pdf',
        ],
    ],
];
