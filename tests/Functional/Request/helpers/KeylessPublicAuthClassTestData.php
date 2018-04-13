<?php

namespace RZP\Tests\Functional\Request;

return [

    //
    // List of public routes name and corresponding path for which we trigger the keyless test and assert
    // that keyless layer sets proper merchant id.
    //

    'public_x_entity_id_routes' => [
        [
            'name'                 => 'payment_callback_post',
            'path'                 => 'payments/pay_1000000payment/callback/hash',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_callback_get',
            'path'                 => 'payments/pay_1000000payment/callback/hash',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_get_status',
            'path'                 => 'payments/pay_1000000payment/status',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_otp_submit',
            'path'                 => 'payments/pay_1000000payment/otp_submit/hash',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_otp_resend',
            'path'                 => 'payments/pay_1000000payment/otp_resend',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_topup_ajax',
            'path'                 => 'payments/pay_1000000payment/topup/ajax',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_topup_post',
            'path'                 => 'payments/pay_1000000payment/topup',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_redirect_callback',
            'path'                 => 'payments/pay_1000000payment/redirect_callback',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_cancel',
            'path'                 => 'payments/pay_1000000payment/cancel',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'payment_add_metadata',
            'path'                 => 'payments/pay_1000000payment/metadata',
            'expected_x_entity_id' => 'pay_1000000payment',
        ],
        [
            'name'                 => 'customer_create_token_public',
            'path'                 => 'customers/cust_110000customer/tokens/public',
            'expected_x_entity_id' => 'cust_110000customer',
        ],
        [
            'name'                 => 'invoice_send_notification',
            'path'                 => 'invoices/inv_1000000invoice/notify/sms',
            'expected_x_entity_id' => 'inv_1000000invoice',
        ],
        [
            'name'                 => 'invoice_get_status',
            'path'                 => 'invoices/inv_1000000invoice/status',
            'expected_x_entity_id' => 'inv_1000000invoice',
        ],
        [
            'name'                 => 'invoice_get_pdf',
            'path'                 => 'invoices/inv_1000000invoice/pdf',
            'expected_x_entity_id' => 'inv_1000000invoice',
        ],
    ],
];
