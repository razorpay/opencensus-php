<?php

return [
    'BptVjGnFv6ITBm' => [
        'setup' => [
            'create_partner'      => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans'        => [
                [
                    'plan_id'           => '200MerchantPln',
                    'percent_rate' => '200',
                ],
                [
                    'plan_id'           => '180PartnerPlan',
                    'percent_rate' => '180',
                ],
            ],
            'attach_submerchant'  => [
                'partner_id' => 'BptVjGnFv6ITBm',
                'plan_id'    => '200MerchantPln',
            ],
            'define_config' => [
                'type'             => 'partner',
                'partner_id'       => 'BptVjGnFv6ITBm',
                'implicit_plan_id' => '180PartnerPlan',
            ],
            'create_payment' => [
                'auth' => 'partner',
            ],
        ],
    ],
];
