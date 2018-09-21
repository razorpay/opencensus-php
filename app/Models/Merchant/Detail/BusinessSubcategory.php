<?php

namespace RZP\Models\Merchant\Detail;


use phpDocumentor\Reflection\DocBlock\Description;

class BusinessSubcategory
{
    const MCC_CODE                        = 'mcc_code';
    const DESCRIPTION                     = 'description';
    const INVALID_SUB_CATEGORIES          = [
        'descriptions',
        'sub_category_metadata',
        'invalid_sub_categories',
        self::MCC_CODE,
        self::DESCRIPTION,
    ];

    // Business Subcategory Codes
    const ACCOMMODATION                   = 'accommodation';
    const ACCOUNTING                      = 'accounting';
    const AD_AND_MARKETING                = 'ad_and_marketing';
    const AGRICULTURE                     = 'agriculture';
    const ALCOHOL                         = 'alcohol';
    const ARTS_AND_COLLECTIBLES           = 'arts_and_collectibles';
    const AVIATION                        = 'aviation';

    const BABY_PRODUCTS                   = 'baby_products';
    const BETTING                         = 'betting';
    const BILL_AND_RECHARGE_AGGREGATORS   = 'bill_and_recharge_aggregators';
    const BOOKS                           = 'books';
    const BROADBAND                       = 'broadband';
    const BUS                             = 'bus';

    const CABLE                           = 'cable';
    const CAB_HAILING                     = 'cab_hailing';
    const CATERING                        = 'catering';
    const CENTRAL                         = 'central';
    const CHARITY                         = 'charity';
    const CLINIC                          = 'clinic';
    const COACHING                        = 'coaching';
    const COLLEGE                         = 'college';
    const COMMODITIES                     = 'commodities';
    const CONSULTING                      = 'consulting';
    const CONSULTING_AND_OUTSOURCING      = 'consulting_and_outsourcing';
    const CONTENT_AND_PUBLISHING          = 'content_and_publishing';
    const COOPERATIVES                    = 'cooperatives';
    const COUPONS                         = 'coupons';
    const COURIER                         = 'courier';
    const COWORKING                       = 'coworking';
    const CROWDFUNDING                    = 'crowdfunding';
    const CRYPTOCURRENCY                  = 'cryptocurrency';
    const CRYPTO_MACHINERY                = 'crypto_machinery ';

    const DAY_CARE                        = 'day_care';
    const DEVELOPER                       = 'developer';
    const DIETICIAN                       = 'dietician';
    const DISTANCE_LEARNING               = 'distance_learning';
    const DISTRIBUTION_MANAGEMENT         = 'distribution';
    const DROP_SHIPPING                   = 'drop_shipping';
    const DTH                             = 'dth';

    const ECOMMERCE_MARKETPLACE           = 'ecommerce_marketplace';
    const EDUCATIONAL                     = 'educational';
    const ELEARNING                       = 'elearning';
    const ELECTRICITY                     = 'electricity';
    const ELECTRONICS_AND_FURNITURE       = 'electronics_and_furniture';
    const END_TO_END_LOGISTICS            = 'end_to_end_logistics';
    const ESPORTS                         = 'esports';
    const EVENT_PLANNING                  = 'event_planning';

    const FACILITY_MANAGEMENT             = 'facility_management';
    const FANTASY_SPORTS                  = 'fantasy_sports';
    const FASHION_AND_LIFESTYLE           = 'fashion_and_lifestyle';
    const FINANCIAL_ADVISOR               = 'financial_advisor';
    const FITNESS                         = 'fitness';
    const FOOD_COURT                      = 'food_court';
    const FOREX                           = 'forex';
    const FREIGHT                         = 'freight';

    const GAME_DEVELOPER                  = 'game_developer';
    const GAMING_MARKETPLACE              = 'gaming_marketplace';
    const GAS                             = 'gas';
    const GET_RICH_SCHEMES                = 'get_rich_schemes';
    const GIFTING                         = 'gifting';
    const GROCERY                         = 'grocery';

    const HEALTH_COACHING                 = 'health_coaching';
    const HEALTH_PRODUCTS                 = 'health_products';
    const HEALTHCARE_MARKETPLACE          = 'healthcare_marketplace';
    const HOSPITAL                        = 'hospital';

    const IAAS                            = 'iaas';
    const INSURANCE                       = 'insurance';
    const INTERIOR_DESIGN_AND_ARCHITECT   = 'interior_design_and_architect';
    const INTERNET_PROVIDER               = 'internet_provider';

    const LAB                             = 'lab';
    const LEGAL                           = 'legal';
    const LENDING                         = 'lending';

    const MATCHMAKING                     = 'matchmaking';
    const MESSAGING                       = 'messaging';
    const MOVERS_AND_PACKERS              = 'movers_and_packers';
    const MULTI_LEVEL_MARKETING           = 'multi_level_marketing';
    const MULTIPLEX                       = 'multiplex';
    const MUSIC_STREAMING                 = 'music_streaming';
    const MUTUAL_FUND                     = 'mutual_fund';

    const NBFC                            = 'nbfc';
    const NEIGHBOURHOOD_NETWORK           = 'neighbourhood_network';
    const NEWS                            = 'news';

    const OFFICE_SUPPLIES                 = 'office_supplies';
    const ONLINE_CASINO                   = 'online_casino';
    const ONLINE_FOOD_ORDERING            = 'online_food_ordering';
    const OTA                             = 'ota';

    const PAAS                            = 'paas';
    const PENSION_FUND                    = 'pension_fund';
    const PERSONAL                        = 'personal';
    const PET_PRODUCTS                    = 'pet_products';
    const PHARMACY                        = 'pharmacy';
    const PROFESSIONAL_COURSES            = 'professional_courses';
    const PROFESSIONAL_NETWORK            = 'professional_network';

    const REALESTATE_CLASSIFIEDS          = 'realestate_classifieds';
    const RELIGIOUS                       = 'religious';
    const RELIGIOUS_PRODUCTS              = 'religious_products';
    const RENTAL                          = 'rental';
    const REPAIR_AND_CLEANING             = 'repair_and_cleaning';
    const RESTAURANT                      = 'restaurant';
    const RESTAURANT_SEARCH_AND_BOOKING   = 'restaurant_search_and_booking';
    const RWA                             = 'rwa';

    const SAAS                            = 'saas';
    const SCHOOLS                         = 'schools';
    const SECURITIES                      = 'securities';
    const SERVICE_CENTRE                  = 'service_centre';
    const SERVICES_CLASSIFIEDS            = 'services_classifieds';
    const SEXUAL_WELLNESS_PRODUCT         = 'sexual_wellness_product';
    const SOCIAL_NETWORK                  = 'social_network';
    const SPACE_RENTAL                    = 'space_rental';
    const SPORTS_PRODUCTS                 = 'sports_products';
    const STATE                           = 'state';

    const TECHNICAL_SUPPORT               = 'technical_support';
    const TELECOM                         = 'telecom';
    const TICKETING                       = 'ticketing';
    const TOBACCO                         = 'tobacco';
    const TRADING                         = 'trading';
    const TRAIN_AND_METRO                 = 'train_and_metro';
    const TRAVEL_AGENCY                   = 'travel_agency';

    const UNIVERSITY                      = 'university';

    const VIDEO_ON_DEMAND                 = 'video_on_demand';

    const WAREHOUSING                     = 'warehousing';
    const WATER                           = 'water';
    const WEAPONS_AND_AMMUNITIONS         = 'weapons_and_ammunitions';
    const WEB_DEVELOPMENT                 = 'web_development';
    const WHOLESALE                       = 'wholesale';

    // Business Subcategory Descriptions
    const DESCRIPTIONS = [
        self::ACCOMMODATION                   => 'Lodging and Accommodation',
        self::ACCOUNTING                      => 'Accounting and Taxes',
        self::AD_AND_MARKETING                => 'Ad and marketing agencies',
        self::AGRICULTURE                     => 'Agricultural products',
        self::ALCOHOL                         => 'Alcoholic Beverages',
        self::ARTS_AND_COLLECTIBLES           => 'Arts, crafts and collectibles',
        self::AVIATION                        => 'Aviation',
        self::BABY_PRODUCTS                   => 'Baby Care and Toys',
        self::BETTING                         => 'Betting',
        self::BILL_AND_RECHARGE_AGGREGATORS   => 'Bill Payment and Recharge Aggregators',
        self::BOOKS                           => 'Books and Publications',
        self::BROADBAND                       => 'Broadband',
        self::BUS                             => 'Bus ticketing',
        self::CABLE                           => 'Cable operator',
        self::CAB_HAILING                     => 'Cab/auto hailing',
        self::CATERING                        => 'Catering Services',
        self::CENTRAL                         => 'Central Department',
        self::CHARITY                         => 'Charity',
        self::CLINIC                          => 'Clinic',
        self::COACHING                        => 'Coaching Institute',
        self::COLLEGE                         => 'College',
        self::COMMODITIES                     => 'Commodities',
        self::CONSULTING                      => 'Consulting Services',
        self::CONSULTING_AND_OUTSOURCING      => 'Consulting and Outsourcing',
        self::CONTENT_AND_PUBLISHING          => 'Content and Publishing',
        self::COOPERATIVES                    => 'Cooperatives',
        self::COUPONS                         => 'Coupons and deals',
        self::COURIER                         => 'Courier Shipping',
        self::COWORKING                       => 'Co-working spaces',
        self::CROWDFUNDING                    => 'Crowdfunding Platform',
        self::CRYPTOCURRENCY                  => 'Cryptocurrency',
        self::CRYPTO_MACHINERY                => 'Crypto Machinery',
        self::DAY_CARE                        => 'Pre-School/Day Care',
        self::DEVELOPER                       => 'Developer',
        self::DIETICIAN                       => 'Dietician/Diet Services',
        self::DISTANCE_LEARNING               => 'Distance Learning',
        self::DISTRIBUTION_MANAGEMENT         => 'Distribution Management',
        self::DROP_SHIPPING                   => 'Dropshipping',
        self::DTH                             => 'DTH',
        self::ECOMMERCE_MARKETPLACE           => 'Horizontal Commerce/Marketplace',
        self::EDUCATIONAL                     => 'Educational',
        self::ELEARNING                       => 'E-Learning',
        self::ELECTRICITY                     => 'Electricity',
        self::ELECTRONICS_AND_FURNITURE       => 'Electronics and Furniture',
        self::END_TO_END_LOGISTICS            => 'End-to-end logistics',
        self::ESPORTS                         => 'E-sports',
        self::EVENT_PLANNING                  => 'Event planning services',
        self::FACILITY_MANAGEMENT             => 'Facility Management Company',
        self::FANTASY_SPORTS                  => 'Fantasy Sports',
        self::FASHION_AND_LIFESTYLE           => 'Fashion and Lifestyle',
        self::FINANCIAL_ADVISOR               => 'Financial and Investment Advisors/Financial Advisor',
        self::FITNESS                         => 'Gym and Fitness',
        self::FOOD_COURT                      => 'Food Courts/Corporate Cafetaria',
        self::FOREX                           => 'Forex',
        self::FREIGHT                         => 'Freight Consolidation/Management',
        self::GAME_DEVELOPER                  => 'Game developer and publisher',
        self::GAMING_MARKETPLACE              => 'Game distributor/Marketplace',
        self::GAS                             => 'Gas',
        self::GET_RICH_SCHEMES                => 'Get Rich Schemes',
        self::GIFTING                         => 'Flowers and Gifts',
        self::GROCERY                         => 'Grocery',
        self::HEALTH_COACHING                 => 'Health and Lifestyle Coaching',
        self::HEALTH_PRODUCTS                 => 'Health Products',
        self::HEALTHCARE_MARKETPLACE          => 'Marketplace/Aggregator',
        self::HOSPITAL                        => 'Hospital',
        self::IAAS                            => 'Infrastructure as a service',
        self::INSURANCE                       => 'Insurance',
        self::INTERIOR_DESIGN_AND_ARCHITECT   => 'Interior Designing and Architect',
        self::INTERNET_PROVIDER               => 'Internet service provider',
        self::LAB                             => 'Lab',
        self::LEGAL                           => 'Legal Services',
        self::LENDING                         => 'Lending',
        self::MATCHMAKING                     => 'Dating and Matrimony platforms',
        self::MESSAGING                       => 'Messaging and Communication',
        self::MOVERS_AND_PACKERS              => 'Movers and Packers',
        self::MULTI_LEVEL_MARKETING           => 'Multi-level Marketing',
        self::MULTIPLEX                       => 'Multiplexes',
        self::MUSIC_STREAMING                 => 'Music streaming services',
        self::MUTUAL_FUND                     => 'Mutual Fund',
        self::NBFC                            => 'NBFC',
        self::NEIGHBOURHOOD_NETWORK           => 'Local/Neighbourhood network',
        self::NEWS                            => 'News',
        self::OFFICE_SUPPLIES                 => 'Office Supplies',
        self::ONLINE_CASINO                   => 'Online Casino',
        self::ONLINE_FOOD_ORDERING            => 'Online Food Ordering',
        self::OTA                             => 'OTA',
        self::PAAS                            => 'Platform as a service',
        self::PENSION_FUND                    => 'Pension Fund',
        self::PERSONAL                        => 'Personal',
        self::PROFESSIONAL_COURSES            => 'Professional Courses',
        self::PET_PRODUCTS                    => 'Pet Care and Supplies',
        self::PHARMACY                        => 'Pharmacy',
        self::PROFESSIONAL_NETWORK            => 'Professional Network',
        self::REALESTATE_CLASSIFIEDS          => 'Real estate classifieds',
        self::RELIGIOUS                       => 'Religious',
        self::RELIGIOUS_PRODUCTS              => 'Religious products',
        self::RENTAL                          => 'Product Rental',
        self::REPAIR_AND_CLEANING             => 'Repair and cleaning services',
        self::RESTAURANT                      => 'Restaurants',
        self::RESTAURANT_SEARCH_AND_BOOKING   => 'Restaurant search and reservations',
        self::RWA                             => 'RWA',
        self::SAAS                            => 'SaaS (Software as a service)',
        self::SCHOOLS                         => 'Schools',
        self::SECURITIES                      => 'Securities',
        self::SERVICE_CENTRE                  => 'Service Centre',
        self::SERVICES_CLASSIFIEDS            => 'Services Classifieds',
        self::SEXUAL_WELLNESS_PRODUCT         => 'Sexual Wellness Products',
        self::SOCIAL_NETWORK                  => 'Social Network',
        self::SPACE_RENTAL                    => 'Home or office rentals',
        self::SPORTS_PRODUCTS                 => 'Sports goods',
        self::STATE                           => 'State Department',
        self::TECHNICAL_SUPPORT               => 'Technical Support',
        self::TELECOM                         => 'Telecom Service Provider',
        self::TICKETING                       => 'Events and movie ticketing',
        self::TOBACCO                         => 'Tobacco',
        self::TRADING                         => 'Stock Brokerage and Trading',
        self::TRAIN_AND_METRO                 => 'Train and metro ticketing',
        self::TRAVEL_AGENCY                   => 'Tours and Travel Agency',
        self::UNIVERSITY                      => 'University',
        self::VIDEO_ON_DEMAND                 => 'Video on demand',
        self::WATER                           => 'Water',
        self::WAREHOUSING                     => 'Public/Contract Warehousing',
        self::WEAPONS_AND_AMMUNITIONS         => 'Weapons and Ammunitions',
        self::WEB_DEVELOPMENT                 => 'Web designing, development and hosting',
        self::WHOLESALE                       => 'Wholesale/Bulk trade',
    ];

    const SUB_CATEGORY_METADATA = [
        self::ACCOMMODATION => [
            self::MCC_CODE                               => 7011,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ACCOMMODATION],
            Category::CATEGORY                           => Category::HOSPITALITY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ACCOUNTING => [
            self::MCC_CODE                               => 8931,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ACCOUNTING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::AD_AND_MARKETING => [
            self::MCC_CODE                               => 7311,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::AD_AND_MARKETING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::AGRICULTURE => [
            self::MCC_CODE                               => 5193,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::AGRICULTURE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ALCOHOL => [
            self::MCC_CODE                               => 5813,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ALCOHOL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::ARTS_AND_COLLECTIBLES => [
            self::MCC_CODE                               => 5971,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ARTS_AND_COLLECTIBLES],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::AVIATION => [
            self::MCC_CODE                               => 4511,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::AVIATION],
            Category::CATEGORY                           => Category::TRAVEL_AGENCY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::BABY_PRODUCTS => [
            self::MCC_CODE                               => 5945,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BABY_PRODUCTS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::BETTING => [
            self::MCC_CODE                               => 7801,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BETTING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::BILL_AND_RECHARGE_AGGREGATORS => [
            self::MCC_CODE                               => 4814,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BILL_AND_RECHARGE_AGGREGATORS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::BOOKS => [
            self::MCC_CODE                               => 5942,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BOOKS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::BROADBAND => [
            self::MCC_CODE                               => 4899,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BROADBAND],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::BUS => [
            self::MCC_CODE                               => 4131,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::BUS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CABLE => [
            self::MCC_CODE                               => 4899,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CABLE],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CAB_HAILING => [
            self::MCC_CODE                               => 4121,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CAB_HAILING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CATERING => [
            self::MCC_CODE                               => 5811,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CATERING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CENTRAL => [
            self::MCC_CODE                               => 9399,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CENTRAL],
            Category::CATEGORY                           => Category::GOVERNMENT,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CHARITY => [
            self::MCC_CODE                               => 8398,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CHARITY],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::CLINIC => [
            self::MCC_CODE                               => 8062,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CLINIC],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COACHING => [
            self::MCC_CODE                               => 8299,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COACHING],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COLLEGE => [
            self::MCC_CODE                               => 8220,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COLLEGE],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COMMODITIES => [
            self::MCC_CODE                               => 6211,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COMMODITIES],
            Category::CATEGORY                           => Category::SECURITIES,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::CONSULTING => [
            self::MCC_CODE                               => 7392,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CONSULTING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::CONSULTING_AND_OUTSOURCING => [
            self::MCC_CODE                               => 7392,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CONSULTING_AND_OUTSOURCING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::CONTENT_AND_PUBLISHING => [
            self::MCC_CODE                               => 2741,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CONTENT_AND_PUBLISHING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COOPERATIVES => [
            self::MCC_CODE                               => 6012,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COOPERATIVES],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::COUPONS => [
            self::MCC_CODE                               => 7311,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COUPONS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COURIER => [
            self::MCC_CODE                               => 4215,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COURIER],
            Category::CATEGORY                           => Category::LOGISTICS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::COWORKING => [
            self::MCC_CODE                               => 6513,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::COWORKING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::CROWDFUNDING => [
            self::MCC_CODE                               => 6050,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CROWDFUNDING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::CRYPTOCURRENCY => [
            self::MCC_CODE                               => 6051,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CRYPTOCURRENCY],
            Category::CATEGORY                           => Category::CRYPTOCURRENCY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::CRYPTO_MACHINERY => [
            self::MCC_CODE                               => 5999,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::CRYPTO_MACHINERY],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::DEVELOPER => [
            self::MCC_CODE                               => 6513,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DEVELOPER],
            Category::CATEGORY                           => Category::HOUSING,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::DIETICIAN => [
            self::MCC_CODE                               => 7298,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DIETICIAN],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::DISTANCE_LEARNING => [
            self::MCC_CODE                               => 8299,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DISTANCE_LEARNING],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::DISTRIBUTION_MANAGEMENT => [
            self::MCC_CODE                               => 4214,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DISTRIBUTION_MANAGEMENT],
            Category::CATEGORY                           => Category::LOGISTICS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::DROP_SHIPPING => [
            self::MCC_CODE                               => 5399,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DROP_SHIPPING],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::DTH => [
            self::MCC_CODE                               => 4899,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::DTH],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ECOMMERCE_MARKETPLACE => [
            self::MCC_CODE                               => 5399,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ECOMMERCE_MARKETPLACE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::EDUCATIONAL => [
            self::MCC_CODE                               => 8398,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::EDUCATIONAL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::ELEARNING => [
            self::MCC_CODE                               => 8299,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ELEARNING],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ELECTRICITY => [
            self::MCC_CODE                               => 4900,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ELECTRICITY],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ELECTRONICS_AND_FURNITURE => [
            self::MCC_CODE                               => 5732,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ELECTRONICS_AND_FURNITURE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::END_TO_END_LOGISTICS => [
            self::MCC_CODE                               => 4214,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::END_TO_END_LOGISTICS],
            Category::CATEGORY                           => Category::LOGISTICS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ESPORTS => [
            self::MCC_CODE                               => 5816,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ESPORTS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::EVENT_PLANNING => [
            self::MCC_CODE                               => 8999,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::EVENT_PLANNING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::FACILITY_MANAGEMENT => [
            self::MCC_CODE                               => 7349,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FACILITY_MANAGEMENT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::FANTASY_SPORTS => [
            self::MCC_CODE                               => 5816,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FANTASY_SPORTS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::FASHION_AND_LIFESTYLE => [
            self::MCC_CODE                               => 5691,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FASHION_AND_LIFESTYLE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::FINANCIAL_ADVISOR => [
            self::MCC_CODE                               => 8931,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FINANCIAL_ADVISOR],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::FITNESS => [
            self::MCC_CODE                               => 7298,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FITNESS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::FOOD_COURT => [
            self::MCC_CODE                               => 5814,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FOOD_COURT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::FOREX => [
            self::MCC_CODE                               => 6010,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FOREX],
            Category::CATEGORY                           => Category::FOREX,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::FREIGHT => [
            self::MCC_CODE                               => 4214,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::FREIGHT],
            Category::CATEGORY                           => Category::LOGISTICS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::GAME_DEVELOPER => [
            self::MCC_CODE                               => 5816,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GAME_DEVELOPER],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::GAMING_MARKETPLACE => [
            self::MCC_CODE                               => 5816,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GAMING_MARKETPLACE],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::GAS => [
            self::MCC_CODE                               => 4900,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GAS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::GET_RICH_SCHEMES => [
            self::MCC_CODE                               => 7361,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GET_RICH_SCHEMES],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::GIFTING => [
            self::MCC_CODE                               => 5193,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GIFTING],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::GROCERY => [
            self::MCC_CODE                               => 5411,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::GROCERY],
            Category::CATEGORY                           => Category::GROCERY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::HEALTH_COACHING => [
            self::MCC_CODE                               => 7298,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::HEALTH_COACHING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::HEALTH_PRODUCTS => [
            self::MCC_CODE                               => 5499,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::HEALTH_PRODUCTS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::HEALTHCARE_MARKETPLACE => [
            self::MCC_CODE                               => 5399,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::HEALTHCARE_MARKETPLACE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::HOSPITAL => [
            self::MCC_CODE                               => 8062,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::HOSPITAL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::IAAS => [
            self::MCC_CODE                               => 5817,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::IAAS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::INSURANCE => [
            self::MCC_CODE                               => 6300,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::INSURANCE],
            Category::CATEGORY                           => Category::INSURANCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::INTERIOR_DESIGN_AND_ARCHITECT => [
            self::MCC_CODE                               => 8911,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::INTERIOR_DESIGN_AND_ARCHITECT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::INTERNET_PROVIDER => [
            self::MCC_CODE                               => 4816,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::INTERNET_PROVIDER],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::LAB => [
            self::MCC_CODE                               => 8071,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::LAB],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::LEGAL => [
            self::MCC_CODE                               => 8111,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::LEGAL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::LENDING => [
            self::MCC_CODE                               => 6012,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::LENDING],
            Category::CATEGORY                           => Category::LENDING,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::MATCHMAKING => [
            self::MCC_CODE                               => 7273,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MATCHMAKING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::MESSAGING => [
            self::MCC_CODE                               => 4821,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MESSAGING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::MOVERS_AND_PACKERS => [
            self::MCC_CODE                               => 4214,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MOVERS_AND_PACKERS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::MULTI_LEVEL_MARKETING => [
            self::MCC_CODE                               => 5964,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MULTI_LEVEL_MARKETING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::MULTIPLEX => [
            self::MCC_CODE                               => 7832,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MULTIPLEX],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::MUSIC_STREAMING => [
            self::MCC_CODE                               => 5815,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MUSIC_STREAMING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::MUTUAL_FUND => [
            self::MCC_CODE                               => 6211,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::MUTUAL_FUND],
            Category::CATEGORY                           => Category::MUTUAL_FUNDS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::NBFC => [
            self::MCC_CODE                               => 6012,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::NBFC],
            Category::CATEGORY                           => Category::LENDING,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::NEIGHBOURHOOD_NETWORK => [
            self::MCC_CODE                               => 8699,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::NEIGHBOURHOOD_NETWORK],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::NEWS => [
            self::MCC_CODE                               => 5994,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::NEWS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::OFFICE_SUPPLIES => [
            self::MCC_CODE                               => 5111,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::OFFICE_SUPPLIES],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::ONLINE_CASINO => [
            self::MCC_CODE                               => 7801,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ONLINE_CASINO],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::ONLINE_FOOD_ORDERING => [
            self::MCC_CODE                               => 5811,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::ONLINE_FOOD_ORDERING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::OTA => [
            self::MCC_CODE                               => 4722,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::OTA],
            Category::CATEGORY                           => Category::TRAVEL_AGENCY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::PAAS => [
            self::MCC_CODE                               => 5817,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PAAS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::PENSION_FUND => [
            self::MCC_CODE                               => 6012,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PENSION_FUND],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::PERSONAL => [
            self::MCC_CODE                               => 8398,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PERSONAL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::PROFESSIONAL_COURSES => [
            self::MCC_CODE                               => 8299,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PROFESSIONAL_COURSES],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::PET_PRODUCTS => [
            self::MCC_CODE                               => 5995,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PET_PRODUCTS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::PHARMACY => [
            self::MCC_CODE                               => 5912,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PHARMACY],
            Category::CATEGORY                           => Category::PHARMA,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::PROFESSIONAL_NETWORK => [
            self::MCC_CODE                               => 8699,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::PROFESSIONAL_NETWORK],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::REALESTATE_CLASSIFIEDS => [
            self::MCC_CODE                               => 6513,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::REALESTATE_CLASSIFIEDS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::RELIGIOUS => [
            self::MCC_CODE                               => 8661,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RELIGIOUS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::RELIGIOUS_PRODUCTS => [
            self::MCC_CODE                               => 5973,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RELIGIOUS_PRODUCTS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::RENTAL => [
            self::MCC_CODE                               => 7394,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RENTAL],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::REPAIR_AND_CLEANING => [
            self::MCC_CODE                               => 7531,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::REPAIR_AND_CLEANING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::RESTAURANT => [
            self::MCC_CODE                               => 5812,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RESTAURANT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::RESTAURANT_SEARCH_AND_BOOKING => [
            self::MCC_CODE                               => 7299,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RESTAURANT_SEARCH_AND_BOOKING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::RWA => [
            self::MCC_CODE                               => 7349,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::RWA],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::SAAS => [
            self::MCC_CODE                               => 5817,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SAAS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::SCHOOLS => [
            self::MCC_CODE                               => 8211,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SCHOOLS],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::SECURITIES => [
            self::MCC_CODE                               => 6211,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SECURITIES],
            Category::CATEGORY                           => Category::SECURITIES,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::SERVICE_CENTRE => [
            self::MCC_CODE                               => 5511,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SERVICE_CENTRE],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::SERVICES_CLASSIFIEDS => [
            self::MCC_CODE                               => 7311,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SERVICES_CLASSIFIEDS],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::SEXUAL_WELLNESS_PRODUCT =>[
            self::MCC_CODE                               => 5999,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SEXUAL_WELLNESS_PRODUCT],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::SOCIAL_NETWORK => [
            self::MCC_CODE                               => 8641,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SOCIAL_NETWORK],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::SPACE_RENTAL => [
            self::MCC_CODE                               => 6513,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SPACE_RENTAL],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::SPORTS_PRODUCTS => [
            self::MCC_CODE                               => 5941,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::SPORTS_PRODUCTS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::STATE => [
            self::MCC_CODE                               => 9399,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::STATE],
            Category::CATEGORY                           => Category::GOVERNMENT,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::TECHNICAL_SUPPORT => [
            self::MCC_CODE                               => 7379,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TECHNICAL_SUPPORT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::TELECOM => [
            self::MCC_CODE                               => 4814,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TELECOM],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::TICKETING => [
            self::MCC_CODE                               => 7832,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TICKETING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::TOBACCO => [
            self::MCC_CODE                               => 5993,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TOBACCO],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::TRADING => [
            self::MCC_CODE                               => 6211,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TRADING],
            Category::CATEGORY                           => Category::SECURITIES,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::TRAIN_AND_METRO => [
            self::MCC_CODE                               => 4112,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TRAIN_AND_METRO],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::TRAVEL_AGENCY => [
            self::MCC_CODE                               => 4722,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::TRAVEL_AGENCY],
            Category::CATEGORY                           => Category::TRAVEL_AGENCY,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::UNIVERSITY => [
            self::MCC_CODE                               => 8220,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::UNIVERSITY],
            Category::CATEGORY                           => Category::PVT_EDUCATION,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::VIDEO_ON_DEMAND => [
            self::MCC_CODE                               => 5815,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::VIDEO_ON_DEMAND],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::WATER => [
            self::MCC_CODE                               => 4900,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::WATER],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::WAREHOUSING => [
            self::MCC_CODE                               => 4225,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::WAREHOUSING],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
        self::WEAPONS_AND_AMMUNITIONS => [
            self::MCC_CODE                               => 5999,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::WEAPONS_AND_AMMUNITIONS],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::BLACKLISTED,
        ],
        self::WEB_DEVELOPMENT => [
            self::MCC_CODE                               => 7372,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::WEB_DEVELOPMENT],
            Category::CATEGORY                           => Category::OTHERS,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::GREYLISTED,
        ],
        self::WHOLESALE => [
            self::MCC_CODE                               => 5300,
            self::DESCRIPTION                            => self::DESCRIPTIONS[self::WHOLESALE],
            Category::CATEGORY                           => Category::ECOMMERCE,
            ActivationCategory::ACTIVATION_CATEGORY      => ActivationCategory::WHITELISTED,
        ],
    ];

    /**
     * This function checks if the given subcategory is valid
     *
     * @param string $subcategory
     * @return boolean true/false
     */
    public static function isValidSubcategory($subcategory) : bool
    {   //
        // return false if subcategory is `descriptions`, mcc_code, description  or
        // sub_categories_metadata
        //
        if (in_array(strtolower($subcategory), self::INVALID_SUB_CATEGORIES, true))
        {
            return false;
        }

        $key = __CLASS__ . '::' . strtoupper($subcategory);

        return ((defined($key) === true) and (constant($key) === $subcategory));
    }
}
