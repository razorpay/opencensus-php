<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;

class RejectionReasons
{
    const CODE                                          = 'code';
    const DESCRIPTION                                   = 'description';
    const INVALID_REJECTION_REASON_CODE                 = 'Invalid rejection reason code';

    //Reason Categories
    const RISK_RELATED_REJECTIONS                       = 'risk_related_rejections';
    const PROHIBITED_BUSINESS                           = 'prohibited_business';
    const UNREG_BLACKLIST                               = 'unreg_blacklist';
    const OPERATIONAL                                   = 'operational';
    const HIGH_RISK_BUSINESS                            = 'high_risk_business';
    const UNREG_HIGH_RISK                               = 'unreg_high_risk';

    //Reason Codes There is also 'others' reason code
    //risk_related_rejections
    const DEDUPE_BLOCKED                                = 'dedupe_blocked';
    const DEDUPE_UNDER_REVIEW                           = 'dedupe_under_review';
    const SUSPICIOUS_ONLINE_PRESENCE                    = 'suspicious_online_presence';
    const REJECT_ON_RISK_REMARKS                        = 'reject_on_risk_remarks';
    const RISK_RELATED_REJECTIONS_OTHERS                = 'risk_related_rejections_others';
    //prohibited_business
    const GET_RICH_SCHEMES                              = 'get_rich_schemes';
    const BETTING_GAMBLING_LOTTERY                      = 'betting_gambling_lottery';
    const TOBACCO_MARIJUANA_VAPES                       = 'tobacco_marijuana_vapes';
    const ARMS_AND_AMMUNITION                           = 'arms_and_ammunition';
    const ONLINE_CASINO                                 = 'online_casino';
    const MULTI_LEVEL_MARKETING                         = 'multi_level_marketing';
    const TECHNICAL_SUPPORT                             = 'technical_support';
    const AUCTIONING                                    = 'auctioning';
    const FAKE_AND_UNLICENSED_PRODUCTS                  = 'fake_and_unlicensed_products';
    const SOCIAL_MEDIA_BOOSTING                         = 'social_media_boosting';
    const GOVERNMENT_IMPERSONATOR                       = 'government_impersonator';
    const BPO_SERVICES                                  = 'bpo_services';
    const PORNOGRAPHY                                   = 'pornography';
    const CRYPTOCURRENCY                                = 'cryptocurrency';
    const SALE_OF_LIVESTOCK_AND_PETS                    = 'sale_of_livestock_and_pets';
    const HAZARDOUS_CHEMICALS                           = 'hazardous_chemicals';
    const PROHIBITED_BUSINESS_OTHERS                    = 'prohibited_business_others';
    //unreg_blacklist
    const UNREG_FINANCIAL_SERVICES                      = 'unreg_financial_services';
    const UNREG_DATING_AND_MATRIMONY                    = 'unreg_dating_and_matrimony';
    const UNREG_CROWDFUNDING                            = 'unreg_crowdfunding';
    const UNREG_STOCK_TRADING                           = 'unreg_stock_trading';
    const UNREG_ONLINE_GAMING                           = 'unreg_online_gaming';
    const UNREG_COUPONS_AND_DEALS                       = 'unreg_coupons_and_deals';
    const UNREG_SEXUAL_WELLNESS_PRODUCTS                = 'unreg_sexual_wellness_products';
    const UNREG_CRYPTO_MACHINERY                        = 'unreg_crypto_machinery';
    const UNREG_PHARMACY                                = 'unreg_pharmacy';
    const UNREG_LABORATORIES                            = 'unreg_laboratories';
    const UNREG_HIRING_SERVICES                         = 'unreg_hiring_services';
    const UNREG_AFFILIATE_MARKETING                     = 'unreg_affiliate_marketing';
    const UNREG_VIDEO_PLATFORMS                         = 'unreg_video_platforms';
    const UNREG_BLACKLIST_OTHERS                        = 'unreg_blacklist_others';
    //operational
    const FORGED_DOCUMENT                               = 'forged_document';
    const INCOMPLETE_DOCUMENT                           = 'incomplete_document';
    const DUPLICATE_OR_ERRONEOUS_CREATION               = 'duplicate_or_erroneous_creation';
    const TEST_ACCOUNT                                  = 'test_account';
    const OPERATIONAL_OTHERS                            = 'operational_others';
    //high_risk_business
    const ALCOHOLIC_BEVERAGES                           = 'alcoholic_beverages';
    const MULTIPLE_VERTICALS_HIGH_RISK                  = 'multiple_verticals_high_risk';
    const VENDOR_FULFILMENT_HIGH_RISK                   = 'vendor_fulfilment_high_risk';
    const DATING_AND_MATRIMONY_HIGH_RISK                = 'dating_and_matrimony_high_risk';
    const HIRING_SERVICES_HIGH_RISK                     = 'hiring_services_high_risk';
    const REFURBISHED_GOODS                             = 'refurbished_goods';
    const BULK_SMS_AND_DATABASES                        = 'bulk_sms_and_databases';
    const HIGH_RISK_BUSINESS_OTHERS                     = 'high_risk_business_others';
    //unreg_high_risk
    const UNREG_DONATIONS                               = 'unreg_donations';
    const UNREG_AGENCY_SERVICES                         = 'unreg_agency_services';
    const UNREG_ASTROLOGY                               = 'unreg_astrology';
    const UNREG_REAL_ESTATE                             = 'unreg_real_estate';
    const UNREG_HIGH_RISK_OTHERS                        = 'unreg_high_risk_others';
    //Reason Descriptions
    //risk_related_rejections
    const DEDUPE_BLOCKED_DESCRIPTION                    = 'Merchant is dedupe blocked';
    const DEDUPE_UNDER_REVIEW_DESCRIPTION               = 'Merchant is \'dedupe under review\'';
    const SUSPICIOUS_ONLINE_PRESENCE_DESCRIPTION        = 'Merchant has suspicious web presence';
    const REJECT_ON_RISK_REMARKS_DESCRIPTION            = 'Merchant rejected based on Risk team\'s remarks';
    const RISK_RELATED_REJECTIONS_OTHERS_DESCRIPTION    = 'Merchant rejected for OTHER risk-related reasons';
    //prohibited_business
    const GET_RICH_SCHEMES_DESCRIPTION                  = 'Merchant offers \'Earn Money Online\' or \'Get Rich\' schemes';
    const BETTING_GAMBLING_LOTTERY_DESCRIPTION          = 'Merchant business into Game of Luck (betting, gambling, lottery)';
    const TOBACCO_MARIJUANA_VAPES_DESCRIPTION           = 'Merchant sells tobacco, marijuana, vapes & other drugs';
    const ARMS_AND_AMMUNITION_DESCRIPTION               = 'Merchant sells weapons and ammunition';
    const ONLINE_CASINO_DESCRIPTION                     = 'Merchant into Online Casino business';
    const MULTI_LEVEL_MARKETING_DESCRIPTION             = 'Merchant into Multi Level Marketing (pyramid schemes)';
    const TECHNICAL_SUPPORT_DESCRIPTION                 = 'Merchant into Technical Support';
    const AUCTIONING_DESCRIPTION                        = 'Merchant into Auctioning / bidding';
    const FAKE_AND_UNLICENSED_PRODUCTS_DESCRIPTION      = 'Merchant sells fake / pirated / unlicensed products';
    const SOCIAL_MEDIA_BOOSTING_DESCRIPTION             = 'Merchant into Social Media Boosting';
    const GOVERNMENT_IMPERSONATOR_DESCRIPTION           = 'Merchant impersonates Govt. services (passport, FASTag etc.)';
    const BPO_SERVICES_DESCRIPTION                      = 'Merchant into BPO services';
    const PORNOGRAPHY_DESCRIPTION                       = 'Merchant into pornography-related business';
    const CRYPTOCURRENCY_DESCRIPTION                    = 'Merchant into cryptocurrency (non-educational)';
    const SALE_OF_LIVESTOCK_AND_PETS_DESCRIPTION        = 'Merchant sells live, endangered animals or animal parts';
    const HAZARDOUS_CHEMICALS_DESCRIPTION               = 'Merchant into hazardous chemicals (except domestic use)';
    const PROHIBITED_BUSINESS_OTHERS_DESCRIPTION        = 'Merchant into OTHER prohibited business';
    //unreg_blacklist
    const UNREG_FINANCIAL_SERVICES_DESCRIPTION          = 'Unreg merchant into financial services';
    const UNREG_DATING_AND_MATRIMONY_DESCRIPTION        = 'Unreg merchant into Dating and Matrimony services';
    const UNREG_CROWDFUNDING_DESCRIPTION                = 'Unreg merchant into Crowdfunding';
    const UNREG_STOCK_TRADING_DESCRIPTION               = 'Unreg merchant into Stock Trading (non-educational)';
    const UNREG_ONLINE_GAMING_DESCRIPTION               = 'Unreg merchant into Online Gaming';
    const UNREG_COUPONS_AND_DEALS_DESCRIPTION           = 'Unreg merchant selling Coupons, Deals, Gift Cards';
    const UNREG_SEXUAL_WELLNESS_PRODUCTS_DESCRIPTION    = 'Unreg merchant selling Sexual Wellness products';
    const UNREG_CRYPTO_MACHINERY_DESCRIPTION            = 'Unreg merchant selling Crypto Machinery';
    const UNREG_PHARMACY_DESCRIPTION                    = 'Unreg merchant into Pharmacy business';
    const UNREG_LABORATORIES_DESCRIPTION                = 'Unreg merchant into Laboratory business';
    const UNREG_HIRING_SERVICES_DESCRIPTION             = 'Unreg merchant into Hiring Services';
    const UNREG_AFFILIATE_MARKETING_DESCRIPTION         = 'Unreg merchant into Affiliate Marketing';
    const UNREG_VIDEO_PLATFORMS_DESCRIPTION             = 'Unreg merchant into OTT / Video platforms';
    const UNREG_BLACKLIST_OTHERS_DESCRIPTION            = 'Unreg merchant into OTHER Blacklisted businesses';
    //operational
    const FORGED_DOCUMENT_DESCRIPTION                   = 'Merchant submitted forged documents';
    const INCOMPLETE_DOCUMENT_DESCRIPTION               = 'Merchant submitted incomplete documents';
    const DUPLICATE_OR_ERRONEOUS_CREATION_DESCRIPTION   = 'Duplicate or Erroneous MID created';
    const TEST_ACCOUNT_DESCRIPTION                      = 'Razorpay Test account';
    const OPERATIONAL_OTHERS_DESCRIPTION                = 'Merchant rejected due to OTHER operational reasons';
    //high_risk_business
    const ALCOHOLIC_BEVERAGES_DESCRIPTION               = 'Merchant into Alcoholic Beverages';
    const MULTIPLE_VERTICALS_HIGH_RISK_DESCRIPTION      = 'Multiple Verticals - High Risk business';
    const VENDOR_FULFILMENT_HIGH_RISK_DESCRIPTION       = 'Dropshipping - High Risk business';
    const DATING_AND_MATRIMONY_HIGH_RISK_DESCRIPTION    = 'Registered merchant - Dating and Matrimony';
    const HIRING_SERVICES_HIGH_RISK_DESCRIPTION         = 'Registered merchant - Hiring services';
    const REFURBISHED_GOODS_DESCRIPTION                 = 'Merchant sells Refurbished goods';
    const BULK_SMS_AND_DATABASES_DESCRIPTION            = 'Merchant sells Bulk SMS';
    const HIGH_RISK_BUSINESS_OTHERS_DESCRIPTION         = 'Merchant into OTHER high-risk business';
    //unreg_high_risk
    const UNREG_DONATIONS_DESCRIPTION                   = 'Unregistered merchant into Charity / Donations';
    const UNREG_AGENCY_SERVICES_DESCRIPTION             = 'Unreg merchant into Agency services (insurance, travel, PAN card agent etc.)';
    const UNREG_ASTROLOGY_DESCRIPTION                   = 'Unreg merchant into Astrology';
    const UNREG_REAL_ESTATE_DESCRIPTION                 = 'Unreg merchant offers Real Estate brokerage services';
    const UNREG_HIGH_RISK_OTHERS_DESCRIPTION            = 'Unregistered merchant into OTHER high-risk business';


    // Reason codes descriptions mapping
    const REASON_CODES_DESCRIPTIONS_MAPPING = [
        //risk_related_rejections
        self::DEDUPE_BLOCKED                    => self::DEDUPE_BLOCKED_DESCRIPTION,
        self::DEDUPE_UNDER_REVIEW               => self::DEDUPE_UNDER_REVIEW_DESCRIPTION,
        self::SUSPICIOUS_ONLINE_PRESENCE        => self::SUSPICIOUS_ONLINE_PRESENCE_DESCRIPTION,
        self::REJECT_ON_RISK_REMARKS            => self::REJECT_ON_RISK_REMARKS_DESCRIPTION,
        self::RISK_RELATED_REJECTIONS_OTHERS    => self::RISK_RELATED_REJECTIONS_OTHERS_DESCRIPTION,
        //prohibited_business
        self::GET_RICH_SCHEMES                  => self::GET_RICH_SCHEMES_DESCRIPTION,
        self::BETTING_GAMBLING_LOTTERY          => self::BETTING_GAMBLING_LOTTERY_DESCRIPTION,
        self::TOBACCO_MARIJUANA_VAPES           => self::TOBACCO_MARIJUANA_VAPES_DESCRIPTION,
        self::ARMS_AND_AMMUNITION               => self::ARMS_AND_AMMUNITION_DESCRIPTION,
        self::ONLINE_CASINO                     => self::ONLINE_CASINO_DESCRIPTION,
        self::MULTI_LEVEL_MARKETING             => self::MULTI_LEVEL_MARKETING_DESCRIPTION,
        self::TECHNICAL_SUPPORT                 => self::TECHNICAL_SUPPORT_DESCRIPTION,
        self::AUCTIONING                        => self::AUCTIONING_DESCRIPTION,
        self::FAKE_AND_UNLICENSED_PRODUCTS      => self::FAKE_AND_UNLICENSED_PRODUCTS_DESCRIPTION,
        self::SOCIAL_MEDIA_BOOSTING             => self::SOCIAL_MEDIA_BOOSTING_DESCRIPTION,
        self::GOVERNMENT_IMPERSONATOR           => self::GOVERNMENT_IMPERSONATOR_DESCRIPTION,
        self::BPO_SERVICES                      => self::BPO_SERVICES_DESCRIPTION,
        self::PORNOGRAPHY                       => self::PORNOGRAPHY_DESCRIPTION,
        self::CRYPTOCURRENCY                    => self::CRYPTOCURRENCY_DESCRIPTION,
        self::SALE_OF_LIVESTOCK_AND_PETS        => self::SALE_OF_LIVESTOCK_AND_PETS_DESCRIPTION,
        self::HAZARDOUS_CHEMICALS               => self::HAZARDOUS_CHEMICALS_DESCRIPTION,
        self::PROHIBITED_BUSINESS_OTHERS        => self::PROHIBITED_BUSINESS_OTHERS_DESCRIPTION,
        //unreg_blacklist
        self::UNREG_FINANCIAL_SERVICES          => self::UNREG_FINANCIAL_SERVICES_DESCRIPTION,
        self::UNREG_DATING_AND_MATRIMONY        => self::UNREG_DATING_AND_MATRIMONY_DESCRIPTION,
        self::UNREG_CROWDFUNDING                => self::UNREG_CROWDFUNDING_DESCRIPTION,
        self::UNREG_STOCK_TRADING               => self::UNREG_STOCK_TRADING_DESCRIPTION,
        self::UNREG_ONLINE_GAMING               => self::UNREG_ONLINE_GAMING_DESCRIPTION,
        self::UNREG_COUPONS_AND_DEALS           => self::UNREG_COUPONS_AND_DEALS_DESCRIPTION,
        self::UNREG_SEXUAL_WELLNESS_PRODUCTS    => self::UNREG_SEXUAL_WELLNESS_PRODUCTS_DESCRIPTION,
        self::UNREG_CRYPTO_MACHINERY            => self::UNREG_CRYPTO_MACHINERY_DESCRIPTION,
        self::UNREG_PHARMACY                    => self::UNREG_PHARMACY_DESCRIPTION,
        self::UNREG_LABORATORIES                => self::UNREG_LABORATORIES_DESCRIPTION,
        self::UNREG_HIRING_SERVICES             => self::UNREG_HIRING_SERVICES_DESCRIPTION,
        self::UNREG_AFFILIATE_MARKETING         => self::UNREG_AFFILIATE_MARKETING_DESCRIPTION,
        self::UNREG_VIDEO_PLATFORMS             => self::UNREG_VIDEO_PLATFORMS_DESCRIPTION,
        self::UNREG_BLACKLIST_OTHERS            => self::UNREG_BLACKLIST_OTHERS_DESCRIPTION,
        //operational
        self::FORGED_DOCUMENT                   => self::FORGED_DOCUMENT_DESCRIPTION,
        self::INCOMPLETE_DOCUMENT               => self::INCOMPLETE_DOCUMENT_DESCRIPTION,
        self::DUPLICATE_OR_ERRONEOUS_CREATION   => self::DUPLICATE_OR_ERRONEOUS_CREATION_DESCRIPTION,
        self::TEST_ACCOUNT                      => self::TEST_ACCOUNT_DESCRIPTION,
        self::OPERATIONAL_OTHERS                => self::OPERATIONAL_OTHERS_DESCRIPTION,
        //high_risk_business
        self::ALCOHOLIC_BEVERAGES               => self::ALCOHOLIC_BEVERAGES_DESCRIPTION,
        self::MULTIPLE_VERTICALS_HIGH_RISK      => self::MULTIPLE_VERTICALS_HIGH_RISK_DESCRIPTION,
        self::VENDOR_FULFILMENT_HIGH_RISK       => self::VENDOR_FULFILMENT_HIGH_RISK_DESCRIPTION,
        self::DATING_AND_MATRIMONY_HIGH_RISK    => self::DATING_AND_MATRIMONY_HIGH_RISK_DESCRIPTION,
        self::HIRING_SERVICES_HIGH_RISK         => self::HIRING_SERVICES_HIGH_RISK_DESCRIPTION,
        self::REFURBISHED_GOODS                 => self::REFURBISHED_GOODS_DESCRIPTION,
        self::BULK_SMS_AND_DATABASES            => self::BULK_SMS_AND_DATABASES_DESCRIPTION,
        self::HIGH_RISK_BUSINESS_OTHERS         => self::HIGH_RISK_BUSINESS_OTHERS_DESCRIPTION,
        //unreg_high_risk
        self::UNREG_DONATIONS                   => self::UNREG_DONATIONS_DESCRIPTION,
        self::UNREG_AGENCY_SERVICES             => self::UNREG_AGENCY_SERVICES_DESCRIPTION,
        self::UNREG_ASTROLOGY                   => self::UNREG_ASTROLOGY_DESCRIPTION,
        self::UNREG_REAL_ESTATE                 => self::UNREG_REAL_ESTATE_DESCRIPTION,
        self::UNREG_HIGH_RISK_OTHERS            => self::UNREG_HIGH_RISK_OTHERS_DESCRIPTION
    ];
    // Reason codes to category mapping

    const REJECTION_REASONS_MAPPING = [
        // Unsupported Business Model
        self::RISK_RELATED_REJECTIONS => [
            [
                self::CODE          => self::DEDUPE_BLOCKED,
                self::DESCRIPTION   => self::DEDUPE_BLOCKED_DESCRIPTION
            ],
            [
                self::CODE          => self::DEDUPE_UNDER_REVIEW,
                self::DESCRIPTION   => self::DEDUPE_UNDER_REVIEW_DESCRIPTION
            ],
            [
                self::CODE          => self::SUSPICIOUS_ONLINE_PRESENCE,
                self::DESCRIPTION   => self::SUSPICIOUS_ONLINE_PRESENCE_DESCRIPTION
            ],
            [
                self::CODE          => self::REJECT_ON_RISK_REMARKS,
                self::DESCRIPTION   => self::REJECT_ON_RISK_REMARKS_DESCRIPTION
            ],
            [
                self::CODE          => self::RISK_RELATED_REJECTIONS_OTHERS,
                self::DESCRIPTION   => self::RISK_RELATED_REJECTIONS_OTHERS_DESCRIPTION
            ]
        ],
        self::PROHIBITED_BUSINESS => [
            [
                self::CODE          => self::GET_RICH_SCHEMES,
                self::DESCRIPTION   => self::GET_RICH_SCHEMES_DESCRIPTION
            ],
            [
                self::CODE          => self::BETTING_GAMBLING_LOTTERY,
                self::DESCRIPTION   => self::BETTING_GAMBLING_LOTTERY_DESCRIPTION
            ],
            [
                self::CODE          => self::TOBACCO_MARIJUANA_VAPES,
                self::DESCRIPTION   => self::TOBACCO_MARIJUANA_VAPES_DESCRIPTION
            ],
            [
                self::CODE          => self::ARMS_AND_AMMUNITION,
                self::DESCRIPTION   => self::ARMS_AND_AMMUNITION_DESCRIPTION
            ],
            [
                self::CODE          => self::ONLINE_CASINO,
                self::DESCRIPTION   => self::ONLINE_CASINO_DESCRIPTION
            ],
            [
                self::CODE          => self::MULTI_LEVEL_MARKETING,
                self::DESCRIPTION   => self::MULTI_LEVEL_MARKETING_DESCRIPTION
            ],
            [
                self::CODE          => self::TECHNICAL_SUPPORT,
                self::DESCRIPTION   => self::TECHNICAL_SUPPORT_DESCRIPTION
            ],
            [
                self::CODE          => self::AUCTIONING,
                self::DESCRIPTION   => self::AUCTIONING_DESCRIPTION
            ],
            [
                self::CODE          => self::FAKE_AND_UNLICENSED_PRODUCTS,
                self::DESCRIPTION   => self::FAKE_AND_UNLICENSED_PRODUCTS_DESCRIPTION
            ],
            [
                self::CODE          => self::SOCIAL_MEDIA_BOOSTING,
                self::DESCRIPTION   => self::SOCIAL_MEDIA_BOOSTING_DESCRIPTION
            ],
            [
                self::CODE          => self::GOVERNMENT_IMPERSONATOR,
                self::DESCRIPTION   => self::GOVERNMENT_IMPERSONATOR_DESCRIPTION
            ],
            [
                self::CODE          => self::BPO_SERVICES,
                self::DESCRIPTION   => self::BPO_SERVICES_DESCRIPTION
            ],
            [
                self::CODE          => self::PORNOGRAPHY,
                self::DESCRIPTION   => self::PORNOGRAPHY_DESCRIPTION
            ],
            [
                self::CODE          => self::CRYPTOCURRENCY,
                self::DESCRIPTION   => self::CRYPTOCURRENCY_DESCRIPTION
            ],
            [
                self::CODE          => self::SALE_OF_LIVESTOCK_AND_PETS,
                self::DESCRIPTION   => self::SALE_OF_LIVESTOCK_AND_PETS_DESCRIPTION
            ],
            [
                self::CODE          => self::HAZARDOUS_CHEMICALS,
                self::DESCRIPTION   => self::HAZARDOUS_CHEMICALS_DESCRIPTION
            ],
            [
                self::CODE          => self::PROHIBITED_BUSINESS_OTHERS,
                self::DESCRIPTION   => self::PROHIBITED_BUSINESS_OTHERS_DESCRIPTION
            ],
        ],
        self::UNREG_BLACKLIST => [
            [
                self::CODE          => self::UNREG_FINANCIAL_SERVICES,
                self::DESCRIPTION   => self::UNREG_FINANCIAL_SERVICES_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_DATING_AND_MATRIMONY,
                self::DESCRIPTION   => self::UNREG_DATING_AND_MATRIMONY_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_CROWDFUNDING,
                self::DESCRIPTION   => self::UNREG_CROWDFUNDING_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_STOCK_TRADING,
                self::DESCRIPTION   => self::UNREG_STOCK_TRADING_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_ONLINE_GAMING,
                self::DESCRIPTION   => self::UNREG_ONLINE_GAMING_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_COUPONS_AND_DEALS,
                self::DESCRIPTION   => self::UNREG_COUPONS_AND_DEALS_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_SEXUAL_WELLNESS_PRODUCTS,
                self::DESCRIPTION   => self::UNREG_SEXUAL_WELLNESS_PRODUCTS_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_CRYPTO_MACHINERY,
                self::DESCRIPTION   => self::UNREG_CRYPTO_MACHINERY_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_PHARMACY,
                self::DESCRIPTION   => self::UNREG_PHARMACY_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_LABORATORIES,
                self::DESCRIPTION   => self::UNREG_LABORATORIES_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_HIRING_SERVICES,
                self::DESCRIPTION   => self::UNREG_HIRING_SERVICES_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_AFFILIATE_MARKETING,
                self::DESCRIPTION   => self::UNREG_AFFILIATE_MARKETING_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_VIDEO_PLATFORMS,
                self::DESCRIPTION   => self::UNREG_VIDEO_PLATFORMS_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_BLACKLIST_OTHERS,
                self::DESCRIPTION   => self::UNREG_BLACKLIST_OTHERS_DESCRIPTION
            ],
        ],
        self::OPERATIONAL => [
            [
                self::CODE          => self::FORGED_DOCUMENT,
                self::DESCRIPTION   => self::FORGED_DOCUMENT_DESCRIPTION
            ],
            [
                self::CODE          => self::INCOMPLETE_DOCUMENT,
                self::DESCRIPTION   => self::INCOMPLETE_DOCUMENT_DESCRIPTION
            ],
            [
                self::CODE          => self::DUPLICATE_OR_ERRONEOUS_CREATION,
                self::DESCRIPTION   => self::DUPLICATE_OR_ERRONEOUS_CREATION_DESCRIPTION
            ],
            [
                self::CODE          => self::TEST_ACCOUNT,
                self::DESCRIPTION   => self::TEST_ACCOUNT_DESCRIPTION
            ],
            [
                self::CODE          => self::OPERATIONAL_OTHERS,
                self::DESCRIPTION   => self::OPERATIONAL_OTHERS_DESCRIPTION
            ],
        ],
        self::HIGH_RISK_BUSINESS => [
            [
                self::CODE          => self::ALCOHOLIC_BEVERAGES,
                self::DESCRIPTION   => self::ALCOHOLIC_BEVERAGES_DESCRIPTION
            ],
            [
                self::CODE          => self::MULTIPLE_VERTICALS_HIGH_RISK,
                self::DESCRIPTION   => self::MULTIPLE_VERTICALS_HIGH_RISK_DESCRIPTION
            ],
            [
                self::CODE          => self::VENDOR_FULFILMENT_HIGH_RISK,
                self::DESCRIPTION   => self::VENDOR_FULFILMENT_HIGH_RISK_DESCRIPTION
            ],
            [
                self::CODE          => self::DATING_AND_MATRIMONY_HIGH_RISK,
                self::DESCRIPTION   => self::DATING_AND_MATRIMONY_HIGH_RISK_DESCRIPTION
            ],
            [
                self::CODE          => self::HIRING_SERVICES_HIGH_RISK,
                self::DESCRIPTION   => self::HIRING_SERVICES_HIGH_RISK_DESCRIPTION
            ],
            [
                self::CODE          => self::REFURBISHED_GOODS,
                self::DESCRIPTION   => self::REFURBISHED_GOODS_DESCRIPTION
            ],
            [
                self::CODE          => self::BULK_SMS_AND_DATABASES,
                self::DESCRIPTION   => self::BULK_SMS_AND_DATABASES_DESCRIPTION
            ],
            [
                self::CODE          => self::HIGH_RISK_BUSINESS_OTHERS,
                self::DESCRIPTION   => self::HIGH_RISK_BUSINESS_OTHERS_DESCRIPTION
            ],
        ],
        self::UNREG_HIGH_RISK => [
            [
                self::CODE          => self::UNREG_DONATIONS,
                self::DESCRIPTION   => self::UNREG_DONATIONS_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_AGENCY_SERVICES,
                self::DESCRIPTION   => self::UNREG_AGENCY_SERVICES_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_ASTROLOGY,
                self::DESCRIPTION   => self::UNREG_ASTROLOGY_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_REAL_ESTATE,
                self::DESCRIPTION   => self::UNREG_REAL_ESTATE_DESCRIPTION
            ],
            [
                self::CODE          => self::UNREG_HIGH_RISK_OTHERS,
                self::DESCRIPTION   => self::UNREG_HIGH_RISK_OTHERS_DESCRIPTION
            ],
        ]
    ];

    /**
     * Given a rejection reason code, it will return the corresponding rejection reason description
     *
     * @param string $reasonCode
     *
     * @return string $reasonDescription
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function getReasonDescriptionByReasonCode(string $reasonCode): string
    {
        if (isset(self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode]) === false) {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REJECTION_REASON_CODE);
        }

        return self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode];
    }
}
