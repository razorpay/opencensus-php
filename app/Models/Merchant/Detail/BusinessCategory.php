<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Models\Merchant\Detail\BusinessSubcategory as Sub;

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
    const DESCRIPTIONS = [
        self::FINANCIAL_SERVICES      => 'Financial Services',
        self::EDUCATION               => 'Education',
        self::HEALTHCARE              => 'Healthcare',
        self::UTILITIES               => 'Utilities',
        self::GOVERNMENT              => 'Government',
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

    // Business Category to Subcategories Mapping
    const SUBCATEGORY_MAP = [
        self::FINANCIAL_SERVICES => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::FINANCIAL_SERVICES],
            self::SUBCATEGORIES => [
                Sub::MUTUAL_FUND                     => Sub::DESCRIPTIONS[Sub::MUTUAL_FUND],
                Sub::LENDING                         => Sub::DESCRIPTIONS[Sub::LENDING],
                Sub::CRYPTOCURRENCY                  => Sub::DESCRIPTIONS[Sub::CRYPTOCURRENCY],
                Sub::INSURANCE                       => Sub::DESCRIPTIONS[Sub::INSURANCE],
                Sub::NBFC                            => Sub::DESCRIPTIONS[Sub::NBFC],
                Sub::COOPERATIVES                    => Sub::DESCRIPTIONS[Sub::COOPERATIVES],
                Sub::PENSION_FUND                    => Sub::DESCRIPTIONS[Sub::PENSION_FUND],
                Sub::FOREX                           => Sub::DESCRIPTIONS[Sub::FOREX],
                Sub::SECURITIES                      => Sub::DESCRIPTIONS[Sub::SECURITIES],
                Sub::COMMODITIES                     => Sub::DESCRIPTIONS[Sub::COMMODITIES],
                Sub::ACCOUNTING                      => Sub::DESCRIPTIONS[Sub::ACCOUNTING],
                Sub::FINANCIAL_ADVISOR               => Sub::DESCRIPTIONS[Sub::FINANCIAL_ADVISOR],
                Sub::CROWDFUNDING                    => Sub::DESCRIPTIONS[Sub::CROWDFUNDING],
                Sub::TRADING                         => Sub::DESCRIPTIONS[Sub::TRADING],
            ],
        ],

        self::EDUCATION => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::EDUCATION],
            self::SUBCATEGORIES => [
                Sub::COLLEGE                         => Sub::DESCRIPTIONS[Sub::COLLEGE],
                Sub::SCHOOLS                         => Sub::DESCRIPTIONS[Sub::SCHOOLS],
                Sub::UNIVERSITY                      => Sub::DESCRIPTIONS[Sub::UNIVERSITY],
                Sub::PROFESSIONAL_COURSES            => Sub::DESCRIPTIONS[Sub::PROFESSIONAL_COURSES],
                Sub::DISTANCE_LEARNING               => Sub::DESCRIPTIONS[Sub::DISTANCE_LEARNING],
                Sub::DAY_CARE                        => Sub::DESCRIPTIONS[Sub::DAY_CARE],
                Sub::COACHING                        => Sub::DESCRIPTIONS[Sub::COACHING],
                Sub::ELEARNING                       => Sub::DESCRIPTIONS[Sub::ELEARNING],
            ],
        ],

        self::HEALTHCARE => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::HEALTHCARE],
            self::SUBCATEGORIES => [
                Sub::PHARMACY                        => Sub::DESCRIPTIONS[Sub::PHARMACY],
                Sub::CLINIC                          => Sub::DESCRIPTIONS[Sub::CLINIC],
                Sub::HOSPITAL                        => Sub::DESCRIPTIONS[Sub::HOSPITAL],
                Sub::LAB                             => Sub::DESCRIPTIONS[Sub::LAB],
                Sub::DIETICIAN                       => Sub::DESCRIPTIONS[Sub::DIETICIAN],
                Sub::FITNESS                         => Sub::DESCRIPTIONS[Sub::FITNESS],
                Sub::HEALTH_COACHING                 => Sub::DESCRIPTIONS[Sub::HEALTH_COACHING],
                Sub::HEALTH_PRODUCTS                 => Sub::DESCRIPTIONS[Sub::HEALTH_PRODUCTS],
                Sub::HEALTHCARE_MARKETPLACE          => Sub::DESCRIPTIONS[Sub::HEALTHCARE_MARKETPLACE],
            ],
        ],

        self::UTILITIES => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::UTILITIES],
            self::SUBCATEGORIES => [
                Sub::ELECTRICITY                     => Sub::DESCRIPTIONS[Sub::ELECTRICITY],
                Sub::GAS                             => Sub::DESCRIPTIONS[Sub::GAS],
                Sub::TELECOM                         => Sub::DESCRIPTIONS[Sub::TELECOM],
                Sub::WATER                           => Sub::DESCRIPTIONS[Sub::WATER],
                Sub::CABLE                           => Sub::DESCRIPTIONS[Sub::CABLE],
                Sub::BROADBAND                       => Sub::DESCRIPTIONS[Sub::BROADBAND],
                Sub::DTH                             => Sub::DESCRIPTIONS[Sub::DTH],
                Sub::INTERNET_PROVIDER               => Sub::DESCRIPTIONS[Sub::INTERNET_PROVIDER],
                Sub::BILL_AND_RECHARGE_AGGREGATORS   => Sub::DESCRIPTIONS[Sub::BILL_AND_RECHARGE_AGGREGATORS],
            ],
        ],

        self::GOVERNMENT => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::GOVERNMENT],
            self::SUBCATEGORIES => [
                Sub::CENTRAL                         => Sub::DESCRIPTIONS[Sub::CENTRAL],
                Sub::STATE                           => Sub::DESCRIPTIONS[Sub::STATE],
            ],
        ],

        self::LOGISTICS => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::LOGISTICS],
            self::SUBCATEGORIES => [
                Sub::FREIGHT                         => Sub::DESCRIPTIONS[Sub::FREIGHT],
                Sub::COURIER                         => Sub::DESCRIPTIONS[Sub::COURIER],
                Sub::WAREHOUSING                     => Sub::DESCRIPTIONS[Sub::WAREHOUSING],
                Sub::DISTRIBUTION_MANAGEMENT         => Sub::DESCRIPTIONS[Sub::DISTRIBUTION_MANAGEMENT],
                Sub::END_TO_END_LOGISTICS            => Sub::DESCRIPTIONS[Sub::END_TO_END_LOGISTICS],
            ],
        ],

        self::TOURS_AND_TRAVEL => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::TOURS_AND_TRAVEL],
            self::SUBCATEGORIES => [
                Sub::AVIATION                        => Sub::DESCRIPTIONS[Sub::AVIATION],
                Sub::ACCOMMODATION                   => Sub::DESCRIPTIONS[Sub::ACCOMMODATION],
                Sub::OTA                             => Sub::DESCRIPTIONS[Sub::OTA],
                Sub::TRAVEL_AGENCY                   => Sub::DESCRIPTIONS[Sub::TRAVEL_AGENCY],
            ],
        ],

        self::TRANSPORT => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::TRANSPORT],
            self::SUBCATEGORIES => [
                Sub::CAB_HAILING                     => Sub::DESCRIPTIONS[Sub::CAB_HAILING],
                Sub::BUS                             => Sub::DESCRIPTIONS[Sub::BUS],
                Sub::TRAIN_AND_METRO                 => Sub::DESCRIPTIONS[Sub::TRAIN_AND_METRO],
            ],
        ],

        self::ECOMMERCE => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::ECOMMERCE],
            self::SUBCATEGORIES => [
                Sub::ECOMMERCE_MARKETPLACE           => Sub::DESCRIPTIONS[Sub::ECOMMERCE_MARKETPLACE],
                Sub::AGRICULTURE                     => Sub::DESCRIPTIONS[Sub::AGRICULTURE],
                Sub::BOOKS                           => Sub::DESCRIPTIONS[Sub::BOOKS],
                Sub::ELECTRONICS_AND_FURNITURE       => Sub::DESCRIPTIONS[Sub::ELECTRONICS_AND_FURNITURE],
                Sub::COUPONS                         => Sub::DESCRIPTIONS[Sub::COUPONS],
                Sub::RENTAL                          => Sub::DESCRIPTIONS[Sub::RENTAL],
                Sub::FASHION_AND_LIFESTYLE           => Sub::DESCRIPTIONS[Sub::FASHION_AND_LIFESTYLE],
                Sub::GIFTING                         => Sub::DESCRIPTIONS[Sub::GIFTING],
                Sub::GROCERY                         => Sub::DESCRIPTIONS[Sub::GROCERY],
                Sub::BABY_PRODUCTS                   => Sub::DESCRIPTIONS[Sub::BABY_PRODUCTS],
                Sub::OFFICE_SUPPLIES                 => Sub::DESCRIPTIONS[Sub::OFFICE_SUPPLIES],
                Sub::WHOLESALE                       => Sub::DESCRIPTIONS[Sub::WHOLESALE],
                Sub::RELIGIOUS_PRODUCTS              => Sub::DESCRIPTIONS[Sub::RELIGIOUS_PRODUCTS],
                Sub::PET_PRODUCTS                    => Sub::DESCRIPTIONS[Sub::PET_PRODUCTS],
                Sub::SPORTS_PRODUCTS                 => Sub::DESCRIPTIONS[Sub::SPORTS_PRODUCTS],
                Sub::ARTS_AND_COLLECTIBLES           => Sub::DESCRIPTIONS[Sub::ARTS_AND_COLLECTIBLES],
            ],
        ],

        self::FOOD => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::FOOD],
            self::SUBCATEGORIES => [
                Sub::ONLINE_FOOD_ORDERING            => Sub::DESCRIPTIONS[Sub::ONLINE_FOOD_ORDERING],
                Sub::RESTAURANT                      => Sub::DESCRIPTIONS[Sub::RESTAURANT],
                Sub::FOOD_COURT                      => Sub::DESCRIPTIONS[Sub::FOOD_COURT],
                Sub::CATERING                        => Sub::DESCRIPTIONS[Sub::CATERING],
                Sub::ALCOHOL                         => Sub::DESCRIPTIONS[Sub::ALCOHOL],
                Sub::RESTAURANT_SEARCH_AND_BOOKING   => Sub::DESCRIPTIONS[Sub::RESTAURANT_SEARCH_AND_BOOKING],
            ],
        ],

        self::IT_AND_SOFTWARE => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::IT_AND_SOFTWARE],
            self::SUBCATEGORIES => [
                Sub::SAAS                            => Sub::DESCRIPTIONS[Sub::SAAS],
                Sub::PAAS                            => Sub::DESCRIPTIONS[Sub::PAAS],
                Sub::IAAS                            => Sub::DESCRIPTIONS[Sub::IAAS],
                Sub::CONSULTING_AND_OUTSOURCING      => Sub::DESCRIPTIONS[Sub::CONSULTING_AND_OUTSOURCING],
                Sub::WEB_DEVELOPMENT                 => Sub::DESCRIPTIONS[Sub::WEB_DEVELOPMENT],
            ],
        ],

        self::GAMING => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::GAMING],
            self::SUBCATEGORIES => [
                Sub::GAME_DEVELOPER                  => Sub::DESCRIPTIONS[Sub::GAME_DEVELOPER],
                Sub::ESPORTS                         => Sub::DESCRIPTIONS[Sub::ESPORTS],
                Sub::ONLINE_CASINO                   => Sub::DESCRIPTIONS[Sub::ONLINE_CASINO],
                Sub::FANTASY_SPORTS                  => Sub::DESCRIPTIONS[Sub::FANTASY_SPORTS],
                Sub::GAMING_MARKETPLACE              => Sub::DESCRIPTIONS[Sub::GAMING_MARKETPLACE],
            ],
        ],

        self::MEDIA_AND_ENTERTAINMENT => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::MEDIA_AND_ENTERTAINMENT],
            self::SUBCATEGORIES => [
                Sub::VIDEO_ON_DEMAND                 => Sub::DESCRIPTIONS[Sub::VIDEO_ON_DEMAND],
                Sub::MUSIC_STREAMING                 => Sub::DESCRIPTIONS[Sub::MUSIC_STREAMING],
                Sub::MULTIPLEX                       => Sub::DESCRIPTIONS[Sub::MULTIPLEX],
                Sub::CONTENT_AND_PUBLISHING          => Sub::DESCRIPTIONS[Sub::CONTENT_AND_PUBLISHING],
                Sub::TICKETING                       => Sub::DESCRIPTIONS[Sub::TICKETING],
                Sub::NEWS                            => Sub::DESCRIPTIONS[Sub::NEWS],
            ],
        ],

        self::SERVICES => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::SERVICES],
            self::SUBCATEGORIES => [
                Sub::REPAIR_AND_CLEANING             => Sub::DESCRIPTIONS[Sub::REPAIR_AND_CLEANING],
                Sub::INTERIOR_DESIGN_AND_ARCHITECT   => Sub::DESCRIPTIONS[Sub::INTERIOR_DESIGN_AND_ARCHITECT],
                Sub::MOVERS_AND_PACKERS              => Sub::DESCRIPTIONS[Sub::MOVERS_AND_PACKERS],
                Sub::LEGAL                           => Sub::DESCRIPTIONS[Sub::LEGAL],
                Sub::EVENT_PLANNING                  => Sub::DESCRIPTIONS[Sub::EVENT_PLANNING],
                Sub::SERVICE_CENTRE                  => Sub::DESCRIPTIONS[Sub::SERVICE_CENTRE],
                Sub::CONSULTING                      => Sub::DESCRIPTIONS[Sub::CONSULTING],
                Sub::AD_AND_MARKETING                => Sub::DESCRIPTIONS[Sub::AD_AND_MARKETING],
                Sub::SERVICES_CLASSIFIEDS            => Sub::DESCRIPTIONS[Sub::SERVICES_CLASSIFIEDS],
            ],
        ],

        self::HOUSING => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::HOUSING],
            self::SUBCATEGORIES => [
                Sub::DEVELOPER                       => Sub::DESCRIPTIONS[Sub::DEVELOPER],
                Sub::FACILITY_MANAGEMENT             => Sub::DESCRIPTIONS[Sub::FACILITY_MANAGEMENT],
                Sub::RWA                             => Sub::DESCRIPTIONS[Sub::RWA],
                Sub::COWORKING                       => Sub::DESCRIPTIONS[Sub::COWORKING],
                Sub::REALESTATE_CLASSIFIEDS          => Sub::DESCRIPTIONS[Sub::REALESTATE_CLASSIFIEDS],
                Sub::SPACE_RENTAL                    => Sub::DESCRIPTIONS[Sub::SPACE_RENTAL],
            ],
        ],

        self::NOT_FOR_PROFIT => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::NOT_FOR_PROFIT],
            self::SUBCATEGORIES => [
                Sub::CHARITY                         => Sub::DESCRIPTIONS[Sub::CHARITY],
                Sub::EDUCATIONAL                     => Sub::DESCRIPTIONS[Sub::EDUCATIONAL],
                Sub::RELIGIOUS                       => Sub::DESCRIPTIONS[Sub::RELIGIOUS],
                Sub::PERSONAL                        => Sub::DESCRIPTIONS[Sub::PERSONAL],
            ],
        ],

        self::SOCIAL => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::SOCIAL],
            self::SUBCATEGORIES => [
                Sub::MATCHMAKING                     => Sub::DESCRIPTIONS[Sub::MATCHMAKING],
                Sub::SOCIAL_NETWORK                  => Sub::DESCRIPTIONS[Sub::SOCIAL_NETWORK],
                Sub::MESSAGING                       => Sub::DESCRIPTIONS[Sub::MESSAGING],
                Sub::PROFESSIONAL_NETWORK            => Sub::DESCRIPTIONS[Sub::PROFESSIONAL_NETWORK],
                Sub::NEIGHBOURHOOD_NETWORK           => Sub::DESCRIPTIONS[Sub::NEIGHBOURHOOD_NETWORK],
            ],
        ],

        self::OTHERS => [
            self::DESCRIPTION   => self::DESCRIPTIONS[self::OTHERS],
            self::SUBCATEGORIES => [],
        ],
    ];
}
