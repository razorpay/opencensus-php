<?php

namespace RZP\Models\Merchant\Detail\BusinessCategoriesV2;

use RZP\Models\Merchant\Detail\BusinessCategoriesV2\BusinessSubcategory as Sub;

class BusinessCategory
{
    const CODE                      = 'code';
    const DESCRIPTION               = 'description';
    const SUBCATEGORIES             = 'subcategories';
    const DISPLAY_ORDER             = 'display_order';
    const CATEGORY_NAME             = 'category_name';
    const CATEGORY_VALUE            = 'category_value';

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

    //subcategory moved to top level categories
    const  COUPONS                              = 'coupons';
    const  REPAIR_AND_CLEANING                  = 'repair_and_cleaning';
    const  ACCOUNTING                           = 'accounting';
    const  TELECOMMUNICATION_SERVICE            = 'telecommunication_service';
    const  SERVICE_CENTRE                       = 'service_centre';
    const  COWORKING                            = 'coworking';
    const  CAB_HAILING                          = 'cab_hailing';
    const  GROCERY                              = 'grocery';
    const  PAAS                                 = 'paas';
    const  SAAS                                 = 'saas';
    const  WEB_DEVELOPMENT                      = 'web_development';
    const  CHARITY                              = 'charity';
    const  FASHION_AND_LIFESTYLE                = 'fashion_and_lifestyle';
    const  DROP_SHIPPING                        = 'drop_shipping';
    const  CONSULTING_AND_OUTSOURCING           = 'consulting_and_outsourcing';
    const  CATERING                             = 'catering';
    const  HEALTH_COACHING                      = 'health_coaching';
    const  COMPUTER_PROGRAMMING_DATA_PROCESSING = 'computer_programming_data_processing';
    const  UTILITIES_ELECTRIC_GAS_OIL_WATER     = 'utilities_electric_gas_oil_water';


    // Business Category Descriptions
    const DESCRIPTIONS = [
        self::FINANCIAL_SERVICES         => 'Financial product or service',
        self::EDUCATION                  => 'Education',
        self::HEALTHCARE                 => 'Healthcare, fitness, or wellness',
        self::UTILITIES                  => 'Utilities provider',
        self::GOVERNMENT                 => 'Public sector',
        self::LOGISTICS                  => 'Logistics',
        self::TOURS_AND_TRAVEL           => 'Tours and travel',
        self::TRANSPORT                  => 'Transport',
        self::ECOMMERCE                  => 'Ecommerce',
        self::FOOD                       => 'Food and beverages',
        self::IT_AND_SOFTWARE            => 'IT or software',
        self::GAMING                     => 'Gaming',
        self::MEDIA_AND_ENTERTAINMENT    => 'Media and entertainment',
        self::SERVICES                   => 'General services',
        self::HOUSING                    => 'Real estate, housing, rentals',
        self::NOT_FOR_PROFIT             => 'Nonprofit',
        self::SOCIAL                     => 'Social group or platform',
        self::OTHERS                     => 'Other',

        self::COUPONS                              => 'Coupons, discounts, deals',
        self::REPAIR_AND_CLEANING                  => 'Automobile garage',
        self::ACCOUNTING                           => 'Accounting Services',
        self::TELECOMMUNICATION_SERVICE            => 'Pre/Post Paid/Telecom Services',
        self::SERVICE_CENTRE                       => 'Service Centre',
        self::COWORKING                            => 'Real Estate Agents/Rentals',
        self::CAB_HAILING                          => 'Cab Service',
        self::GROCERY                              => 'Groceries',
        self::PAAS                                 => 'Platform as a service (PaaS)',
        self::SAAS                                 => 'Software as a service (SaaS)',
        self::WEB_DEVELOPMENT                      => 'Web design, development, hosting',
        self::CHARITY                              => 'Charity',
        self::FASHION_AND_LIFESTYLE                => 'Fashion and lifestyle',
        self::DROP_SHIPPING                        => 'General merchandise',
        self::CONSULTING_AND_OUTSOURCING           => 'Consulting or outsourcing',
        self::CATERING                             => 'Caterers',
        self::HEALTH_COACHING                      => 'Health and Beauty Spas',
        self::COMPUTER_PROGRAMMING_DATA_PROCESSING => 'Programming',
        self::UTILITIES_ELECTRIC_GAS_OIL_WATER     => 'Utilities–Electric, Gas, Water, Oil',
    ];

    const DISPLAY_ORDER_LIST = [
        self::ECOMMERCE                             => 1,
        self::EDUCATION                             => 1,
        self::FASHION_AND_LIFESTYLE                 => 1,
        self::FOOD                                  => 1,
        self::GROCERY                               => 1,
        self::IT_AND_SOFTWARE                       => 1,
        self::HEALTHCARE                            => 1,
        self::SERVICES                              => 1,
        self::WEB_DEVELOPMENT                       => 1,
        self::ACCOUNTING                            => 1,
        self::COUPONS                               => 1,
        self::REPAIR_AND_CLEANING                   => 1,
        self::CAB_HAILING                           => 1,
        self::CATERING                              => 1,
        self::CHARITY                               => 1,
        self::COMPUTER_PROGRAMMING_DATA_PROCESSING  => 1,
        self::CONSULTING_AND_OUTSOURCING            => 1,
        self::FINANCIAL_SERVICES                    => 1,
        self::GAMING                                => 1,
        self::DROP_SHIPPING                         => 1,
        self::GOVERNMENT                            => 1,
        self::HEALTH_COACHING                       => 1,
        self::HOUSING                               => 1,
        self::LOGISTICS                             => 1,
        self::MEDIA_AND_ENTERTAINMENT               => 1,
        self::NOT_FOR_PROFIT                        => 1,
        self::OTHERS                                => 1000,
        self::PAAS                                  => 1,
        self::COWORKING                             => 1,
        self::SAAS                                  => 1,
        self::SERVICE_CENTRE                        => 1,
        self::SOCIAL                                => 1,
        self::TELECOMMUNICATION_SERVICE             => 1,
        self::TOURS_AND_TRAVEL                      => 1,
        self::TRANSPORT                             => 1,
        self::UTILITIES                             => 1,
        self::UTILITIES_ELECTRIC_GAS_OIL_WATER      => 1,
    ];

    // Business Category to Subcategories Details mapping
    const SUBCATEGORY_MAP = [
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
            Sub::SPORTS_PRODUCTS,
            Sub::ARTS_AND_COLLECTIBLES,
            Sub::SEXUAL_WELLNESS_PRODUCTS,
            Sub::DROP_SHIPPING,
            Sub::CRYPTO_MACHINERY,
            Sub::TOBACCO,
            Sub::WEAPONS_AND_AMMUNITIONS,
            Sub::JEWELLERY_AND_WATCH_STORES,
            Sub::SHOE_STORES_RETAIL,
            Sub::COSMETIC_STORES,
            Sub::COMPUTERS_PERIPHERAL_EQUIPMENT_SOFTWARE,
            Sub::ACCESSORY_AND_APPAREL_STORES,
            Sub::FURNITURE_AND_HOME_FURNISHING_STORE,
            Sub::OTHERS,
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
            Sub::OTHERS,
        ],

        self::FASHION_AND_LIFESTYLE => [
            Sub::FASHION_AND_LIFESTYLE,
        ],

        self::FOOD => [
            Sub::ONLINE_FOOD_ORDERING,
            Sub::RESTAURANT,
            Sub::FOOD_COURT,
            Sub::CATERING,
            Sub::ALCOHOL,
            Sub::RESTAURANT_SEARCH_AND_BOOKING,
        ],

        self::GROCERY => [
            Sub::GROCERY,
        ],

        self::IT_AND_SOFTWARE => [
            Sub::SAAS,
            Sub::PAAS,
            Sub::IAAS,
            Sub::CONSULTING_AND_OUTSOURCING,
            Sub::WEB_DEVELOPMENT,
            Sub::TECHNICAL_SUPPORT,
            Sub::DATA_PROCESSING,
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
            Sub::MEDICAL_EQUIPMENT_AND_SUPPLY_STORES,
            Sub::HEALTH_PRACTITIONERS_MEDICAL_SERVICES,
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
            Sub::TELECOMMUNICATION_SERVICE,
            Sub::RECREATIONAL_AND_SPORTING_CAMPS,
            Sub::PHOTOGRAPHIC_STUDIO,
            Sub::PROFESSIONAL_SERVICES,
            Sub::OTHERS,
        ],

        self::WEB_DEVELOPMENT => [
            Sub::WEB_DEVELOPMENT,
        ],

        self::ACCOUNTING => [
            Sub::ACCOUNTING,
        ],

        self::COUPONS => [
            Sub::COUPONS,
        ],

        self::REPAIR_AND_CLEANING => [
            Sub::REPAIR_AND_CLEANING,
        ],

        self::CAB_HAILING => [
            Sub::CAB_HAILING,
        ],

        self::CATERING => [
            Sub::CATERING,
        ],

        self::CHARITY => [
            Sub::CHARITY,
        ],

        self::COMPUTER_PROGRAMMING_DATA_PROCESSING => [
            Sub::COMPUTER_PROGRAMMING_DATA_PROCESSING,
        ],

        self::CONSULTING_AND_OUTSOURCING => [
            Sub::CONSULTING_AND_OUTSOURCING,
        ],

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

        self::GAMING => [
            Sub::GAME_DEVELOPER,
            Sub::ESPORTS,
            Sub::ONLINE_CASINO,
            Sub::FANTASY_SPORTS,
            Sub::GAMING_MARKETPLACE,
        ],

        self::DROP_SHIPPING => [
            Sub::DROP_SHIPPING,
        ],

        self::GOVERNMENT => [
            Sub::CENTRAL,
            Sub::STATE,
        ],

        self::HEALTH_COACHING => [
            Sub::HEALTH_COACHING,
        ],

        self::HOUSING => [
            Sub::DEVELOPER,
            Sub::FACILITY_MANAGEMENT,
            Sub::RWA,
            Sub::COWORKING,
            Sub::REALESTATE_CLASSIFIEDS,
            Sub::SPACE_RENTAL,
        ],

        self::LOGISTICS => [
            Sub::FREIGHT,
            Sub::COURIER,
            Sub::WAREHOUSING,
            Sub::DISTRIBUTION,
            Sub::END_TO_END_LOGISTICS,
        ],

        self::MEDIA_AND_ENTERTAINMENT => [
            Sub::VIDEO_ON_DEMAND,
            Sub::MUSIC_STREAMING,
            Sub::MULTIPLEX,
            Sub::CONTENT_AND_PUBLISHING,
            Sub::TICKETING,
            Sub::NEWS,
        ],

        self::NOT_FOR_PROFIT => [
            Sub::CHARITY,
            Sub::EDUCATIONAL,
            Sub::RELIGIOUS,
            Sub::PERSONAL,
        ],

        self::OTHERS => [
        ],

        self::PAAS => [
            Sub::PAAS,
        ],

        self::COWORKING => [
            Sub::COWORKING,
        ],

        self::SAAS => [
            Sub::SAAS,
        ],

        self::SERVICE_CENTRE => [
            Sub::SERVICE_CENTRE,
        ],

        self::SOCIAL => [
            Sub::MATCHMAKING,
            Sub::SOCIAL_NETWORK,
            Sub::MESSAGING,
            Sub::PROFESSIONAL_NETWORK,
            Sub::NEIGHBOURHOOD_NETWORK,
            Sub::ASSOCIATIONS_AND_MEMBERSHIP,
            Sub::OTHERS,
        ],

        self::TELECOMMUNICATION_SERVICE => [
            Sub::TELECOMMUNICATION_SERVICE,
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
            Sub::AUTOMOBILE_RENTALS,
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
            Sub::OTHERS,
        ],

        self::UTILITIES_ELECTRIC_GAS_OIL_WATER => [
            Sub::UTILITIES_ELECTRIC_GAS_OIL_WATER,
        ],
    ];

    /**
     * This function checks if the given category is valid
     *
     * @param string $category
     *
     * @return boolean true/false
     */
    public static function isValidCategory(string $category) : bool
    {
        $key = __CLASS__ . '::' . strtoupper($category);

        return ((defined($key) === true) and (constant($key) === $category));
    }
}
