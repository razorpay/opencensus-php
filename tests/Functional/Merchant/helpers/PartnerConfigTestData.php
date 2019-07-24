<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
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
            ],
        ],
        'response' => [
            'content' => [
                'entity_type'         => 'application',
                'entity_id'           => Constants::DEFAULT_PLATFORM_APP_ID,
                'commission_model'    => 'commission',
                'commissions_enabled' => true,
            ],
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
                        'entity_id'              => '8ckeirnw84ifke',
                        'default_plan_id'        => '1hDYlICobzOCYt',
                        'commission_model'       => 'commission',
                        'commissions_enabled'    => true,
                        'implicit_plan_id'       => null,
                        'explicit_plan_id'       => null,
                        'implicit_expiry_at'     => null,
                        'explicit_refund_fees'   => false,
                        'explicit_should_charge' => false,
                    ]
                ],
            ],
        ],
    ],
];
