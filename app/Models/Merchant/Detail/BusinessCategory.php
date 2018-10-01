<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;

class BusinessCategory
{
    const CODE                    = 'code';
    const DESCRIPTION             = 'description';
    const SUBCATEGORIES           = 'subcategories';

    // Business Category Codes
    const FINANCIAL_SERVICES      = 'financial_services';
    const EDUCATION               = 'education';
    const HEALTHCARE              = 'healthcare';
    const UTILITIES               = 'utilities';
    const GOVERNMENT              = 'government';
    const LOGISTICS               = 'logistics';
    const TOURS_AND_TRAVEL        = 'tours_and_travel';
    const TRANSPORT               = 'transport';
    const ECOMMERCE               = 'ecommerce';
    const FOOD                    = 'food';
    const IT_AND_SOFTWARE         = 'it_and_software';
    const GAMING                  = 'gaming';
    const MEDIA_AND_ENTERTAINMENT = 'media_and_entertainment';
    const SERVICES                = 'services';
    const HOUSING                 = 'housing';
    const NOT_FOR_PROFIT          = 'not_for_profit';
    const SOCIAL                  = 'social';
    const OTHERS                  = 'others';

    // Business Category Descriptions
    const DESCRIPTIONS = [
        self::FINANCIAL_SERVICES      => 'Financial Services',
        self::EDUCATION               => 'Education',
        self::HEALTHCARE              => 'Healthcare',
        self::UTILITIES               => 'Utilities',
        self::GOVERNMENT              => 'Government Bodies',
        self::LOGISTICS               => 'Logistics',
        self::TOURS_AND_TRAVEL        => 'Tours and Travel',
        self::TRANSPORT               => 'Transport',
        self::ECOMMERCE               => 'Ecommerce',
        self::FOOD                    => 'Food and Beverage',
        self::IT_AND_SOFTWARE         => 'IT and Software',
        self::GAMING                  => 'Gaming',
        self::MEDIA_AND_ENTERTAINMENT => 'Media and Entertainment',
        self::SERVICES                => 'Services',
        self::HOUSING                 => 'Housing and Real Estate',
        self::NOT_FOR_PROFIT          => 'Not-For-Profit',
        self::SOCIAL                  => 'Social',
        self::OTHERS                  => 'Others',
    ];

    // Business Category to Subcategories Details mapping
    const SUBCATEGORY_MAP = [
        self::FINANCIAL_SERVICES => [
            Sub::MUTUAL_FUND,
            Sub::LENDING,
            Sub::CRYPTOCURRENCY,
            Sub::INSURANCE,
            Sub::NBFC,
            Sub::COOPERATIVES,
            Sub::PENSION_FUND,
            Sub::FOREX,
            Sub::SECURITIES,
            Sub::COMMODITIES,
            Sub::ACCOUNTING,
            Sub::FINANCIAL_ADVISOR,
            Sub::CROWDFUNDING,
            Sub::TRADING,
            Sub::BETTING,
            Sub::GET_RICH_SCHEMES,
        ],

        self::EDUCATION => [
            Sub::COLLEGE,
            Sub::SCHOOLS,
            Sub::UNIVERSITY,
            Sub::PROFESSIONAL_COURSES,
            Sub::DISTANCE_LEARNING,
            Sub::DAY_CARE,
            Sub::COACHING,
            Sub::ELEARNING,
        ],

        self::HEALTHCARE => [
            Sub::PHARMACY,
            Sub::CLINIC,
            Sub::HOSPITAL,
            Sub::LAB,
            Sub::DIETICIAN,
            Sub::FITNESS,
            Sub::HEALTH_COACHING,
            Sub::HEALTH_PRODUCTS,
            Sub::HEALTHCARE_MARKETPLACE,
        ],

        self::UTILITIES => [
            Sub::ELECTRICITY,
            Sub::GAS,
            Sub::TELECOM,
            Sub::WATER,
            Sub::CABLE,
            Sub::BROADBAND,
            Sub::DTH,
            Sub::INTERNET_PROVIDER,
            Sub::BILL_AND_RECHARGE_AGGREGATORS,
        ],

        self::GOVERNMENT => [
            Sub::CENTRAL,
            Sub::STATE,
        ],

        self::LOGISTICS => [
            Sub::FREIGHT,
            Sub::COURIER,
            Sub::WAREHOUSING,
            Sub::DISTRIBUTION_MANAGEMENT,
            Sub::END_TO_END_LOGISTICS,
        ],

        self::TOURS_AND_TRAVEL => [
            Sub::AVIATION,
            Sub::ACCOMMODATION,
            Sub::OTA,
            Sub::TRAVEL_AGENCY,
        ],

        self::TRANSPORT => [
            Sub::CAB_HAILING,
            Sub::BUS,
            Sub::TRAIN_AND_METRO,
        ],

        self::ECOMMERCE => [
            Sub::ECOMMERCE_MARKETPLACE,
            Sub::AGRICULTURE,
            Sub::BOOKS,
            Sub::ELECTRONICS_AND_FURNITURE,
            Sub::COUPONS,
            Sub::RENTAL,
            Sub::FASHION_AND_LIFESTYLE,
            Sub::GIFTING,
            Sub::GROCERY,
            Sub::BABY_PRODUCTS,
            Sub::OFFICE_SUPPLIES,
            Sub::WHOLESALE,
            Sub::RELIGIOUS_PRODUCTS,
            Sub::PET_PRODUCTS,
            Sub::SPORTS_PRODUCTS,
            Sub::ARTS_AND_COLLECTIBLES,
            Sub::SEXUAL_WELLNESS_PRODUCTS,
            Sub::DROP_SHIPPING,
            Sub::CRYPTO_MACHINERY,
            Sub::TOBACCO,
            Sub::WEAPONS_AND_AMMUNITIONS,
        ],

        self::FOOD => [
            Sub::ONLINE_FOOD_ORDERING,
            Sub::RESTAURANT,
            Sub::FOOD_COURT,
            Sub::CATERING,
            Sub::ALCOHOL,
            Sub::RESTAURANT_SEARCH_AND_BOOKING,
        ],

        self::IT_AND_SOFTWARE => [
            Sub::SAAS,
            Sub::PAAS,
            Sub::IAAS,
            Sub::CONSULTING_AND_OUTSOURCING,
            Sub::WEB_DEVELOPMENT,
            Sub::TECHNICAL_SUPPORT,
        ],

        self::GAMING => [
            Sub::GAME_DEVELOPER,
            Sub::ESPORTS,
            Sub::ONLINE_CASINO,
            Sub::FANTASY_SPORTS,
            Sub::GAMING_MARKETPLACE,
        ],

        self::MEDIA_AND_ENTERTAINMENT => [
            Sub::VIDEO_ON_DEMAND,
            Sub::MUSIC_STREAMING,
            Sub::MULTIPLEX,
            Sub::CONTENT_AND_PUBLISHING,
            Sub::TICKETING,
            Sub::NEWS,
        ],

        self::SERVICES => [
            Sub::REPAIR_AND_CLEANING,
            Sub::INTERIOR_DESIGN_AND_ARCHITECT,
            Sub::MOVERS_AND_PACKERS,
            Sub::LEGAL,
            Sub::EVENT_PLANNING,
            Sub::SERVICE_CENTRE,
            Sub::CONSULTING,
            Sub::AD_AND_MARKETING,
            Sub::SERVICES_CLASSIFIEDS,
            Sub::MULTI_LEVEL_MARKETING,
        ],

        self::HOUSING => [
            Sub::DEVELOPER,
            Sub::FACILITY_MANAGEMENT,
            Sub::RWA,
            Sub::COWORKING,
            Sub::REALESTATE_CLASSIFIEDS,
            Sub::SPACE_RENTAL,
        ],

        self::NOT_FOR_PROFIT => [
            Sub::CHARITY,
            Sub::EDUCATIONAL,
            Sub::RELIGIOUS,
            Sub::PERSONAL,
        ],

        self::SOCIAL => [
            Sub::MATCHMAKING,
            Sub::SOCIAL_NETWORK,
            Sub::MESSAGING,
            Sub::PROFESSIONAL_NETWORK,
            Sub::NEIGHBOURHOOD_NETWORK,
        ],

        self::OTHERS => [
        ],
    ];
}
