<?php

namespace RZP\Models\Merchant\Product\Config;

use RZP\Models\Merchant\Product\Util\Constants;
use RZP\Models\Merchant\Entity;

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

    const ROUTE = [];

    const PRODUCT_CONFIG_MERCHANT_FIELD_MAPPING = [
        Constants::THEME_COLOR => Entity::BRAND_COLOR
    ];

    private const PAYMENT_METHODS = [
        'netbanking' => [
            'instrument' => [
                [
                    'type' => 'retail',
                    'bank' => ['ABPB','BDBL']
                ],
                [
                    'type' => 'corporate',
                    'bank' => ['ANDB','BARB']
                ],
            ]
        ],
        'emi' => [
            'instrument' => [
                [
                'type' => 'cardless_emi',
                'partner' => ['zestmoney', 'earlysalary']
                ],
                [
                    'type' => 'card_emi',
                    'partner' => ['debit', 'credit']
                ]
            ]
        ],
        'wallet' => [
            'instrument' => [
                'amazonpay'
            ]
        ],
        'paylater' => [
            'instrument' =>[
                'simp',
                'epaylater'
            ]
        ]
    ];
}
