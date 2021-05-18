<?php

namespace RZP\Models\Merchant\Product\Config;

class Defaults
{
    const PAYMENT_GATEWAY = [

        'notifications'   => [
            'sms'      => false,
            'whatsapp' => false
        ],
        'checkout'        => [
            'default_refund_speed' => 'normal'
        ]
    ];
}
