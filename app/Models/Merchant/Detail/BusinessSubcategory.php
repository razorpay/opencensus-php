<?php

namespace RZP\Models\Merchant\Detail;

class BusinessSubcategory
{
    // Business Subcategory Codes
    const ACCOMMODATION                   = 'accommodation';
    const ACCOUNTING                      = 'accounting';
    const AD_AND_MARKETING                = 'ad_and_marketing';
    const AGRICULTURE                     = 'agriculture';
    const ALCOHOL                         = 'alcohol';
    const ARTS_AND_COLLECTIBLES           = 'arts_and_collectibles';
    const AVIATION                        = 'aviation';

    const BABY_PRODUCTS                   = 'baby_products';
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

    const DAY_CARE                        = 'day_care';
    const DEVELOPER                       = 'developer';
    const DIETICIAN                       = 'dietician';
    const DISTANCE_LEARNING               = 'distance_learning';
    const DISTRIBUTION_MANAGEMENT         = 'distribution';
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
    const SOCIAL_NETWORK                  = 'social_network';
    const SPACE_RENTAL                    = 'space_rental';
    const SPORTS_PRODUCTS                 = 'sports_products';
    const STATE                           = 'state';

    const TELECOM                         = 'telecom';
    const TICKETING                       = 'ticketing';
    const TRADING                         = 'trading';
    const TRAIN_AND_METRO                 = 'train_and_metro';
    const TRAVEL_AGENCY                   = 'travel_agency';

    const UNIVERSITY                      = 'university';

    const VIDEO_ON_DEMAND                 = 'video_on_demand';

    const WAREHOUSING                     = 'warehousing';
    const WATER                           = 'water';
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
        self::DAY_CARE                        => 'Pre-School/Day Care',
        self::DEVELOPER                       => 'Developer',
        self::DIETICIAN                       => 'Dietician/Diet Services',
        self::DISTANCE_LEARNING               => 'Distance Learning',
        self::DISTRIBUTION_MANAGEMENT         => 'Distribution Management',
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
        self::SOCIAL_NETWORK                  => 'Social Network',
        self::SPACE_RENTAL                    => 'Home or office rentals',
        self::SPORTS_PRODUCTS                 => 'Sports goods',
        self::STATE                           => 'State Department',
        self::TELECOM                         => 'Telecom Service Provider',
        self::TICKETING                       => 'Events and movie ticketing',
        self::TRADING                         => 'Stock Brokerage and Trading',
        self::TRAIN_AND_METRO                 => 'Train and metro ticketing',
        self::TRAVEL_AGENCY                   => 'Tours and Travel Agency',
        self::UNIVERSITY                      => 'University',
        self::VIDEO_ON_DEMAND                 => 'Video on demand',
        self::WATER                           => 'Water',
        self::WAREHOUSING                     => 'Public/Contract Warehousing',
        self::WEB_DEVELOPMENT                 => 'Web designing, development and hosting',
        self::WHOLESALE                       => 'Wholesale/Bulk trade',
    ];
}
