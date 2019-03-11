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

    'testInvalidSource' => [
        'setup'  => [
            'create_transfer' => [],
        ],
        'action' => [
            'exception' => [
                'class'   => 'TypeError',
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

    'testImplicitExplicitPricingDoesNotExist' => [
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
                'explicit_plan_id' => null,
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

    'testPostpaidFeeModel' => [
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
                'fee_model'       => 'postpaid',
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

    'testCommissionDisabled' => [
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
                'type'                => 'partner',
                'implicit_plan_id'    => '180PartnerPlan',
                'commissions_enabled' => 0,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitVariableMultiplePricingRules' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'fully_managed',
            ],
            'create_plans' => [
                [
                    'id'              => 'DefaultPlnRule',
                    'plan_id'         => '200MerchantPln',
                    'plan_name'       => '2 percent merchant plan',
                    'product'         => 'primary',
                    'feature'         => 'payment',
                    'percent_rate'    => 200,
                    'international'   => 0,
                    'payment_network' => null,
                    'receiver_type'   => null,
                    'type'            => 'pricing',
                ],
                [
                    'id'              => 'RecurringRule1',
                    'plan_id'         => '200MerchantPln',
                    'plan_name'       => '2 percent merchant plan',
                    'product'         => 'primary',
                    'feature'         => 'recurring',
                    'payment_method'  => 'card',
                    'percent_rate'    => 200,
                    'international'   => 0,
                    'payment_network' => null,
                    'receiver_type'   => null,
                    'type'            => 'pricing',
                ],
                [
                    'id'              => 'PartnrRuleBase',
                    'plan_id'         => '180PartnerPlan',
                    'plan_name'       => 'Partner plan with recurring set',
                    'product'         => 'primary',
                    'feature'         => 'payment',
                    'percent_rate'    => 180,
                    'international'   => 0,
                    'payment_network' => null,
                    'receiver_type'   => null,
                    'type'            => 'pricing',
                ],
                [
                    'id'              => 'PartnrRuleRecu',
                    'plan_id'         => '180PartnerPlan',
                    'plan_name'       => 'Partner plan with recurring set',
                    'product'         => 'primary',
                    'feature'         => 'recurring',
                    'payment_method'  => 'card',
                    'percent_rate'    => 200,
                    'international'   => 0,
                    'payment_network' => null,
                    'receiver_type'   => null,
                    'type'            => 'pricing',
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
            'create_payment' => [
                'amount'    => 4000 * 100, // paise
                'auth'      => 'partner',
                'recurring' => 1,
            ],
        ],
    ],

    'testImplicitPricingExpiredNoExplicitDefined' => [
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
                'type'                => 'partner',
                'implicit_plan_id'    => '180PartnerPlan',
                'implicit_expiry_at'  => 1551169951,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitPricingExpiredExplicitExists' => [
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
                [
                    'plan_id'      => '1CommissionPln',
                    'percent_rate' => '20',
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'                => 'partner',
                'implicit_plan_id'    => '180PartnerPlan',
                'explicit_plan_id'    => '1CommissionPln',
                'implicit_expiry_at'  => 1551169951,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testPublicAuthPaymentForReseller' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
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
                'auth'   => 'public',
            ],
        ],
    ],

    'testPublicAuthPaymentForAggregator' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'aggregator',
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
                'auth'   => 'public',
            ],
        ],
    ],

    'testMissingPartnerPricingRule' => [
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
                    'feature'      => 'transfer', // no rule for payment
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
];
