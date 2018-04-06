<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;
use RZP\Models\Merchant\Detail\BusinessSubcategory as Subcategory;

class BusinessCategory
{
    const CODE                                = 'code';
    const DESCRIPTION                         = 'description';
    const SUBCATEGORIES                       = 'subcategories';

    // Business Category Codes
    const FINANCIAL_SERVICES                  = 'financial_services';
    const EDUCATION                           = 'education';
    const HEALTHCARE                          = 'healthcare';
    const UTILITIES                           = 'utilities';
    const GOVERNMENT                          = 'government';
    const LOGISTICS                           = 'logistics';
    const TOURS_AND_TRAVEL                    = 'tours_and_travel';
    const TRANSPORT                           = 'transport';
    const ECOMMERCE                           = 'ecommerce';
    const FOOD                                = 'food';
    const IT_AND_SOFTWARE                     = 'it_and_software';
    const GAMING                              = 'gaming';
    const MEDIA_AND_ENTERTAINMENT             = 'media_and_entertainment';
    const SERVICES                            = 'services';
    const HOUSING                             = 'housing';
    const NOT_FOR_PROFIT                      = 'not_for_profit';
    const SOCIAL                              = 'social';
    const OTHERS                              = 'others';

    // Business Category Descriptions
    const FINANCIAL_SERVICES_DESCRIPTION      = 'Financial Services';
    const EDUCATION_DESCRIPTION               = 'Education';
    const HEALTHCARE_DESCRIPTION              = 'Healthcare';
    const UTILITIES_DESCRIPTION               = 'Utilities';
    const GOVERNMENT_DESCRIPTION              = 'Government';
    const LOGISTICS_DESCRIPTION               = 'Logistics';
    const TOURS_AND_TRAVEL_DESCRIPTION        = 'Tours and Travel';
    const TRANSPORT_DESCRIPTION               = 'Transport';
    const ECOMMERCE_DESCRIPTION               = 'Ecommerce';
    const FOOD_DESCRIPTION                    = 'Food and Beverage';
    const IT_AND_SOFTWARE_DESCRIPTION         = 'IT and Software';
    const GAMING_DESCRIPTION                  = 'Gaming';
    const MEDIA_AND_ENTERTAINMENT_DESCRIPTION = 'Media and Entertainment';
    const SERVICES_DESCRIPTION                = 'Services';
    const HOUSING_DESCRIPTION                 = 'Housing and Real Estate';
    const NOT_FOR_PROFIT_DESCRIPTION          = 'Not-For-Profit';
    const SOCIAL_DESCRIPTION                  = 'Social';
    const OTHERS_DESCRIPTION                  = 'Others';

    // Business Category to Subcategories Mapping
    const BUSINESS_CATEGORY_SUBCATEGORIES_MAPPING = [
        self::FINANCIAL_SERVICES => [
            self::DESCRIPTION   => self::FINANCIAL_SERVICES_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::MUTUAL_FUND                   => Subcategory::MUTUAL_FUND_DESCRIPTION,
                Subcategory::LENDING                       => Subcategory::LENDING_DESCRIPTION,
                Subcategory::CRYPTOCURRENCY                => Subcategory::CRYPTOCURRENCY_DESCRIPTION,
                Subcategory::INSURANCE                     => Subcategory::INSURANCE_DESCRIPTION,
                Subcategory::NBFC                          => Subcategory::NBFC_DESCRIPTION,
                Subcategory::COOPERATIVES                  => Subcategory::COOPERATIVES_DESCRIPTION,
                Subcategory::PENSION_FUND                  => Subcategory::PENSION_FUND_DESCRIPTION,
                Subcategory::FOREX                         => Subcategory::FOREX_DESCRIPTION,
                Subcategory::SECURITIES                    => Subcategory::SECURITIES_DESCRIPTION,
                Subcategory::COMMODITIES                   => Subcategory::COMMODITIES_DESCRIPTION,
                Subcategory::ACCOUNTING                    => Subcategory::ACCOUNTING_DESCRIPTION,
                Subcategory::FINANCIAL_ADVISOR             => Subcategory::FINANCIAL_ADVISOR_DESCRIPTION,
                Subcategory::CROWDFUNDING                  => Subcategory::CROWDFUNDING_DESCRIPTION,
                Subcategory::TRADING                       => Subcategory::TRADING_DESCRIPTION,
            ],
        ],
        self::EDUCATION => [
            self::DESCRIPTION   => self::EDUCATION_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::COLLEGE                       => Subcategory::COLLEGE_DESCRIPTION,
                Subcategory::SCHOOLS                       => Subcategory::SCHOOLS_DESCRIPTION,
                Subcategory::UNIVERSITY                    => Subcategory::UNIVERSITY_DESCRIPTION,
                Subcategory::PROFESSIONAL_COURSES          => Subcategory::PROFESSIONAL_COURSES_DESCRIPTION,
                Subcategory::DISTANCE_LEARNING             => Subcategory::DISTANCE_LEARNING_DESCRIPTION,
                Subcategory::DAY_CARE                      => Subcategory::DAY_CARE_DESCRIPTION,
                Subcategory::COACHING                      => Subcategory::COACHING_DESCRIPTION,
                Subcategory::ELEARNING                     => Subcategory::ELEARNING_DESCRIPTION,
            ],
        ],
        self::HEALTHCARE => [
            self::DESCRIPTION   => self::HEALTHCARE_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::PHARMACY                      => Subcategory::PHARMACY_DESCRIPTION,
                Subcategory::CLINIC                        => Subcategory::CLINIC_DESCRIPTION,
                Subcategory::HOSPITAL                      => Subcategory::HOSPITAL_DESCRIPTION,
                Subcategory::LAB                           => Subcategory::LAB_DESCRIPTION,
                Subcategory::DIETICIAN                     => Subcategory::DIETICIAN_DESCRIPTION,
                Subcategory::FITNESS                       => Subcategory::FITNESS_DESCRIPTION,
                Subcategory::HEALTH_COACHING               => Subcategory::HEALTH_COACHING_DESCRIPTION,
                Subcategory::HEALTH_PRODUCTS               => Subcategory::HEALTH_PRODUCTS_DESCRIPTION,
                Subcategory::MARKETPLACE                   => Subcategory::MARKETPLACE_DESCRIPTION,
            ],
        ],
        self::UTILITIES => [
            self::DESCRIPTION   => self::UTILITIES_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::ELECTRICITY                   => Subcategory::ELECTRICITY_DESCRIPTION,
                Subcategory::GAS                           => Subcategory::GAS_DESCRIPTION,
                Subcategory::TELECOM                       => Subcategory::TELECOM_DESCRIPTION,
                Subcategory::WATER                         => Subcategory::WATER_DESCRIPTION,
                Subcategory::CABLE                         => Subcategory::CABLE_DESCRIPTION,
                Subcategory::BROADBAND                     => Subcategory::BROADBAND_DESCRIPTION,
                Subcategory::DTH                           => Subcategory::DTH_DESCRIPTION,
                Subcategory::INTERNET_PROVIDER             => Subcategory::INTERNET_PROVIDER_DESCRIPTION,
                Subcategory::BILL_AND_RECHARGE_AGGREGATORS => Subcategory::BILL_AND_RECHARGE_AGGREGATORS_DESCRIPTION,
            ],
        ],
        self::GOVERNMENT => [
            self::DESCRIPTION   => self::GOVERNMENT_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::CENTRAL                       => Subcategory::CENTRAL_DESCRIPTION,
                Subcategory::STATE                         => Subcategory::STATE_DESCRIPTION,
            ],
        ],
        self::LOGISTICS => [
            self::DESCRIPTION   => self::LOGISTICS_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::FREIGHT                       => Subcategory::FREIGHT_DESCRIPTION,
                Subcategory::COURIER                       => Subcategory::COURIER_DESCRIPTION,
                Subcategory::WAREHOUSING                   => Subcategory::WAREHOUSING_DESCRIPTION,
                Subcategory::DISTRIBUTION_MANAGEMENT       => Subcategory::DISTRIBUTION_MANAGEMENT_DESCRIPTION,
                Subcategory::END_TO_END_LOGISTICS          => Subcategory::END_TO_END_LOGISTICS_DESCRIPTION,
            ],
        ],
        self::TOURS_AND_TRAVEL => [
            self::DESCRIPTION   => self::TOURS_AND_TRAVEL_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::AVIATION                      => Subcategory::AVIATION_DESCRIPTION,
                Subcategory::ACCOMMODATION                 => Subcategory::ACCOMMODATION_DESCRIPTION,
                Subcategory::OTA                           => Subcategory::OTA_DESCRIPTION,
                Subcategory::TRAVEL_AGENCY                 => Subcategory::TRAVEL_AGENCY_DESCRIPTION,
            ],
        ],
        self::TRANSPORT => [
            self::DESCRIPTION   => self::TRANSPORT_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::CAB_HAILING                   => Subcategory::CAB_HAILING_DESCRIPTION,
                Subcategory::BUS                           => Subcategory::BUS_DESCRIPTION,
                Subcategory::TRAIN_AND_METRO               => Subcategory::TRAIN_AND_METRO_DESCRIPTION,
            ],
        ],
        self::ECOMMERCE => [
            self::DESCRIPTION   => self::ECOMMERCE_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::MARKETPLACE                   => Subcategory::HORIZONTAL_COMMERCE_DESCRIPTION,
                Subcategory::AGRICULTURE                   => Subcategory::AGRICULTURE_DESCRIPTION,
                Subcategory::BOOKS                         => Subcategory::BOOKS_DESCRIPTION,
                Subcategory::ELECTRONICS_AND_FURNITURE     => Subcategory::ELECTRONICS_AND_FURNITURE_DESCRIPTION,
                Subcategory::COUPONS                       => Subcategory::COUPONS_DESCRIPTION,
                Subcategory::RENTAL                        => Subcategory::RENTAL_DESCRIPTION,
                Subcategory::FASHION_AND_LIFESTYLE         => Subcategory::FASHION_AND_LIFESTYLE_DESCRIPTION,
                Subcategory::GIFTING                       => Subcategory::GIFTING_DESCRIPTION,
                Subcategory::GROCERY                       => Subcategory::GROCERY_DESCRIPTION,
                Subcategory::BABY_PRODUCTS                 => Subcategory::BABY_PRODUCTS_DESCRIPTION,
                Subcategory::OFFICE_SUPPLIES               => Subcategory::OFFICE_SUPPLIES_DESCRIPTION,
                Subcategory::WHOLESALE                     => Subcategory::WHOLESALE_DESCRIPTION,
                Subcategory::RELIGIOUS_PRODUCTS            => Subcategory::RELIGIOUS_PRODUCTS_DESCRIPTION,
                Subcategory::PET_PRODUCTS                  => Subcategory::PET_PRODUCTS_DESCRIPTION,
                Subcategory::SPORTS_PRODUCTS               => Subcategory::SPORTS_PRODUCTS_DESCRIPTION,
                Subcategory::ARTS_AND_COLLECTIBLES         => Subcategory::ARTS_AND_COLLECTIBLES_DESCRIPTION,
            ],
        ],
        self::FOOD => [
            self::DESCRIPTION   => self::FOOD_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::ONLINE_FOOD_ORDERING          => Subcategory::ONLINE_FOOD_ORDERING_DESCRIPTION,
                Subcategory::RESTAURANT                    => Subcategory::RESTAURANT_DESCRIPTION,
                Subcategory::FOOD_COURT                    => Subcategory::FOOD_COURT_DESCRIPTION,
                Subcategory::CATERING                      => Subcategory::CATERING_DESCRIPTION,
                Subcategory::ALCOHOL                       => Subcategory::ALCOHOL_DESCRIPTION,
                Subcategory::RESTAURANT_SEARCH_AND_BOOKING => Subcategory::RESTAURANT_SEARCH_AND_BOOKING_DESCRIPTION,
            ],
        ],
        self::IT_AND_SOFTWARE => [
            self::DESCRIPTION   => self::IT_AND_SOFTWARE_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::SAAS                          => Subcategory::SAAS_DESCRIPTION,
                Subcategory::PAAS                          => Subcategory::PAAS_DESCRIPTION,
                Subcategory::IAAS                          => Subcategory::IAAS_DESCRIPTION,
                Subcategory::CONSULTING_AND_OUTSOURCING    => Subcategory::CONSULTING_AND_OUTSOURCING_DESCRIPTION,
                Subcategory::WEB_DEVELOPMENT               => Subcategory::WEB_DEVELOPMENT_DESCRIPTION,
            ],
        ],
        self::GAMING => [
            self::DESCRIPTION   => self::GAMING_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::GAME_DEVELOPER                => Subcategory::GAME_DEVELOPER_DESCRIPTION,
                Subcategory::ESPORTS                       => Subcategory::ESPORTS_DESCRIPTION,
                Subcategory::ONLINE_CASINO                 => Subcategory::ONLINE_CASINO_DESCRIPTION,
                Subcategory::FANTASY_SPORTS                => Subcategory::FANTASY_SPORTS_DESCRIPTION,
                Subcategory::MARKETPLACE                   => Subcategory::GAME_DISTRIBUTOR_DESCRIPTION,
            ],
        ],
        self::MEDIA_AND_ENTERTAINMENT => [
            self::DESCRIPTION   => self::MEDIA_AND_ENTERTAINMENT_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::VIDEO_ON_DEMAND               => Subcategory::VIDEO_ON_DEMAND_DESCRIPTION,
                Subcategory::MUSIC_STREAMING               => Subcategory::MUSIC_STREAMING_DESCRIPTION,
                Subcategory::MULTIPLEX                     => Subcategory::MULTIPLEX_DESCRIPTION,
                Subcategory::CONTENT_AND_PUBLISHING        => Subcategory::CONTENT_AND_PUBLISHING_DESCRIPTION,
                Subcategory::TICKETING                     => Subcategory::TICKETING_DESCRIPTION,
                Subcategory::NEWS                          => Subcategory::NEWS_DESCRIPTION,
            ],
        ],
        self::SERVICES => [
            self::DESCRIPTION   => self::SERVICES_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::REPAIR_AND_CLEANING           => Subcategory::REPAIR_AND_CLEANING_DESCRIPTION,
                Subcategory::INTERIOR_DESIGN_AND_ARCHITECT => Subcategory::INTERIOR_DESIGN_AND_ARCHITECT_DESCRIPTION,
                Subcategory::MOVERS_AND_PACKERS            => Subcategory::MOVERS_AND_PACKERS_DESCRIPTION,
                Subcategory::LEGAL                         => Subcategory::LEGAL_DESCRIPTION,
                Subcategory::EVENT_PLANNING                => Subcategory::EVENT_PLANNING_DESCRIPTION,
                Subcategory::SERVICE_CENTRE                => Subcategory::SERVICE_CENTRE_DESCRIPTION,
                Subcategory::CONSULTING                    => Subcategory::CONSULTING_DESCRIPTION,
                Subcategory::AD_AND_MARKETING              => Subcategory::AD_AND_MARKETING_DESCRIPTION,
                Subcategory::CLASSIFIEDS                   => Subcategory::CLASSIFIEDS_DESCRIPTION,
            ],
        ],
        self::HOUSING => [
            self::DESCRIPTION   => self::HOUSING_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::DEVELOPER                     => Subcategory::DEVELOPER_DESCRIPTION,
                Subcategory::FACILITY_MANAGEMENT           => Subcategory::FACILITY_MANAGEMENT_DESCRIPTION,
                Subcategory::RWA                           => Subcategory::RWA_DESCRIPTION,
                Subcategory::COWORKING                     => Subcategory::COWORKING_DESCRIPTION,
                Subcategory::CLASSIFIEDS                   => Subcategory::REAL_ESTATE_DESCRIPTION,
                Subcategory::SPACE_RENTAL                  => Subcategory::SPACE_RENTAL_DESCRIPTION,
            ],
        ],
        self::NOT_FOR_PROFIT => [
            self::DESCRIPTION   => self::NOT_FOR_PROFIT_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::CHARITY                       => Subcategory::CHARITY_DESCRIPTION,
                Subcategory::EDUCATIONAL                   => Subcategory::EDUCATIONAL_DESCRIPTION,
                Subcategory::RELIGIOUS                     => Subcategory::RELIGIOUS_DESCRIPTION,
                Subcategory::PERSONAL                      => Subcategory::PERSONAL_DESCRIPTION,
            ],
        ],
        self::SOCIAL => [
            self::DESCRIPTION   => self::SOCIAL_DESCRIPTION,
            self::SUBCATEGORIES => [
                Subcategory::MATCHMAKING                    => Subcategory::MATCHMAKING_DESCRIPTION,
                Subcategory::SOCIAL_NETWORK                 => Subcategory::SOCIAL_NETWORK_DESCRIPTION,
                Subcategory::MESSAGING                      => Subcategory::MESSAGING_DESCRIPTION,
                Subcategory::PROFESSIONAL_NETWORK           => Subcategory::PROFESSIONAL_NETWORK_DESCRIPTION,
                Subcategory::NEIGHBOURHOOD_NETWORK          => Subcategory::NEIGHBOURHOOD_NETWORK_DESCRIPTION,
            ],
        ],
        self::OTHERS => [
            self::DESCRIPTION   => self::OTHERS_DESCRIPTION,
            self::SUBCATEGORIES => [],
        ],
    ];
}
