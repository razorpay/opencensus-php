<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Partner\Config\Entity;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;

return [
    'testAddingConfigForNonPartner' => [
        'request' => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'      => Constants::DEFAULT_MERCHANT_ID,
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testAddingConfigWhenBothAppAndPartnerIdNotSent' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_APPLICATION_ID_OR_PARTNER_ID_MISSING,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_APPLICATION_ID_OR_PARTNER_ID_MISSING,
        ],
    ],

    'testAddingConfigWhenBothAppAndPartnerIdSent' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'      => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'application_id'  => Constants::DEFAULT_NON_PLATFORM_APP_ID
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_APPLICATION_ID_PARTNER_ID_BOTH_PRESENT,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_APPLICATION_ID_PARTNER_ID_BOTH_PRESENT,
        ],
    ],

    'testAddingConfigForNonPlatformPartnerUsingAppId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'    => 1,
                'revisit_at'             => 1648416783,
                'settle_to_partner'      => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'application',
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'commissions_enabled'    => true,
                'revisit_at'             => 1648416783,
                'settle_to_partner'      => true,
            ],
        ],
    ],

    'testAddingConfigForNonPlatformPartnerUsingPartnerId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'             => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'commissions_enabled'    => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'            => 'application',
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'    => true,
                'tds_percentage'         => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate'    => false,
            ],
        ],
    ],

    'testAddingConfigForPlatformPartnerUsingAppId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_PLATFORM_APP_ID,
                'commissions_enabled'    => 1,
                'explicit_plan_id'       => null,
                'implicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'tds_percentage'         => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate'    => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'         => 'application',
                'entity_id'           => Constants::DEFAULT_PLATFORM_APP_ID,
                'commission_model'    => 'commission',
                'commissions_enabled' => true,
                'tds_percentage'      => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate' => true,
            ],
        ],
    ],

    'testAddingInvalidConfigForPlatformPartner' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_PLATFORM_APP_ID,
                'commissions_enabled'    => 1,
                'explicit_plan_id'       => null,
                'implicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'settle_to_partner'      => 1,
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID,
        ],
    ],

    'testAddingConfigForSubvention' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_PLATFORM_APP_ID,
                'commissions_enabled'    => 1,
                'commission_model'       => 'subvention',
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'         => 'application',
                'entity_id'           => Constants::DEFAULT_PLATFORM_APP_ID,
                'commission_model'    => 'subvention',
                'commissions_enabled' => true,
            ],
        ],
    ],

    'testAddingConfigWithExpiryForSubvention' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_PLATFORM_APP_ID,
                'commissions_enabled'    => 1,
                'commission_model'       => 'subvention',
                'implicit_expiry_at'     => 1548860950,
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_EXPIRY_DATE_SET_FOR_SUBVENTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXPIRY_DATE_SET_FOR_SUBVENTION,
        ],
    ],

    'testAddingConfigWithDefaultPaymentMethods' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'         => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'          => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'     => 1,
                'commission_model'        => 'subvention',
                'default_payment_methods' => [
                    'credit_card' => true,
                    'debit_card'  => true,
                    'netbanking'  => true,
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'             => 'application',
                'entity_id'               => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commission_model'        => 'subvention',
                'commissions_enabled'     => true,
                'default_payment_methods' => [
                    'credit_card' => true,
                    'debit_card'  => true,
                    'netbanking'  => true,
                ],
            ],
        ],
    ],

    'testEditingConfigWithSettingDefaultPaymentMethodsToEmpty' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'default_plan_id'         => '10ZeroPricingP',
                'commissions_enabled'     => 0,
                'implicit_plan_id'        => null,
                'explicit_plan_id'        => null,
                'implicit_expiry_at'      => null,
                'settle_to_partner'       => 1,
                'has_gst_certificate'     => true,
                'default_payment_methods' => []
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'               => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_id'               => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'         => '10ZeroPricingP',
                'commissions_enabled'     => false,
                'implicit_plan_id'        => null,
                'explicit_plan_id'        => null,
                'implicit_expiry_at'      => null,
                'explicit_refund_fees'    => true,
                'explicit_should_charge'  => false,
                'commission_model'        => 'commission',
                'tds_percentage'          => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate'     => true,
                'default_payment_methods' => []
            ],
        ],
    ],

    'testAddingConfigWithDefaultPaymentMethodsForPurePlatform' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'         => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'          => Constants::DEFAULT_PLATFORM_APP_ID,
                'commissions_enabled'     => 1,
                'commission_model'        => 'subvention',
                'default_payment_methods' => [
                    'credit_card' => true,
                    'debit_card'  => true,
                    'netbanking'  => true,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_CONFIGURATION_INVALID,
        ],
    ],

    'testAddingConfigWithIncorrectDefaultPaymentMethods' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'         => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'          => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'     => 1,
                'commission_model'        => 'subvention',
                'default_payment_methods' => [
                    'card'       => true,
                    'debit_card' => true,
                    'netbanking' => true,
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'card is/are not required and should not be sent',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testAddingConfigForPlatformPartnerUsingPartnerId' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'             => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_ID_SENT_FOR_PURE_PLATFORM,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_ID_SENT_FOR_PURE_PLATFORM,
        ],
    ],

    'testAddingConfigForPlatFormPartnerAgain' => [
        'request' => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'             => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
        ],
    ],

    'testAddingConfigForSubmerchantAgain' => [
        'request'   => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'submerchant_id'         => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_APPLICATION_SUBMERCHANT_CONFIG_EXISTS,
        ],
    ],

    'testAddingConfigForSubMerchantUsingAppId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'application_id'         => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'submerchant_id'         => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'commissions_enabled'    => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'            => 'merchant',
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'            => 'application',
                'origin_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'    => true,
            ],
        ],
    ],

    'testAddingConfigForSubMerchantUsingPartnerId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'             => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'submerchant_id'         => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'commissions_enabled'    => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'         => 'merchant',
                'entity_id'           => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'         => 'application',
                'origin_id'           => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled' => true,
            ],
        ],
    ],

    'testAddingSubmerchantConfigWhenAppConfigAlreadyPresent' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id'        => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'             => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'submerchant_id'         => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'commissions_enabled'    => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'            => 'merchant',
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_type'            => 'application',
                'origin_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'commissions_enabled'    => true,
            ],
        ],
    ],

    'testAddingConfigForSubMerchantNotMappedToApp' => [
        'request' => [
            'url'     => '/partner_configs',
            'method'  => 'POST',
            'content' => [
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_id'      => Constants::DEFAULT_NON_PLATFORM_MERCHANT_ID,
                'submerchant_id'  => Constants::DEFAULT_SUBMERCHANT_ID,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
        ],
    ],

    'testGettingConfigUsingAppId' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'GET',
            'content' => [
                'application_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ],
        ],
        'response' => [
            'content' => [
                [
                    'entity_id'   => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                    'entity_type' => 'merchant',
                ],
                [
                    'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                    'entity_type' => 'application',
                ],
            ],
        ],
    ],

    'testGettingConfigForAppUsingSubMerchant' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'GET',
            'content' => [
                'application_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'submerchant_id' => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'application',
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'origin_id'   => null,
                'origin_type' => null,
            ],
        ],
    ],

    'testGettingOverriddenConfig' => [
        'request'  => [
            'url'     => '/partner_configs',
            'method'  => 'GET',
            'content' => [
                'application_id' => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'submerchant_id' => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
            ],
        ],
        'response' => [
            'content' => [
                'entity_type' => 'merchant',
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'origin_type' => 'application',
            ],
        ],
    ],

    'testEditingConfig' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'default_plan_id'        => '10ZeroPricingP',
                'commissions_enabled'    => 0,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'settle_to_partner'      => 1,
                'tds_percentage'         => Entity::DEFAULT_TDS_PERCENTAGE,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => '10ZeroPricingP',
                'commissions_enabled'    => false,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'explicit_refund_fees'   => true,
                'explicit_should_charge' => false,
                'commission_model'       => 'commission',
                'settle_to_partner'      => true,
                'tds_percentage'         => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate'    => false,
            ],
        ],
    ],

    'testEditingOverriddenConfig' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'default_plan_id'        => '10ZeroPricingP',
                'commissions_enabled'    => 0,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'settle_to_partner'      => 1,
                'has_gst_certificate'    => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_SUBMERCHANT_ID,
                'origin_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => '10ZeroPricingP',
                'commissions_enabled'    => false,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'explicit_refund_fees'   => true,
                'explicit_should_charge' => false,
                'commission_model'       => 'commission',
                'tds_percentage'         => Entity::DEFAULT_TDS_PERCENTAGE,
                'has_gst_certificate'    => true,
            ],
        ],
    ],

    'testEditingConfigToSubventionModel' => [
        'request'  => [
            'method'  => 'PUT',
            'content' => [
                'commission_model'       => 'subvention',
                'default_plan_id'        => '10ZeroPricingP',
                'commissions_enabled'    => 0,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'settle_to_partner'      => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'              => Constants::DEFAULT_NON_PLATFORM_APP_ID,
                'default_plan_id'        => '10ZeroPricingP',
                'commission_model'       => 'subvention',
                'commissions_enabled'    => false,
                'implicit_plan_id'       => null,
                'explicit_plan_id'       => null,
                'implicit_expiry_at'     => null,
                'explicit_refund_fees'   => true,
                'explicit_should_charge' => false,
                'settle_to_partner'      => true,
            ],
        ],
    ],

    'testGettingConfigsByPartner' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/me/partner/configs',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'commission_model'       => 'commission',
                    ]
                ],
            ],
        ],
    ],

    'testSubmerchantPricingplanUpsertViaBatch' => [
        'request'  => [
            'url'     => '/partner_configs/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'batch_action'  => 'submerchant_partner_config_upsert',
                    'entity'        => 'merchant',
                    'partner_id'    => '1000000000plat',
                    'merchant_id'   => '100submerchant',
                    'implicit_plan_id' => '10ImplicitPlan',
                    'idempotent_id' => 'random',
                ],
                [
                    'batch_action'  => 'submerchant_partner_config_upsert',
                    'entity'        => 'merchant',
                    'partner_id'    => '1000000000plat',
                    'merchant_id'   => '100submerchant',
                    'implicit_plan_id' => 'SubmerchantPln',
                    'idempotent_id' => 'random',
                ],
                [
                    'batch_action'  => 'submerchant_partner_config_upsert',
                    'entity'        => 'merchant',
                    'partner_id'    => '1000000000plat',
                    'merchant_id'   => '100submerchant',
                    'implicit_plan_id' => 'SubmerchanUPlX',
                    'idempotent_id' => 'random',
                ],
                [
                    'batch_action'  => 'submerchant_partner_config_upsert',
                    'entity'        => 'merchant',
                    'partner_id'    => '100nonplatform',
                    'merchant_id'   => '10submerchant1',
                    'implicit_plan_id' => 'SubmerchantPln',
                    'idempotent_id' => 'random',
                ],
                [
                    'batch_action'  => 'submerchant_partner_config_upsert',
                    'entity'        => 'merchant',
                    'partner_id'    => 'abcd',
                    'merchant_id'   => 'efg',
                    'implicit_plan_id' => 'SubmerchantPln',
                    'idempotent_id' => 'random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 5,
                'items'  => [
                    [
                        'batch_action'  => 'submerchant_partner_config_upsert',
                        'entity'        => 'merchant',
                        'partner_id'    => '1000000000plat',
                        'merchant_id'   => '100submerchant',
                        'idempotent_id' => 'random',
                        'status'        => 'Success'
                    ],
                    [
                        'batch_action'  => 'submerchant_partner_config_upsert',
                        'entity'        => 'merchant',
                        'partner_id'    => '1000000000plat',
                        'merchant_id'   => '100submerchant',
                        'idempotent_id' => 'random',
                        'status'        => 'Success'
                    ],
                    [
                        'batch_action'  => 'submerchant_partner_config_upsert',
                        'entity'        => 'merchant',
                        'partner_id'    => '1000000000plat',
                        'merchant_id'   => '100submerchant',
                        'implicit_plan_id' => 'SubmerchanUPlX',
                        'idempotent_id' => 'random',
                        'status'        => 'Failure',
                        'error'         => ['description' => 'The id provided does not exist',
                                            'code'        => 'BAD_REQUEST_ERROR'
                        ],
                        'http_status_code' => 400
                    ],
                    [
                        'batch_action'  => 'submerchant_partner_config_upsert',
                        'entity'        => 'merchant',
                        'partner_id'    => '100nonplatform',
                        'merchant_id'   => '10submerchant1',
                        'idempotent_id' => 'random',
                        'status'        => 'Success'
                    ],
                    [
                        'batch_action'     => 'submerchant_partner_config_upsert',
                        'entity'           => 'merchant',
                        'partner_id'       => 'abcd',
                        'merchant_id'      => 'efg',
                        'implicit_plan_id' => 'SubmerchantPln',
                        'idempotent_id'    => 'random',
                        'status'           => 'Failure',
                        'error'            => ['description' => 'The partner id does not exist or invalid',
                                               'code'        => 'BAD_REQUEST_ERROR'],
                        'http_status_code' => 400
                    ]
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testCreatePartnersSubMerchantConfigWithNullConfigInDB' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'individual'],
                'value'=> 2000011,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ],
        ],
    ],

    'testCreatePartnersSubMerchantConfigGmvLimitForNoDoc' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'gmv_limit',
                'parameters'     => ['set_for' => 'no_doc_submerchants'],
                'value'=> 5100000,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ],
        ],
    ],

    'testSetGmvLimitForNonNoDocPartnerNegative' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'gmv_limit',
                'parameters'     => ['set_for' => 'no_doc_submerchants'],
                'value'=> 2000011,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER,
        ],
    ],

    'testCreatePartnersSubMerchantConfigWithConfigInDB' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'not_yet_registered'],
                'value'=> 2000011,
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ],
        ],
    ],

    'testCreatePartnersSubMerchantConfigInvalidParameters' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'invalidType'],
                'value'=> 2000011,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
        ],
    ],

    'testCreatePartnersSubMerchantConfigInvalidConfigName' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'invalid_config',
                'parameters'     => ['business_type' => 'not_yet_registered'],
                'value'=> 2000011,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
        ],
    ],

    'testCreatePartnersSubMerchantConfigInvalidPartner' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'POST',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'not_yet_registered'],
                'value'=> 2000011,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testUpdatePartnersSubMerchantConfig' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'PUT',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'individual'],
            ],
        ],
        'response' => [
            'content' => [
                'entity_id'   => Constants::DEFAULT_NON_PLATFORM_APP_ID,
            ],
        ],
    ],

    'testUpdatePartnersSubMerchantConfigWithInvalidParameters' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'PUT',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'invalidType'],
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
        ],
    ],

    'testUpdatePartnersSubMerchantConfigWithInvalidConfigName' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'PUT',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'invalid_config_name',
                'parameters'     => ['business_type' => 'individual'],
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
        ],
    ],

    'testUpdatePartnersSubMerchantConfigWithInvalidPartnerType' => [
        'request'  => [
            'url'     => '/partner_configs/submerchant/config',
            'method'  => 'PUT',
            'content' => [
                'partner_id'     => '100nonplatform',
                'attribute_name' => 'max_payment_amount',
                'parameters'     => ['business_type' => 'individual'],
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testFetchConfigByPartner' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'partner_id'     => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'text_color'  => '000FFF',
                    'brand_name'  => 'apple'
                ],
            ],
        ],
    ],

    'testFetchConfigByPartnerWithDefaultValues' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'partner_id'     => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '528FF0',
                    'text_color'  => 'FFFFFF',
                    'brand_name'  => 'Business Partner'
                ],
            ],
        ],
    ],

    'testFetchPartnerConfigByInternalAppAuth' => [
        'request'  => [
            'url'     => '/partner_config_guest',
            'method'  => 'GET',
            'content' => [
                'partner_id'     => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'text_color'  => '000FFF',
                    'brand_name'  => 'google'
                ],
            ],
        ],
    ],

    'testFetchConfigByInvalidPartner' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'partner_id'    => 'DefaultPartner',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testUpdateAllowedConfigByPartner' => [
        'request'  => [
            'url'     => '/partner_config/{id}',
            'method'  => 'PUT',
            'content' => [
                'partner_metadata' => [
                    'brand_name' => 'samsung',
                    'brand_color' => '0000FF'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'brand_name'  => 'samsung'
                ]
            ],
        ],
    ],

    'testUpdateDisallowedConfigByPartner' => [
        'request'  => [
            'url'     => '/partner_config/{id}',
            'method'  => 'PUT',
            'content' => [
                'default_plan_id' => Pricing::DEFAULT_PRICING_PLAN_ID,
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                ],
                'commissions_enabled'     => 1,
                'implicit_plan_id'        => null,
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => "default_plan_id, commissions_enabled, implicit_plan_id is/are not required and should not be sent",
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\\Exception\\ExtraFieldsException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],

    'testUploadBrandLogoByPartner' => [
        'request'  => [
            'url'     => '/partner_config/{id}/logo',
            'method'  => 'POST',
            'files' => [

            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'text_color'  => '000FFF',
                    'brand_name'  => 'google'
                ]
            ],
        ],
    ],

    'testFetchConfigWithApplicationIdByPartner' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'application_id'     => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'text_color'  => '000FFF',
                    'brand_name'  => 'apple'
                ],
            ],
        ],
    ],

    'testFetchConfigByPartnerWithDefaultValuesAndApplicationId' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'application_id'     => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '528FF0',
                    'text_color'  => 'FFFFFF',
                    'brand_name'  => 'Business Partner'
                ],
            ],
        ],
    ],

    'testFetchPartnerConfigWithApplicationIdByInternalAppAuth' => [
        'request'  => [
            'url'     => '/partner_config_guest',
            'method'  => 'GET',
            'content' => [
                'application'  => 'DefaultPartner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_metadata' => [
                    'brand_color' => '0000FF',
                    'text_color'  => '000FFF',
                    'brand_name'  => 'google'
                ],
            ],
        ],
    ],

    'testFetchConfigWithApplicationIdByInvalidPartner' => [
        'request'  => [
            'url'     => '/partner_config',
            'method'  => 'GET',
            'content' => [
                'application_id'    => 'DefaultPartner',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],
];
