<?php

return [
    'testGetOneCcMerchantConfigsForCheckout' => [
        'request' => [
            'url' => '/checkout/1cc/merchant/configs',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'cod_intelligence' => false,
                'one_cc_auto_fetch_coupons' => true,
                'one_cc_capture_billing_address' => false,
                'one_cc_international_shipping' => false,
                'manual_control_cod_order' => false,
                'one_cc_capture_gstin' => false,
                'one_cc_capture_order_instructions' => false,
                'one_click_checkout' => true,
                'one_cc_ga_analytics' => false,
                'one_cc_fb_analytics' => false,
                'one_cc_buy_now_button' => false,
                'one_cc_gift_card' => false,
                'one_cc_gift_card_restrict_coupon' => false,
                'one_cc_buy_gift_card' => false,
                'one_cc_multiple_gift_card' => false,
                'one_cc_gift_card_cod_restrict' => false,
            ],
        ],
    ],
];
