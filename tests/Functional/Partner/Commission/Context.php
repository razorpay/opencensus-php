<?php

use RZP\Tests\Functional\Fixtures\Entity\Pricing;

return [
    'testImplicitVariable' => [
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
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitVariableWithMYRCurrency' => [
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
                'country_code'    => 'MY'
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '180PartnerPlan',
            ],
            'create_payment'     => [
                'amount'    => 4000 * 100,
                'auth'      => 'partner',
                'currency'  => 'MYR',
            ],
        ],
    ],

    'testNoCommissionOnDetachedMerchant' => [
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
            'delete_submerchant_access_map' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
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

    'testImplicitForPartnerWithNoGstin' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
                'merchant_detail' => [
                    'gstin' => null,
                ],
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

    'testImplicitFixed' => [
        'setup' => [
            'create_partner'     => [
                'id'    => 'BptVjGnFv6ITBm',
                'type'  => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => 200,
                ],
                [
                    'plan_id'      => '003PartnerPlan',
                    'percent_rate' => 30,
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '003PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitFixedCommissionGreaterThanMerchantFees' => [
        'setup' => [
            'create_partner'     => [
                'id'    => 'BptVjGnFv6ITBm',
                'type'  => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => 200,
                ],
                [
                    'plan_id'      => '003PartnerPlan',
                    'percent_rate' => 300,
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '003PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitFixedCommissionIsZero' => [
        'setup' => [
            'create_partner'     => [
                'id'    => 'BptVjGnFv6ITBm',
                'type'  => 'fully_managed',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => 200,
                ],
                [
                    'plan_id'      => '003PartnerPlan',
                    'percent_rate' => 0,
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '003PartnerPlan',
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
                'type' => 'reseller',
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

    'testPartnerAsSubmerchantCommission' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'aggregator',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '200',
                ],
            ],
            'attach_partner_as_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '180PartnerPlan',
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

    'testImplicitExplicitPricingDoesNotExist' => [
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

    'testFullyManagedPartnerTypeCommission' => [
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
                'commissions_enabled' => 1,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitVariableWithSubmerchantPartnerESPricingRules' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
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
                    'id'              => 'EsAutomateRule',
                    'plan_id'         => '200MerchantPln',
                    'plan_name'       => '1.5 percent merchant plan',
                    'product'         => 'primary',
                    'feature'         => 'esautomatic',
                    'payment_method'  => 'card',
                    'percent_rate'    => 150,
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
                    'id'              => 'EsAutoRulePart',
                    'plan_id'         => '180PartnerPlan',
                    'plan_name'       => '1 percent merchant plan',
                    'product'         => 'primary',
                    'feature'         => 'esautomatic',
                    'payment_method'  => 'card',
                    'percent_rate'    => 100,
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
            'add_feature' => [
                [
                    'feature_name' => 'es_automatic',
                    'merchant_id'  => 'BptVjGnFv6ITBm',
                ],
                [
                    'feature_name' => 'es_automatic',
                    'type'         => 'submerchant',
                ],
            ]
        ],
    ],

    'testImplicitVariableWithSubmerchantPartnerDiffPricingRules' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
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
                    'id'              => 'EsAutomateRule',
                    'plan_id'         => '200MerchantPln',
                    'plan_name'       => '1.5 percent merchant plan',
                    'product'         => 'primary',
                    'feature'         => 'esautomatic',
                    'payment_method'  => 'card',
                    'percent_rate'    => 150,
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
            'add_feature' => [
                [
                    'feature_name' => 'es_automatic',
                    'merchant_id'  => 'BptVjGnFv6ITBm',
                ],
                [
                    'feature_name' => 'es_automatic',
                    'type'         => 'submerchant',
                ],
            ]
        ],
    ],

    'testImplicitVariableMultiplePricingRules' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
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

    'testZeroPartnerPricingRule' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '20MerchantPlan',
                    'percent_rate' => '20',
                ],
                [
                    'plan_id'      => 'zeroPartnerPln',
                    'percent_rate' => '0',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '20MerchantPlan',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => 'zeroPartnerPln',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testGSTOnCommissionForPaymentWithNoGST' => [
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
                'amount' => 1000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testExplicit' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testExplicitWithMYRCurrency' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'country_code'    => 'MY' // commission will get created in sub merchant base currency irrespective of payment currency
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount'    => 4000 * 100,
                'auth'      => 'partner',
                'currency'  => 'MYR'
            ],
        ],
    ],

    'testExplicitWithUSDCurrency' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount'    => 4000 * 100,
                'auth'      => 'partner',
                'currency'  => 'USD'
            ],
        ],
    ],

    'testExplicitRecordOnly' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'                   => 'partner',
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 0,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testGSTOnExplicit' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'                   => 'partner',
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 0,
            ],
            'create_payment'     => [
                'amount' => 1000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitVariableAndExplicit' => [
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
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitVariableAndExplicitForReferredApp' => [
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
                [
                    'plan_id'      => '160PartnerPlan',
                    'percent_rate' => '160',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'       => 'BptVjGnFv6ITBm',
                'submerchant_type' => 'referred',
                'pricing_plan_id'  => '200MerchantPln',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '180PartnerPlan',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'define_config_for_referred_app' => [
                'type'             => 'partner',
                'implicit_plan_id' => '160PartnerPlan',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
            ],
        ],
    ],

    'testExplicitFixedFeesType' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => 'FixedCommPlanA',
                    'percent_rate' => 0,
                    'fixed_rate'   => '800', // 8 rupees fixed amount
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => 'FixedCommPlanA',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitFixedAndExplicit' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => 'FixedCommPlanA',
                    'percent_rate' => 0,
                    'fixed_rate'   => '800',
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => 'FixedCommPlanA',
                'explicit_plan_id' => 'FixedCommPlanA',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitAndExplicitGreaterThanAmount' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => 'FixedPriPlanAB',
                    'percent_rate' => 0,
                    'fixed_rate'   => '500',
                    'type'         => 'pricing',
                ],
                [
                    'plan_id'      => 'FixedCommPlanA',
                    'percent_rate' => 0,
                    'fixed_rate'   => '300',
                    'type'         => 'commission',
                ],
                [
                    'plan_id'      => 'FixedCommPlanB',
                    'percent_rate' => 0,
                    'fixed_rate'   => '800',
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => 'FixedPriPlanAB',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => 'FixedCommPlanA',
                'explicit_plan_id' => 'FixedCommPlanB',
            ],
            'create_payment'     => [
                'amount' => 10 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
        'action' => [
            'exception' => [
                'class'   => 'RZP\Exception\LogicException',
                'message' => 'Total commission greater than txn amount',
            ],
        ],
    ],

    'testExplicitWithAddOnPricingRules' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans' => [
                [
                    'id'              => 'DefaultPlnRule',
                    'plan_id'         => 'FixedCommPlanA',
                    'product'         => 'primary',
                    'feature'         => 'payment',
                    'percent_rate'    => 20,
                    'type'            => 'commission',
                ],
                [
                    'id'              => 'RecurringRule1',
                    'plan_id'         => 'FixedCommPlanA',
                    'product'         => 'primary',
                    'feature'         => 'recurring',
                    'percent_rate'    => 20,
                    'type'            => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => 'FixedCommPlanA',
            ],
            'create_payment' => [
                'amount'    => 4000 * 100, // paise
                'auth'      => 'partner',
                'recurring' => 1,
            ],
        ],
    ],

    //
    // Customer fee bearer model creates a payment with amount inclusive of the tax.
    // The fee attribute needs to be set explicitly because while calculating the fees on the a mount,
    // the fee gets deducted from the amount if the merchant is on a customer fee bearer model.
    //
    'testImplicitCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                    'fee_bearer'   => 'customer',
                ],
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '180',
                    'fee_bearer'   => 'customer',
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
                'amount' => (4000 * 100) + (4000 * 100 * 18 / 100), // amount+fee
                'auth'   => 'partner',
                'fee'    => (4000 * 100 * 18 / 100),
            ],
        ],
    ],

    'testImplicitVariablePostpaid' => [
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

    'testImplicitFixedCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'    => 'BptVjGnFv6ITBm',
                'type'  => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => 200,
                    'fee_bearer'   => 'customer',
                ],
                [
                    'plan_id'      => '003PartnerPlan',
                    'percent_rate' => 30,
                    'type'         => 'commission',
                    'fee_bearer'   => 'customer',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
                'fee_model'       => 'postpaid',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '003PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitFixedPostpaid' => [
        'setup' => [
            'create_partner'     => [
                'id'    => 'BptVjGnFv6ITBm',
                'type'  => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => 200,
                ],
                [
                    'plan_id'      => '003PartnerPlan',
                    'percent_rate' => 30,
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => '200MerchantPln',
                'fee_model'       => 'postpaid',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => '003PartnerPlan',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testExplicitPostpaid' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'fee_model'       => 'postpaid',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testImplicitFixedAndExplicitPostpaid' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => 'FixedCommPlanA',
                    'percent_rate' => 0,
                    'fixed_rate'   => '800',
                    'type'         => 'commission',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => 'FixedCommPlanA',
                'explicit_plan_id' => 'FixedCommPlanA',
            ],
            'create_payment'     => [
                'amount' => 4000 * 100, // paise
                'auth'   => 'partner',
            ],
        ],
    ],

    'testExplicitCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'edit_plans' => [
                Pricing::DEFAULT_PRICING_PLAN_ID => [
                    'fee_bearer' => 'customer',
                ],
                Pricing::DEFAULT_COMMISSION_PLAN_ID => [
                    'fee_bearer' => 'customer',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'fee_bearer'      => 'customer',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18/100) + (4000 * 0.2) + (4000 * 0.2 * 18/100)),
                'auth'   => 'partner',
                'fee'    => ((4000 * 2) + (4000 * 2 * 18/100) + (4000 * 0.2) + (4000 * 0.2 * 18/100)),
            ],
        ],
    ],

    'testImplicitAndExplicitCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => '200MerchantPln',
                    'percent_rate' => '200',
                    'fee_bearer'   => 'customer',
                ],
                [
                    'plan_id'      => '180PartnerPlan',
                    'percent_rate' => '180',
                    'fee_bearer'   => 'customer',
                ],
            ],
            'edit_plans' => [
                Pricing::DEFAULT_COMMISSION_PLAN_ID => [
                    'fee_bearer' => 'customer',
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
                'explicit_plan_id' => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ],
            'create_payment'     => [
                'amount' => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18/100) + (4000 * 0.2) + (4000 * 0.2 * 18/100)), // amount+fee+commission+commission_tax
                'auth'   => 'partner',
                'fee'    => ((4000 * 2) + (4000 * 2 * 18/100) + (4000 * 0.2) + (4000 * 0.2 * 18/100)),
            ],
        ],
    ],

    'testImplicitAndExplicitGreaterThanAmountCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'aggregator',
            ],
            'create_plans'       => [
                [
                    'plan_id'      => 'FixedPriPlanAB',
                    'percent_rate' => 0,
                    'fixed_rate'   => '50000',
                    'type'         => 'pricing',
                    'fee_bearer'   => 'customer',
                ],
                [
                    'plan_id'      => 'FixedCommPlanA',
                    'percent_rate' => 0,
                    'fixed_rate'   => '30000',
                    'type'         => 'commission',
                    'fee_bearer'   => 'customer',
                ],
                [
                    'plan_id'      => 'FixedCommPlanB',
                    'percent_rate' => 0,
                    'fixed_rate'   => '500000',
                    'type'         => 'commission',
                    'fee_bearer'   => 'customer',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'pricing_plan_id' => 'FixedPriPlanAB',
                'fee_bearer'      => 'customer',
            ],
            'define_config'      => [
                'type'             => 'partner',
                'implicit_plan_id' => 'FixedCommPlanA',
                'explicit_plan_id' => 'FixedCommPlanB',
            ],
            'create_payment'     => [
                'amount' => (4000 + 50000 + (50000 * 18/100) + 500000 + (500000 * 18/100)) * 100, // amount+fee+commission+commission_tax
                'auth'   => 'partner',
                'fee'    => (50000 + (50000 * 18/100) + 500000 + (500000 * 18/100)) * 100,
            ],
        ],
    ],

    'testExplicitRecordOnlyCustomerFeeBearer' => [
        'setup' => [
            'create_partner'     => [
                'id'   => 'BptVjGnFv6ITBm',
                'type' => 'reseller',
            ],
            'edit_plans' => [
                Pricing::DEFAULT_COMMISSION_PLAN_ID => [
                    'fee_bearer' => 'customer',
                ],
                Pricing::DEFAULT_PRICING_PLAN_ID => [
                    'fee_bearer' => 'customer',
                ],
            ],
            'attach_submerchant' => [
                'partner_id'      => 'BptVjGnFv6ITBm',
                'fee_bearer'      => 'customer',
                'pricing_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
            'define_config'      => [
                'type'                   => 'partner',
                'explicit_plan_id'       => Pricing::DEFAULT_COMMISSION_PLAN_ID,
                'explicit_should_charge' => 0,
            ],
            'create_payment'     => [
                'amount' => (4000 * 100 + (4000 * 2) + (4000 * 2 * 18/100)), // amount+fee
                'auth'   => 'partner',
                'fee'    => ((4000 * 2) + (4000 * 2 * 18/100)),
            ],
        ],
    ],
];
