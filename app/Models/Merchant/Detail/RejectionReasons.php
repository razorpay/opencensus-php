<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;

class RejectionReasons
{
    const CODE                                          = 'code';
    const DESCRIPTION                                   = 'description';
    const INVALID_REJECTION_REASON_CODE                 = 'Invalid rejection reason code';

    //Reason Categories
    //old
    const UNSUPPORTED_BUSINESS_MODEL                    = 'unsupported_business_model';
    const RISKY_BUSINESS                                = 'risky_business';
    const OTHERS                                        = 'others';
    const PROHIBITED_BUSINESSES                         = 'prohibited_businesses';
    //new
    const RISK_RELATED_REJECTIONS                       = 'risk_related_rejections';
    const PROHIBITED_BUSINESS                           = 'prohibited_business';
    const UNREG_BLACKLIST                               = 'unreg_blacklist';
    const OPERATIONAL                                   = 'operational';
    const HIGH_RISK_BUSINESS                            = 'high_risk_business';
    const UNREG_HIGH_RISK                               = 'unreg_high_risk';

    /*
    * Reason Codes
    * There is also 'others' reason code
    */
    //old
    const WEB_DEVELOPMENT_OR_WEB_HOSTING                = 'web_development_or_web_hosting';
    const CHEMICAL_GOODS                                = 'chemical_goods';
    const CROWDFUNDING                                  = 'crowdfunding';
    const SOCIAL_MEDIA_MARKETING                        = 'social_media_marketing';
    const SOCIAL_MEDIA_PLATFORM                         = 'social_media_platform';
    const REAL_ESTATE                                   = 'real_estate';
    const INSURANCE_SERVICES                            = 'insurance_services';
    const UNREGISTERED_INDIVIDUAL                       = 'unregistered_individual';
    const NOT_REGISTERED_IN_INDIA                       = 'not_registered_in_india';
    const FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION      = 'fake_products_or_unlicensed_distribution';
    const DTH_OR_MOBILE_RECHARGE                        = 'dth_or_mobile_recharge';
    const FINANCIAL_ADVISORY                            = 'financial_advisory';
    const IT_SUPPORT                                    = 'it_support';
    const ONLINE_GAMES_OR_GAMBLING                      = 'online_games_or_gambling';
    const GIFT_CARDS                                    = 'gift_cards';
    const ASTROLOGY_SERVICES_AND_PRODUCTS               = 'astrology_services_and_products';
    const HIRING_SERVICES                               = 'hiring_services';
    const LEAD_GENERATION                               = 'lead_generation';
    const BULK_SMS_AND_DATABASE_SALE                    = 'bulk_sms_and_database_sale';
    const ONLY_DIGITAL_GOODS                            = 'only_digital_goods';
    const CREATING_ASSIGNMENTS                          = 'creating_assignments';
    const SELLING_ARMS_OR_AMMUNITION                    = 'selling_arms_or_ammunition';
    const DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES      = 'dating_or_matchmaking_or_escort_services';
    const MULTIPLE_VERTICALS                            = 'multiple_verticals';
    const DUPLICATE_OR_ERRENOUS_CREATION                = 'duplicate_or_errenous_creation';
    const EARN_MONEY_ONLINE                             = 'earn_money_online';
    const IMPROPER_DOCUMENTATION                        = 'improper_documentation';
    const BETTING                                       = 'betting';
    const SALE_OF_LIVESTOCK_PETS                        = 'sale_of_livestock_or_pets';
    const STOCK_TRADING_TIPS                            = 'stock_trading_tips';
    const DROPSHIPPING                                  = 'dropshipping';
    const DRUG_STORE_PHARMACY                           = 'drug_store_pharmacy';
    const SEXUAL_WELLNESS_AND_ADULT_TOYS                = 'sexual_wellness_and_adult_toys';
    const CHIT_FUNDS                                    = 'chit_funds';
    const TOBACCO_OR_MARHUANA                           = 'tobacco_or_marijuana';
    const HOTEL_BOOKING                                 = 'hotel_booking';
    const VAPES_AND_E_CIGARETTES                        = 'vapes_and_e_cigarettes';
    const TELEMARKETING_SERVICES                        = 'telemarketing_services';
    const HAZARDOUS_CHEMICAL                            = 'hazardous_chemicals';
    //new
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

    /*
 * Reason Descriptions
 */
    //old
    const WEB_DEVELOPMENT_OR_WEB_HOSTING_DESCRIPTION            = 'Proprietor/partnership into web hosting/web development';
    const CHEMICAL_GOODS_DESCRIPTION                            = 'Merchant into selling of chemical goods like firecrackers etc.';
    const CROWDFUNDING_DESCRIPTION                              = 'Merchant into crowdfunding';
    const SOCIAL_MEDIA_MARKETING_DESCRIPTION                    = 'Merchant into unsupported social media marketing activtities like paid likes/views/re-tweets, inorganic leads, inorganic website hits, contact databases etc.';
    const SOCIAL_MEDIA_PLATFORM_DESCRIPTION                     = 'Merchant with own social media platform';
    const REAL_ESTATE_DESCRIPTION                               = 'Merchant into real estate';
    const INSURANCE_SERVICES_DESCRIPTION                        = 'Merchant into insurance and related services without certification';
    const UNREGISTERED_INDIVIDUAL_DESCRIPTION                   = 'Merchant is an unregistered individual';
    const OTHERS_DESCRIPTION                                    = 'Merchant in an unsupported business model not elsewhere classified';
    const NOT_REGISTERED_IN_INDIA_DESCRIPTION                   = 'Merchant not registered in India';
    const FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION_DESCRIPTION  = 'Merchant into imitation/fake products/unlicensed distribution';
    const DTH_OR_MOBILE_RECHARGE_DESCRIPTION                    = 'Merchant into DTH, mobile recharges';
    const FINANCIAL_ADVISORY_DESCRIPTION                        = 'Merchant into financial/investment advisory without SEBI';
    const IT_SUPPORT_DESCRIPTION                                = 'Merchant into IT support services';
    const ONLINE_GAMES_OR_GAMBLING_DESCRIPTION                  = 'Merchant into online games/gambling/fantasy leagues etc.';
    const GIFT_CARDS_DESCRIPTION                                = 'Merchant into gift cards/gift vouchers';
    const ASTROLOGY_SERVICES_AND_PRODUCTS_DESCRIPTION           = 'Merchant into astrology services and products';
    const HIRING_SERVICES_DESCRIPTION                           = 'Merchant into jobs/hiring services';
    const LEAD_GENERATION_DESCRIPTION                           = 'Merchant into lead generation services';
    const BULK_SMS_AND_DATABASE_SALE_DESCRIPTION                = 'Merchant providing bulk sms services, comprising database sale';
    const ONLY_DIGITAL_GOODS_DESCRIPTION                        = 'Merchant exclusively selling digital goods like e-books, podcasts etc.';
    const CREATING_ASSIGNMENTS_DESCRIPTION                      = 'Merchant into creating projects/assignments etc.';
    const SELLING_ARMS_OR_AMMUNITION_DESCRIPTION                = 'Merchant selling arms, ammunitions like firearms, air guns etc.';
    const DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES_DESCRIPTION  = 'Merchant into dating/matchmaking/escort services';
    const MULTIPLE_VERTICALS_DESCRIPTION                        = 'Merchant into multiple verticals';
    const DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION            = 'Archived due to duplicate account/errenous creation';
    const EARN_MONEY_ONLINE_DESCRIPTION                         = 'Merchant into earn money online business';
    const IMPROPER_DOCUMENTATION_DESCRIPTION                    = 'Merchants having improper documentation';
    const BETTING_DESCRIPTION                                   = 'Merchant into betting';
    const SALE_OF_LIVESTOCK_PETS_DESCRIPTION                    = 'Merchant into sale of livestock/pets';
    const STOCK_TRADING_TIPS_DESCRIPTION                        = 'Merchant into stock trading tips providing service';
    const DROPSHIPPING_DESCRIPTION                              = 'Dropshipping';
    const DRUG_STORE_PHARMACY_DESCRIPTION                       = 'Merchant into drug store pharmacy';
    const SEXUAL_WELLNESS_AND_ADULT_TOYS_DESCRIPTION            = 'Merchant into Sexual Wellness and Adult toys';
    const CHIT_FUNDS_DESCRIPTION                                = 'Merchant into chit funds';
    const TOBACCO_OR_MARHUANA_DESCRIPTION                       = 'Merchant into Tobacco/Marijuana';
    const HOTEL_BOOKING_DESCRIPTION                             = 'Merchant into Hotel booking';
    const VAPES_AND_E_CIGARETTES_DESCRIPTION                    = 'Merchant into Vapes and E-cigarettes';
    const TELEMARKETING_SERVICES_DESCRIPTION                    = 'Merchants into Telemarketing services';
    const HAZARDOUS_CHEMICAL_DESCRIPTION                        = 'Merchant into and hazardous chemicals';
    //new
    //risk_related_rejections
    const DEDUPE_BLOCKED_DESCRIPTION                            = 'Merchant is dedupe blocked';
    const DEDUPE_UNDER_REVIEW_DESCRIPTION                       = 'Merchant is \'dedupe under review\'';
    const SUSPICIOUS_ONLINE_PRESENCE_DESCRIPTION                = 'Merchant has suspicious web presence';
    const REJECT_ON_RISK_REMARKS_DESCRIPTION                    = 'Merchant rejected based on Risk team\'s remarks';
    const RISK_RELATED_REJECTIONS_OTHERS_DESCRIPTION            = 'Merchant rejected for OTHER risk-related reasons';
    //prohibited_business
    const GET_RICH_SCHEMES_DESCRIPTION                          = 'Merchant offers \'Earn Money Online\' or \'Get Rich\' schemes';
    const BETTING_GAMBLING_LOTTERY_DESCRIPTION                  = 'Merchant business into Game of Luck (betting, gambling, lottery)';
    const TOBACCO_MARIJUANA_VAPES_DESCRIPTION                   = 'Merchant sells tobacco, marijuana, vapes & other drugs';
    const ARMS_AND_AMMUNITION_DESCRIPTION                       = 'Merchant sells weapons and ammunition';
    const ONLINE_CASINO_DESCRIPTION                             = 'Merchant into Online Casino business';
    const MULTI_LEVEL_MARKETING_DESCRIPTION                     = 'Merchant into Multi Level Marketing (pyramid schemes)';
    const TECHNICAL_SUPPORT_DESCRIPTION                         = 'Merchant into Technical Support';
    const AUCTIONING_DESCRIPTION                                = 'Merchant into Auctioning / bidding';
    const FAKE_AND_UNLICENSED_PRODUCTS_DESCRIPTION              = 'Merchant sells fake / pirated / unlicensed products';
    const SOCIAL_MEDIA_BOOSTING_DESCRIPTION                     = 'Merchant into Social Media Boosting';
    const GOVERNMENT_IMPERSONATOR_DESCRIPTION                   = 'Merchant impersonates Govt. services (passport, FASTag etc.)';
    const BPO_SERVICES_DESCRIPTION                              = 'Merchant into BPO services';
    const PORNOGRAPHY_DESCRIPTION                               = 'Merchant into pornography-related business';
    const CRYPTOCURRENCY_DESCRIPTION                            = 'Merchant into cryptocurrency (non-educational)';
    const SALE_OF_LIVESTOCK_AND_PETS_DESCRIPTION                = 'Merchant sells live, endangered animals or animal parts';
    const HAZARDOUS_CHEMICALS_DESCRIPTION                       = 'Merchant into hazardous chemicals (except domestic use)';
    const PROHIBITED_BUSINESS_OTHERS_DESCRIPTION                = 'Merchant into OTHER prohibited business';
    //unreg_blacklist
    const UNREG_FINANCIAL_SERVICES_DESCRIPTION                  = 'Unreg merchant into financial services';
    const UNREG_DATING_AND_MATRIMONY_DESCRIPTION                = 'Unreg merchant into Dating and Matrimony services';
    const UNREG_CROWDFUNDING_DESCRIPTION                        = 'Unreg merchant into Crowdfunding';
    const UNREG_STOCK_TRADING_DESCRIPTION                       = 'Unreg merchant into Stock Trading (non-educational)';
    const UNREG_ONLINE_GAMING_DESCRIPTION                       = 'Unreg merchant into Online Gaming';
    const UNREG_COUPONS_AND_DEALS_DESCRIPTION                   = 'Unreg merchant selling Coupons, Deals, Gift Cards';
    const UNREG_SEXUAL_WELLNESS_PRODUCTS_DESCRIPTION            = 'Unreg merchant selling Sexual Wellness products';
    const UNREG_CRYPTO_MACHINERY_DESCRIPTION                    = 'Unreg merchant selling Crypto Machinery';
    const UNREG_PHARMACY_DESCRIPTION                            = 'Unreg merchant into Pharmacy business';
    const UNREG_LABORATORIES_DESCRIPTION                        = 'Unreg merchant into Laboratory business';
    const UNREG_HIRING_SERVICES_DESCRIPTION                     = 'Unreg merchant into Hiring Services';
    const UNREG_AFFILIATE_MARKETING_DESCRIPTION                 = 'Unreg merchant into Affiliate Marketing';
    const UNREG_VIDEO_PLATFORMS_DESCRIPTION                     = 'Unreg merchant into OTT / Video platforms';
    const UNREG_BLACKLIST_OTHERS_DESCRIPTION                    = 'Unreg merchant into OTHER Blacklisted businesses';
    //operational
    const FORGED_DOCUMENT_DESCRIPTION                           = 'Merchant submitted forged documents';
    const INCOMPLETE_DOCUMENT_DESCRIPTION                       = 'Merchant submitted incomplete documents';
    const DUPLICATE_OR_ERRONEOUS_CREATION_DESCRIPTION           = 'Duplicate or Erroneous MID created';
    const TEST_ACCOUNT_DESCRIPTION                              = 'Razorpay Test account';
    const OPERATIONAL_OTHERS_DESCRIPTION                        = 'Merchant rejected due to OTHER operational reasons';
    //high_risk_business
    const ALCOHOLIC_BEVERAGES_DESCRIPTION                       = 'Merchant into Alcoholic Beverages';
    const MULTIPLE_VERTICALS_HIGH_RISK_DESCRIPTION              = 'Multiple Verticals - High Risk business';
    const VENDOR_FULFILMENT_HIGH_RISK_DESCRIPTION               = 'Dropshipping - High Risk business';
    const DATING_AND_MATRIMONY_HIGH_RISK_DESCRIPTION            = 'Registered merchant - Dating and Matrimony';
    const HIRING_SERVICES_HIGH_RISK_DESCRIPTION                 = 'Registered merchant - Hiring services';
    const REFURBISHED_GOODS_DESCRIPTION                         = 'Merchant sells Refurbished goods';
    const BULK_SMS_AND_DATABASES_DESCRIPTION                    = 'Merchant sells Bulk SMS';
    const HIGH_RISK_BUSINESS_OTHERS_DESCRIPTION                 = 'Merchant into OTHER high-risk business';
    //unreg_high_risk
    const UNREG_DONATIONS_DESCRIPTION                           = 'Unregistered merchant into Charity / Donations';
    const UNREG_AGENCY_SERVICES_DESCRIPTION                     = 'Unreg merchant into Agency services (insurance, travel, PAN card agent etc.)';
    const UNREG_ASTROLOGY_DESCRIPTION                           = 'Unreg merchant into Astrology';
    const UNREG_REAL_ESTATE_DESCRIPTION                         = 'Unreg merchant offers Real Estate brokerage services';
    const UNREG_HIGH_RISK_OTHERS_DESCRIPTION                    = 'Unregistered merchant into OTHER high-risk business';


    // Reason codes descriptions mapping
    const REASON_CODES_DESCRIPTIONS_MAPPING = [
        //old
        self::DROPSHIPPING                             => self::DROPSHIPPING_DESCRIPTION,
        self::SEXUAL_WELLNESS_AND_ADULT_TOYS           => self::SEXUAL_WELLNESS_AND_ADULT_TOYS_DESCRIPTION,
        self::DRUG_STORE_PHARMACY                      => self::DRUG_STORE_PHARMACY_DESCRIPTION,
        self::CHIT_FUNDS                               => self::CHIT_FUNDS_DESCRIPTION,
        self::TOBACCO_OR_MARHUANA                      => self::TOBACCO_OR_MARHUANA_DESCRIPTION,
        self::HOTEL_BOOKING                            => self::HOTEL_BOOKING_DESCRIPTION,
        self::VAPES_AND_E_CIGARETTES                   => self::VAPES_AND_E_CIGARETTES_DESCRIPTION,
        self::TELEMARKETING_SERVICES                   => self::TELEMARKETING_SERVICES_DESCRIPTION,
        self::WEB_DEVELOPMENT_OR_WEB_HOSTING           => self::WEB_DEVELOPMENT_OR_WEB_HOSTING_DESCRIPTION,
        self::CHEMICAL_GOODS                           => self::CHEMICAL_GOODS_DESCRIPTION,
        self::CROWDFUNDING                             => self::CROWDFUNDING_DESCRIPTION,
        self::SOCIAL_MEDIA_MARKETING                   => self::SOCIAL_MEDIA_MARKETING_DESCRIPTION,
        self::SOCIAL_MEDIA_PLATFORM                    => self::SOCIAL_MEDIA_PLATFORM_DESCRIPTION,
        self::REAL_ESTATE                              => self::REAL_ESTATE_DESCRIPTION,
        self::INSURANCE_SERVICES                       => self::INSURANCE_SERVICES_DESCRIPTION,
        self::UNREGISTERED_INDIVIDUAL                  => self::UNREGISTERED_INDIVIDUAL_DESCRIPTION,
        self::OTHERS                                   => self::OTHERS_DESCRIPTION,
        self::NOT_REGISTERED_IN_INDIA                  => self::NOT_REGISTERED_IN_INDIA_DESCRIPTION,
        self::FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION => self::FAKE_PRODUCTS_OR_UNLICENSED_DISTRIBUTION_DESCRIPTION,
        self::DTH_OR_MOBILE_RECHARGE                   => self::DTH_OR_MOBILE_RECHARGE_DESCRIPTION,
        self::FINANCIAL_ADVISORY                       => self::FINANCIAL_ADVISORY_DESCRIPTION,
        self::IT_SUPPORT                               => self::IT_SUPPORT_DESCRIPTION,
        self::ONLINE_GAMES_OR_GAMBLING                 => self::ONLINE_GAMES_OR_GAMBLING_DESCRIPTION,
        self::GIFT_CARDS                               => self::GIFT_CARDS_DESCRIPTION,
        self::ASTROLOGY_SERVICES_AND_PRODUCTS          => self::ASTROLOGY_SERVICES_AND_PRODUCTS_DESCRIPTION,
        self::HIRING_SERVICES                          => self::HIRING_SERVICES_DESCRIPTION,
        self::LEAD_GENERATION                          => self::LEAD_GENERATION_DESCRIPTION,
        self::BULK_SMS_AND_DATABASE_SALE               => self::BULK_SMS_AND_DATABASE_SALE_DESCRIPTION,
        self::ONLY_DIGITAL_GOODS                       => self::ONLY_DIGITAL_GOODS_DESCRIPTION,
        self::CREATING_ASSIGNMENTS                     => self::CREATING_ASSIGNMENTS_DESCRIPTION,
        self::SELLING_ARMS_OR_AMMUNITION               => self::SELLING_ARMS_OR_AMMUNITION_DESCRIPTION,
        self::DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES => self::DATING_OR_MATCHMAKING_OR_ESCORT_SERVICES_DESCRIPTION,
        self::MULTIPLE_VERTICALS                       => self::MULTIPLE_VERTICALS_DESCRIPTION,
        self::DUPLICATE_OR_ERRENOUS_CREATION           => self::DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION,
        self::EARN_MONEY_ONLINE                        => self::EARN_MONEY_ONLINE_DESCRIPTION,
        self::IMPROPER_DOCUMENTATION                   => self::IMPROPER_DOCUMENTATION_DESCRIPTION,
        self::BETTING                                  => self::BETTING_DESCRIPTION,
        self::SALE_OF_LIVESTOCK_PETS                   => self::SALE_OF_LIVESTOCK_PETS_DESCRIPTION,
        self::STOCK_TRADING_TIPS                       => self::STOCK_TRADING_TIPS_DESCRIPTION,
        //new
        //risk_related_rejections
        self::DEDUPE_BLOCKED                            => self::DEDUPE_BLOCKED_DESCRIPTION,
        self::DEDUPE_UNDER_REVIEW                       => self::DEDUPE_UNDER_REVIEW_DESCRIPTION,
        self::SUSPICIOUS_ONLINE_PRESENCE                => self::SUSPICIOUS_ONLINE_PRESENCE_DESCRIPTION,
        self::REJECT_ON_RISK_REMARKS                    => self::REJECT_ON_RISK_REMARKS_DESCRIPTION,
        self::RISK_RELATED_REJECTIONS_OTHERS            => self::RISK_RELATED_REJECTIONS_OTHERS_DESCRIPTION,
        //prohibited_business
        self::GET_RICH_SCHEMES                          => self::GET_RICH_SCHEMES_DESCRIPTION,
        self::BETTING_GAMBLING_LOTTERY                  => self::BETTING_GAMBLING_LOTTERY_DESCRIPTION,
        self::TOBACCO_MARIJUANA_VAPES                   => self::TOBACCO_MARIJUANA_VAPES_DESCRIPTION,
        self::ARMS_AND_AMMUNITION                       => self::ARMS_AND_AMMUNITION_DESCRIPTION,
        self::ONLINE_CASINO                             => self::ONLINE_CASINO_DESCRIPTION,
        self::MULTI_LEVEL_MARKETING                     => self::MULTI_LEVEL_MARKETING_DESCRIPTION,
        self::TECHNICAL_SUPPORT                         => self::TECHNICAL_SUPPORT_DESCRIPTION,
        self::AUCTIONING                                => self::AUCTIONING_DESCRIPTION,
        self::FAKE_AND_UNLICENSED_PRODUCTS              => self::FAKE_AND_UNLICENSED_PRODUCTS_DESCRIPTION,
        self::SOCIAL_MEDIA_BOOSTING                     => self::SOCIAL_MEDIA_BOOSTING_DESCRIPTION,
        self::GOVERNMENT_IMPERSONATOR                   => self::GOVERNMENT_IMPERSONATOR_DESCRIPTION,
        self::BPO_SERVICES                              => self::BPO_SERVICES_DESCRIPTION,
        self::PORNOGRAPHY                               => self::PORNOGRAPHY_DESCRIPTION,
        self::CRYPTOCURRENCY                            => self::CRYPTOCURRENCY_DESCRIPTION,
        self::SALE_OF_LIVESTOCK_AND_PETS                => self::SALE_OF_LIVESTOCK_AND_PETS_DESCRIPTION,
        self::HAZARDOUS_CHEMICALS                       => self::HAZARDOUS_CHEMICALS_DESCRIPTION,
        self::PROHIBITED_BUSINESS_OTHERS                => self::PROHIBITED_BUSINESS_OTHERS_DESCRIPTION,
        //unreg_blacklist
        self::UNREG_FINANCIAL_SERVICES                  => self::UNREG_FINANCIAL_SERVICES_DESCRIPTION,
        self::UNREG_DATING_AND_MATRIMONY                => self::UNREG_DATING_AND_MATRIMONY_DESCRIPTION,
        self::UNREG_CROWDFUNDING                        => self::UNREG_CROWDFUNDING_DESCRIPTION,
        self::UNREG_STOCK_TRADING                       => self::UNREG_STOCK_TRADING_DESCRIPTION,
        self::UNREG_ONLINE_GAMING                       => self::UNREG_ONLINE_GAMING_DESCRIPTION,
        self::UNREG_COUPONS_AND_DEALS                   => self::UNREG_COUPONS_AND_DEALS_DESCRIPTION,
        self::UNREG_SEXUAL_WELLNESS_PRODUCTS            => self::UNREG_SEXUAL_WELLNESS_PRODUCTS_DESCRIPTION,
        self::UNREG_CRYPTO_MACHINERY                    => self::UNREG_CRYPTO_MACHINERY_DESCRIPTION,
        self::UNREG_PHARMACY                            => self::UNREG_PHARMACY_DESCRIPTION,
        self::UNREG_LABORATORIES                        => self::UNREG_LABORATORIES_DESCRIPTION,
        self::UNREG_HIRING_SERVICES                     => self::UNREG_HIRING_SERVICES_DESCRIPTION,
        self::UNREG_AFFILIATE_MARKETING                 => self::UNREG_AFFILIATE_MARKETING_DESCRIPTION,
        self::UNREG_VIDEO_PLATFORMS                     => self::UNREG_VIDEO_PLATFORMS_DESCRIPTION,
        self::UNREG_BLACKLIST_OTHERS                    => self::UNREG_BLACKLIST_OTHERS_DESCRIPTION,
        //operational
        self::FORGED_DOCUMENT                           => self::FORGED_DOCUMENT_DESCRIPTION,
        self::INCOMPLETE_DOCUMENT                       => self::INCOMPLETE_DOCUMENT_DESCRIPTION,
        self::DUPLICATE_OR_ERRONEOUS_CREATION           => self::DUPLICATE_OR_ERRONEOUS_CREATION_DESCRIPTION,
        self::TEST_ACCOUNT                              => self::TEST_ACCOUNT_DESCRIPTION,
        self::OPERATIONAL_OTHERS                        => self::OPERATIONAL_OTHERS_DESCRIPTION,
        //high_risk_business
        self::ALCOHOLIC_BEVERAGES                       => self::ALCOHOLIC_BEVERAGES_DESCRIPTION,
        self::MULTIPLE_VERTICALS_HIGH_RISK              => self::MULTIPLE_VERTICALS_HIGH_RISK_DESCRIPTION,
        self::VENDOR_FULFILMENT_HIGH_RISK               => self::VENDOR_FULFILMENT_HIGH_RISK_DESCRIPTION,
        self::DATING_AND_MATRIMONY_HIGH_RISK            => self::DATING_AND_MATRIMONY_HIGH_RISK_DESCRIPTION,
        self::HIRING_SERVICES_HIGH_RISK                 => self::HIRING_SERVICES_HIGH_RISK_DESCRIPTION,
        self::REFURBISHED_GOODS                         => self::REFURBISHED_GOODS_DESCRIPTION,
        self::BULK_SMS_AND_DATABASES                    => self::BULK_SMS_AND_DATABASES_DESCRIPTION,
        self::HIGH_RISK_BUSINESS_OTHERS                 => self::HIGH_RISK_BUSINESS_OTHERS_DESCRIPTION,
        //unreg_high_risk
        self::UNREG_DONATIONS                           => self::UNREG_DONATIONS_DESCRIPTION,
        self::UNREG_AGENCY_SERVICES                     => self::UNREG_AGENCY_SERVICES_DESCRIPTION,
        self::UNREG_ASTROLOGY                           => self::UNREG_ASTROLOGY_DESCRIPTION,
        self::UNREG_REAL_ESTATE                         => self::UNREG_REAL_ESTATE_DESCRIPTION,
        self::UNREG_HIGH_RISK_OTHERS                    => self::UNREG_HIGH_RISK_OTHERS_DESCRIPTION
    ];
    // Reason codes to category mapping

    const REJECTION_REASONS_MAPPING = [
        //UNSUPPORTED_BUSINESS_MODEL
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
            [
                self::CODE        => self::CRYPTOCURRENCY,
                self::DESCRIPTION => self::CRYPTOCURRENCY_DESCRIPTION,
            ],
            [
                self::CODE        => self::EARN_MONEY_ONLINE,
                self::DESCRIPTION => self::EARN_MONEY_ONLINE_DESCRIPTION,
            ],
            [
                self::CODE        => self::TECHNICAL_SUPPORT,
                self::DESCRIPTION => self::TECHNICAL_SUPPORT_DESCRIPTION,
            ],
            [
                self::CODE        => self::BETTING,
                self::DESCRIPTION => self::BETTING_DESCRIPTION,
            ],
            [
                self::CODE        => self::SALE_OF_LIVESTOCK_PETS,
                self::DESCRIPTION => self::SALE_OF_LIVESTOCK_PETS_DESCRIPTION,
            ],
            [
                self::CODE        => self::STOCK_TRADING_TIPS,
                self::DESCRIPTION => self::STOCK_TRADING_TIPS_DESCRIPTION,
            ],
            [
                self::CODE        => self::AUCTIONING,
                self::DESCRIPTION => self::AUCTIONING_DESCRIPTION,
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
            [
                self::CODE        => self::FINANCIAL_ADVISORY,
                self::DESCRIPTION => self::FINANCIAL_ADVISORY_DESCRIPTION,
            ],
            [
                self::CODE        => self::CROWDFUNDING,
                self::DESCRIPTION => self::CROWDFUNDING_DESCRIPTION,
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
                self::CODE        => self::STOCK_TRADING_TIPS,
                self::DESCRIPTION => self::STOCK_TRADING_TIPS_DESCRIPTION,
            ],
            [
                self::CODE        => self::DROPSHIPPING,
                self::DESCRIPTION => self::DROPSHIPPING_DESCRIPTION,
            ],
        ],

        // Others
        self::OTHERS => [
            [
                self::CODE        => self::DUPLICATE_OR_ERRENOUS_CREATION,
                self::DESCRIPTION => self::DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION,
            ],
            [
                self::CODE        => self::IMPROPER_DOCUMENTATION,
                self::DESCRIPTION => self::IMPROPER_DOCUMENTATION_DESCRIPTION,
            ],
        ],

        //prohibited business
        Self::PROHIBITED_BUSINESSES => [
            [
                self::CODE        => self::IMPROPER_DOCUMENTATION,
                self::DESCRIPTION => self::IMPROPER_DOCUMENTATION_DESCRIPTION,
            ],
            [
                self::CODE        => self::DUPLICATE_OR_ERRENOUS_CREATION,
                self::DESCRIPTION => self::DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION,
            ],
            [
                self::CODE        => self::TECHNICAL_SUPPORT,
                self::DESCRIPTION => self::TECHNICAL_SUPPORT_DESCRIPTION,
            ],
            [
                self::CODE        => self::ALCOHOLIC_BEVERAGES,
                self::DESCRIPTION => self::ALCOHOLIC_BEVERAGES_DESCRIPTION,
            ],
            [
                self::CODE        => self::MULTIPLE_VERTICALS,
                self::DESCRIPTION => self::MULTIPLE_VERTICALS_DESCRIPTION,
            ],
            [
                self::CODE        => self::MULTI_LEVEL_MARKETING,
                self::DESCRIPTION => self::MULTI_LEVEL_MARKETING_DESCRIPTION,
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
                self::CODE        => self::SOCIAL_MEDIA_MARKETING,
                self::DESCRIPTION => self::SOCIAL_MEDIA_MARKETING_DESCRIPTION,
            ],
            [
                self::CODE        => self::OTHERS,
                self::DESCRIPTION => self::OTHERS_DESCRIPTION,
            ],
            [
                self::CODE        => self::NOT_REGISTERED_IN_INDIA,
                self::DESCRIPTION => self::NOT_REGISTERED_IN_INDIA_DESCRIPTION,
            ],
            [
                self::CODE        => self::CRYPTOCURRENCY,
                self::DESCRIPTION => self::CRYPTOCURRENCY_DESCRIPTION,
            ],
            [
                self::CODE        => self::EARN_MONEY_ONLINE,
                self::DESCRIPTION => self::EARN_MONEY_ONLINE_DESCRIPTION,
            ],
            [
                self::CODE        => self::BETTING,
                self::DESCRIPTION => self::BETTING_DESCRIPTION,
            ],
            [
                self::CODE        => self::SALE_OF_LIVESTOCK_PETS,
                self::DESCRIPTION => self::SALE_OF_LIVESTOCK_PETS_DESCRIPTION,
            ],
            [
                self::CODE        => self::AUCTIONING,
                self::DESCRIPTION => self::AUCTIONING_DESCRIPTION,
            ],
            [
                self::CODE        => self::DRUG_STORE_PHARMACY,
                self::DESCRIPTION => self::DRUG_STORE_PHARMACY_DESCRIPTION,
            ],
            [
                self::CODE        => self::SEXUAL_WELLNESS_AND_ADULT_TOYS,
                self::DESCRIPTION => self::SEXUAL_WELLNESS_AND_ADULT_TOYS_DESCRIPTION,
            ],
            [
                self::CODE        => self::CHIT_FUNDS,
                self::DESCRIPTION => self::CHIT_FUNDS_DESCRIPTION,
            ],
            [
                self::CODE        => self::BPO_SERVICES,
                self::DESCRIPTION => self::BPO_SERVICES_DESCRIPTION,
            ],
            [
                self::CODE        => self::TOBACCO_OR_MARHUANA,
                self::DESCRIPTION => self::TOBACCO_OR_MARHUANA_DESCRIPTION,
            ],
            [
                self::CODE        => self::HOTEL_BOOKING,
                self::DESCRIPTION => self::HOTEL_BOOKING_DESCRIPTION,
            ],
            [
                self::CODE        => self::VAPES_AND_E_CIGARETTES,
                self::DESCRIPTION => self::VAPES_AND_E_CIGARETTES_DESCRIPTION,
            ],
            [
                self::CODE        => self::TELEMARKETING_SERVICES,
                self::DESCRIPTION => self::TELEMARKETING_SERVICES_DESCRIPTION,
            ],
            [
                self::CODE        => self::HAZARDOUS_CHEMICAL,
                self::DESCRIPTION => self::HAZARDOUS_CHEMICAL_DESCRIPTION,
            ],
        ],
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

    public static function getDescriptionReasonCodeMapping()
    {
        return array_flip(self::REASON_CODES_DESCRIPTIONS_MAPPING);
    }

    public static function getReasonDescriptionByReasonCode(string $reasonCode): string
    {
        if (isset(self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode]) === false) {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_REJECTION_REASON_CODE);
        }

        return self::REASON_CODES_DESCRIPTIONS_MAPPING[$reasonCode];
    }
}
