<?php

return [
    'testImplicitVariable' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                ],
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '180',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '180PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testPartnerDoesNotExist' => [
        'setup' => [
            'create_plans'   => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                ],
            ],
            'create_payment' => [
                'merchant_id' => '10000000000000',
                'amount'      => 4000 * 100, // paise
            ],
        ],
    ],

    'testPartnerConfigDoesNotExist' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testInvalidSource' => [
        'setup' => [
            'create_transfer' => [],
        ],
    ],

    'testImplicitPricingDoesNotExist' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                ],
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '180',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => null,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                ],
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '180',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
                'fee_bearer'      => 'customer',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '180PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],
];
