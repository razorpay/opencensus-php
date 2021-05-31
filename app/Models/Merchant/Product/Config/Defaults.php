<?php

namespace RZP\Models\Merchant\Product\Config;

class Defaults
{
    const PAYMENT_GATEWAY = [

        'notifications' => [
            'sms'      => false,
            'whatsapp' => false
        ],
        'refund'        => [
            'default_refund_speed' => 'normal'
        ],
        'checkout' => [
            'theme_color' => '#FFFFFF'
        ]
    ];
}
