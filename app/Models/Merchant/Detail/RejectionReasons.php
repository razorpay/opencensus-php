<?php

namespace RZP\Models\Merchant\Detail;

class RejectionReasons
{
    const CODE                                    = 'code';
    const DESCRIPTION                             = 'description';

    /*
     * Reason Categories
     */
    const UNSUPPORTED_BUSINESS_MODEL              = 'unsupported_business_model';
    const RISKY_BUSINESS                          = 'risky_business';
    const OTHERS                                  = 'others';

    /*
     * Reason Codes
     * There is also 'others' reason code
     */
    const WEB_DEVELOPMENT_OR_WEB_HOSTING           = 'web_development_or_web_hosting';
    const CHEMICAL_GOODS                           = 'chemical_goods';
    const CROWDFUNDING                             = 'crowdfunding';
    const SOCIAL_MEDIA_MARKETING                   = 'social_media_marketing';
    const SOCIAL_MEDIA_PLATFORM                    = 'social_media_platform';
    const REAL_ESTATE                              = 'real_estate';
    const INSURANCE_SERVICES                       = 'insurance_services';
    const UNREGISTERED_INDIVIDUAL                  = 'unregistered_individual';
    const NOT_REGISTERED_IN_INDIA                  = 'not_registered_in_india';
    const FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION = 'fake_products_or_unlicensed_distribution';
    const REFURBISHED_GOODS                        = 'refurbished_goods';
    const DTH_OR_MOBILE_RECHARGE                   = 'dth_or_mobile_recharge';
    const FINANCIAL_ADVISORY                       = 'financial_advisory';
    const IT_SUPPORT                               = 'it_support';
    const ONLINE_GAMES_OR_GAMBLING                 = 'online_games_or_gambling';
    const ALCOHOLIC_BEVERAGES                      = 'alcoholic_beverages';
    const GIFT_CARDS                               = 'gift_cards';
    const ASTROLOGY_SERVICES_AND_PRODUCTS          = 'astrology_services_and_products';
    const HIRING_SERVICES                          = 'hiring_services';
    const LEAD_GENERATION                          = 'lead_generation';
    const BULK_SMS_AND_DATABASE_SALE               = 'bulk_sms_and_database_sale';
    const MULTI_LEVEL_MARKETING                    = 'multi_level_marketing';
    const ONLY_DIGITAL_GOODS                       = 'only_digital_goods';
    const CREATING_ASSIGNMENTS                     = 'creating_assignments';
    const SELLING_ARMS_OR_AMMUNITION               = 'selling_arms_or_ammunition';
    const DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES = 'dating_or_matchmaking_or_escort_services';
    const MULTIPLE_VERTICALS                       = 'multiple_verticals';
    const DUPLICATE_OR_ERRENOUS_CREATION           = 'duplicate_or_errenous_creation';

    /*
     * Reason Descriptions
     */
    const WEB_DEVELOPMENT_OR_WEB_HOSTING_DESCRIPTION           = 'Proprietor/partnership into web hosting/web development';
    const CHEMICAL_GOODS_DESCRIPTION                           = 'Merchant into selling of chemical goods like firecrackers etc.';
    const CROWDFUNDING_DESCRIPTION                             = 'Merchant into crowdfunding';
    const SOCIAL_MEDIA_MARKETING_DESCRIPTION                   = 'Merchant into unsupported social media marketing activtities like paid likes/views/re-tweets, inorganic leads, inorganic website hits, contact databases etc.';
    const SOCIAL_MEDIA_PLATFORM_DESCRIPTION                    = 'Merchant with own social media platform';
    const REAL_ESTATE_DESCRIPTION                              = 'Merchant into real estate';
    const INSURANCE_SERVICES_DESCRIPTION                       = 'Merchant into insurance and related services';
    const UNREGISTERED_INDIVIDUAL_DESCRIPTION                  = 'Merchant is an unregistered individual';
    const OTHERS_DESCRIPTION                                   = 'Merchant in an unsupported business model not elsewhere classified';
    const NOT_REGISTERED_IN_INDIA_DESCRIPTION                  = 'Merchant not registered in India';
    const FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION_DESCRIPTION = 'Merchant into imitation/fake products/unlicensed distribution';
    const REFURBISHED_GOODS_DESCRIPTION                        = 'Merchant selling second hand/refurbished goods';
    const DTH_OR_MOBILE_RECHARGE_DESCRIPTION                   = 'Merchant into DTH, mobile recharges';
    const FINANCIAL_ADVISORY_DESCRIPTION                       = 'Merchant into financial/investment advisory';
    const IT_SUPPORT_DESCRIPTION                               = 'Merchant into IT support services';
    const ONLINE_GAMES_OR_GAMBLING_DESCRIPTION                 = 'Merchant into online games/gambling/fantasy leagues etc.';
    const ALCOHOLIC_BEVERAGES_DESCRIPTION                      = 'Merchant into selling alcoholic beverages';
    const GIFT_CARDS_DESCRIPTION                               = 'Merchant into gift cards/gift vouchers';
    const ASTROLOGY_SERVICES_AND_PRODUCTS_DESCRIPTION          = 'Merchant into astrology services and products';
    const HIRING_SERVICES_DESCRIPTION                          = 'Merchant into jobs/hiring services';
    const LEAD_GENERATION_DESCRIPTION                          = 'Merchant into lead generation services';
    const BULK_SMS_AND_DATABASE_SALE_DESCRIPTION               = 'Merchant providing bulk sms services, comprising database sale';
    const MULTI_LEVEL_MARKETING_DESCRIPTION                    = 'Merchant into multi-level marketing';
    const ONLY_DIGITAL_GOODS_DESCRIPTION                       = 'Merchant exclusively selling digital goods like e-books, podcasts etc.';
    const CREATING_ASSIGNMENTS_DESCRIPTION                     = 'Merchant into creating projects/assignments etc.';
    const SELLING_ARMS_OR_AMMUNITION_DESCRIPTION               = 'Merchant selling arms, ammunitions like firearms, air guns etc.';
    const DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES_DESCRIPTION = 'Merchant into dating/matchmaking/escort services';
    const MULTIPLE_VERTICALS_DESCRIPTION                       = 'Merchant into multiple verticals';
    const DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION           = 'Archived due to duplicate account/errenous creation';

    // Allowed Reason Codes
    const ALLOWED_REASON_CODES = [
        self::WEB_DEVELOPMENT_OR_WEB_HOSTING,
        self::CHEMICAL_GOODS,
        self::CROWDFUNDING,
        self::SOCIAL_MEDIA_MARKETING,
        self::SOCIAL_MEDIA_PLATFORM,
        self::REAL_ESTATE,
        self::INSURANCE_SERVICES,
        self::UNREGISTERED_INDIVIDUAL,
        self::OTHERS,
        self::NOT_REGISTERED_IN_INDIA,
        self::FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION,
        self::REFURBISHED_GOODS,
        self::DTH_OR_MOBILE_RECHARGE,
        self::FINANCIAL_ADVISORY,
        self::IT_SUPPORT,
        self::ONLINE_GAMES_OR_GAMBLING,
        self::ALCOHOLIC_BEVERAGES,
        self::GIFT_CARDS,
        self::ASTROLOGY_SERVICES_AND_PRODUCTS,
        self::HIRING_SERVICES,
        self::LEAD_GENERATION,
        self::BULK_SMS_AND_DATABASE_SALE,
        self::MULTI_LEVEL_MARKETING,
        self::ONLY_DIGITAL_GOODS,
        self::CREATING_ASSIGNMENTS,
        self::SELLING_ARMS_OR_AMMUNITION,
        self::DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES,
        self::MULTIPLE_VERTICALS,
        self::DUPLICATE_OR_ERRENOUS_CREATION,
    ];

    const REJECTION_REASONS_MAPPING = [
        // Unsupported Business Model
        self::UNSUPPORTED_BUSINESS_MODEL => [
            [
                self::CODE        => self::WEB_DEVELOPMENT_OR_WEB_HOSTING,
                self::DESCRIPTION => self::WEB_DEVELOPMENT_OR_WEB_HOSTING_DESCRIPTION,
            ],
            [
                self::CODE        => self::CHEMICAL_GOODS,
                self::DESCRIPTION => self::CHEMICAL_GOODS_DESCRIPTION,
            ],
            [
                self::CODE        => self::CROWDFUNDING,
                self::DESCRIPTION => self::CROWDFUNDING_DESCRIPTION,
            ],
            [
                self::CODE        => self::SOCIAL_MEDIA_MARKETING,
                self::DESCRIPTION => self::SOCIAL_MEDIA_MARKETING_DESCRIPTION,
            ],
            [
                self::CODE        => self::SOCIAL_MEDIA_PLATFORM,
                self::DESCRIPTION => self::SOCIAL_MEDIA_PLATFORM_DESCRIPTION,
            ],
            [
                self::CODE        => self::REAL_ESTATE,
                self::DESCRIPTION => self::REAL_ESTATE_DESCRIPTION,
            ],
            [
                self::CODE        => self::INSURANCE_SERVICES,
                self::DESCRIPTION => self::INSURANCE_SERVICES_DESCRIPTION,
            ],
            [
                self::CODE        => self::UNREGISTERED_INDIVIDUAL,
                self::DESCRIPTION => self::UNREGISTERED_INDIVIDUAL_DESCRIPTION,
            ],
            [
                self::CODE        => self::OTHERS,
                self::DESCRIPTION => self::OTHERS_DESCRIPTION,
            ],
            [
                self::CODE        => self::NOT_REGISTERED_IN_INDIA,
                self::DESCRIPTION => self::NOT_REGISTERED_IN_INDIA_DESCRIPTION,
            ],
        ],

        // Risky Business
        self::RISKY_BUSINESS => [
            [
                self::CODE        => self::FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION,
                self::DESCRIPTION => self::FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION_DESCRIPTION,
            ],
            [
                self::CODE        => self::REFURBISHED_GOODS,
                self::DESCRIPTION => self::REFURBISHED_GOODS_DESCRIPTION,
            ],
            [
                self::CODE        => self::DTH_OR_MOBILE_RECHARGE,
                self::DESCRIPTION => self::DTH_OR_MOBILE_RECHARGE_DESCRIPTION,
            ],
            [
                self::CODE        => self::FINANCIAL_ADVISORY,
                self::DESCRIPTION => self::FINANCIAL_ADVISORY_DESCRIPTION,
            ],
            [
                self::CODE        => self::IT_SUPPORT,
                self::DESCRIPTION => self::IT_SUPPORT_DESCRIPTION,
            ],
            [
                self::CODE        => self::ONLINE_GAMES_OR_GAMBLING,
                self::DESCRIPTION => self::ONLINE_GAMES_OR_GAMBLING_DESCRIPTION,
            ],
            [
                self::CODE        => self::ALCOHOLIC_BEVERAGES,
                self::DESCRIPTION => self::ALCOHOLIC_BEVERAGES_DESCRIPTION,
            ],
            [
                self::CODE        => self::GIFT_CARDS,
                self::DESCRIPTION => self::GIFT_CARDS_DESCRIPTION,
            ],
            [
                self::CODE        => self::ASTROLOGY_SERVICES_AND_PRODUCTS,
                self::DESCRIPTION => self::ASTROLOGY_SERVICES_AND_PRODUCTS_DESCRIPTION,
            ],
            [
                self::CODE        => self::HIRING_SERVICES,
                self::DESCRIPTION => self::HIRING_SERVICES_DESCRIPTION,
            ],
            [
                self::CODE        => self::LEAD_GENERATION,
                self::DESCRIPTION => self::LEAD_GENERATION_DESCRIPTION,
            ],
            [
                self::CODE        => self::BULK_SMS_AND_DATABASE_SALE,
                self::DESCRIPTION => self::BULK_SMS_AND_DATABASE_SALE_DESCRIPTION,
            ],
            [
                self::CODE        => self::MULTI_LEVEL_MARKETING,
                self::DESCRIPTION => self::MULTI_LEVEL_MARKETING_DESCRIPTION,
            ],
            [
                self::CODE        => self::ONLY_DIGITAL_GOODS,
                self::DESCRIPTION => self::ONLY_DIGITAL_GOODS_DESCRIPTION,
            ],
            [
                self::CODE        => self::CREATING_ASSIGNMENTS,
                self::DESCRIPTION => self::CREATING_ASSIGNMENTS_DESCRIPTION,
            ],
            [
                self::CODE        => self::SELLING_ARMS_OR_AMMUNITION,
                self::DESCRIPTION => self::SELLING_ARMS_OR_AMMUNITION_DESCRIPTION,
            ],
            [
                self::CODE        => self::DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES,
                self::DESCRIPTION => self::DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES_DESCRIPTION,
            ],
            [
                self::CODE        => self::MULTIPLE_VERTICALS,
                self::DESCRIPTION => self::MULTIPLE_VERTICALS_DESCRIPTION,
            ],
        ],

        // Others
        self::OTHERS => [
            [
                self::CODE        => self::DUPLICATE_OR_ERRENOUS_CREATION,
                self::DESCRIPTION => self::DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION,
            ],
        ],
    ];
}
