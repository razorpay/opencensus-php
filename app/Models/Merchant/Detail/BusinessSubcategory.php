<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Exception;

class BusinessSubcategory
{
    // Business Subcategory Codes
    const ACCOMMODATION                 = 'accommodation';
    const ACCOUNTING                    = 'accounting';
    const AD_AND_MARKETING              = 'ad_and_marketing';
    const AGRICULTURE                   = 'agriculture';
    const ALCOHOL                       = 'alcohol';
    const ARTS_AND_COLLECTIBLES         = 'arts_and_collectibles';
    const AVIATION                      = 'aviation';

    const BABY_PRODUCTS                 = 'baby_products';
    const BILL_AND_RECHARGE_AGGREGATORS = 'bill_and_recharge_aggregators';
    const BOOKS                         = 'books';
    const BROADBAND                     = 'broadband';
    const BUS                           = 'bus';

    const CABLE                         = 'cable';
    const CAB_HAILING                   = 'cab_hailing';
    const CATERING                      = 'catering';
    const CENTRAL                       = 'central';
    const CHARITY                       = 'charity';
    const CLASSIFIEDS                   = 'classifieds';
    const CLINIC                        = 'clinic';
    const COACHING                      = 'coaching';
    const COLLEGE                       = 'college';
    const COMMODITIES                   = 'commodities';
    const CONSULTING                    = 'consulting';
    const CONSULTING_AND_OUTSOURCING    = 'consulting_and_outsourcing';
    const CONTENT_AND_PUBLISHING        = 'content_and_publishing';
    const COOPERATIVES                  = 'cooperatives';
    const COUPONS                       = 'coupons';
    const COURIER                       = 'courier';
    const COWORKING                     = 'coworking';
    const CROWDFUNDING                  = 'crowdfunding';
    const CRYPTOCURRENCY                = 'cryptocurrency';

    const DAY_CARE                      = 'day_care';
    const DEVELOPER                     = 'developer';
    const DIETICIAN                     = 'dietician';
    const DISTANCE_LEARNING             = 'distance_learning';
    const DISTRIBUTION_MANAGEMENT       = 'distribution';
    const DTH                           = 'dth';

    const EDUCATIONAL                   = 'educational';
    const ELEARNING                     = 'elearning';
    const ELECTRICITY                   = 'electricity';
    const ELECTRONICS_AND_FURNITURE     = 'electronics_and_furniture';
    const END_TO_END_LOGISTICS          = 'end_to_end_logistics';
    const ESPORTS                       = 'esports';
    const EVENT_PLANNING                = 'event_planning';

    const FACILITY_MANAGEMENT           = 'facility_management';
    const FANTASY_SPORTS                = 'fantasy_sports';
    const FASHION_AND_LIFESTYLE         = 'fashion_and_lifestyle';
    const FINANCIAL_ADVISOR             = 'financial_advisor';
    const FITNESS                       = 'fitness';
    const FOOD_COURT                    = 'food_court';
    const FOREX                         = 'forex';
    const FREIGHT                       = 'freight';

    const GAME_DEVELOPER                = 'game_developer';
    const GAS                           = 'gas';
    const GIFTING                       = 'gifting';
    const GROCERY                       = 'grocery';

    const HEALTH_COACHING               = 'health_coaching';
    const HEALTH_PRODUCTS               = 'health_products';
    const HOSPITAL                      = 'hospital';

    const IAAS                          = 'iaas';
    const INSURANCE                     = 'insurance';
    const INTERIOR_DESIGN_AND_ARCHITECT = 'interior_design_and_architect';
    const INTERNET_PROVIDER             = 'internet_provider';

    const LAB                           = 'lab';
    const LEGAL                         = 'legal';
    const LENDING                       = 'lending';

    const MARKETPLACE                   = 'marketplace';
    const MATCHMAKING                   = 'matchmaking';
    const MESSAGING                     = 'messaging';
    const MOVERS_AND_PACKERS            = 'movers_and_packers';
    const MULTIPLEX                     = 'multiplex';
    const MUSIC_STREAMING               = 'music_streaming';
    const MUTUAL_FUND                   = 'mutual_fund';

    const NBFC                          = 'nbfc';
    const NEIGHBOURHOOD_NETWORK         = 'neighbourhood_network';
    const NEWS                          = 'news';

    const OFFICE_SUPPLIES               = 'office_supplies';
    const ONLINE_CASINO                 = 'online_casino';
    const ONLINE_FOOD_ORDERING          = 'online_food_ordering';
    const OTA                           = 'ota';

    const PAAS                          = 'paas';
    const PENSION_FUND                  = 'pension_fund';
    const PERSONAL                      = 'personal';
    const PET_PRODUCTS                  = 'pet_products';
    const PHARMACY                      = 'pharmacy';
    const PROFESSIONAL_COURSES          = 'professional_courses';
    const PROFESSIONAL_NETWORK          = 'professional_network';

    const RELIGIOUS                     = 'religious';
    const RELIGIOUS_PRODUCTS            = 'religious_products';
    const RENTAL                        = 'rental';
    const REPAIR_AND_CLEANING           = 'repair_and_cleaning';
    const RESTAURANT                    = 'restaurant';
    const RESTAURANT_SEARCH_AND_BOOKING = 'restaurant_search_and_booking';
    const RWA                           = 'rwa';

    const SAAS                          = 'saas';
    const SCHOOLS                       = 'schools';
    const SECURITIES                    = 'securities';
    const SERVICE_CENTRE                = 'service_centre';
    const SOCIAL_NETWORK                = 'social_network';
    const SPACE_RENTAL                  = 'space_rental';
    const SPORTS_PRODUCTS               = 'sports_products';
    const STATE                         = 'state';

    const TELECOM                       = 'telecom';
    const TICKETING                     = 'ticketing';
    const TRADING                       = 'trading';
    const TRAIN_AND_METRO               = 'train_and_metro';
    const TRAVEL_AGENCY                 = 'travel_agency';

    const UNIVERSITY                    = 'university';

    const VIDEO_ON_DEMAND               = 'video_on_demand';

    const WAREHOUSING                   = 'warehousing';
    const WATER                         = 'water';
    const WEB_DEVELOPMENT               = 'web_development';
    const WHOLESALE                     = 'wholesale';

    // Business Subcategory Descriptions
    const ACCOMMODATION_DESCRIPTION                 = 'Lodging and Accommodation';
    const ACCOUNTING_DESCRIPTION                    = 'Accounting and Taxes';
    const AD_AND_MARKETING_DESCRIPTION              = 'Ad and marketing agencies';
    const AGRICULTURE_DESCRIPTION                   = 'Agricultural products';
    const ALCOHOL_DESCRIPTION                       = 'Alcoholic Beverages';
    const ARTS_AND_COLLECTIBLES_DESCRIPTION         = 'Arts, crafts and collectibles';
    const AVIATION_DESCRIPTION                      = 'Aviation';

    const BABY_PRODUCTS_DESCRIPTION                 = 'Baby Care and Toys';
    const BILL_AND_RECHARGE_AGGREGATORS_DESCRIPTION = 'Bill Payment and Recharge Aggregators';
    const BOOKS_DESCRIPTION                         = 'Books and Publications';
    const BROADBAND_DESCRIPTION                     = 'Broadband';
    const BUS_DESCRIPTION                           = 'Bus ticketing';

    const CABLE_DESCRIPTION                         = 'Cable operator';
    const CAB_HAILING_DESCRIPTION                   = 'Cab/auto hailing';
    const CATERING_DESCRIPTION                      = 'Catering Services';
    const CENTRAL_DESCRIPTION                       = 'Central Department';
    const CHARITY_DESCRIPTION                       = 'Charity';
    const CLASSIFIEDS_DESCRIPTION                   = 'Services Classifieds';
    const CLINIC_DESCRIPTION                        = 'Clinic';
    const COACHING_DESCRIPTION                      = 'Coaching Institute';
    const COLLEGE_DESCRIPTION                       = 'College';
    const COMMODITIES_DESCRIPTION                   = 'Commodities';
    const CONSULTING_DESCRIPTION                    = 'Consulting Services';
    const CONSULTING_AND_OUTSOURCING_DESCRIPTION    = 'Consulting and Outsourcing';
    const CONTENT_AND_PUBLISHING_DESCRIPTION        = 'Content and Publishing';
    const COOPERATIVES_DESCRIPTION                  = 'Cooperatives';
    const COUPONS_DESCRIPTION                       = 'Coupons and deals';
    const COURIER_DESCRIPTION                       = 'Courier Shipping';
    const COWORKING_DESCRIPTION                     = 'Co-working spaces';
    const CROWDFUNDING_DESCRIPTION                  = 'Crowdfunding Platform';
    const CRYPTOCURRENCY_DESCRIPTION                = 'Cryptocurrency';

    const DAY_CARE_DESCRIPTION                      = 'Pre-School/Day Care';
    const DEVELOPER_DESCRIPTION                     = 'Developer';
    const DIETICIAN_DESCRIPTION                     = 'Dietician/Diet Services';
    const DISTANCE_LEARNING_DESCRIPTION             = 'Distance Learning';
    const DISTRIBUTION_MANAGEMENT_DESCRIPTION       = 'Distribution Management';
    const DTH_DESCRIPTION                           = 'DTH';

    const EDUCATIONAL_DESCRIPTION                   = 'Educational';
    const ELEARNING_DESCRIPTION                     = 'E-Learning';
    const ELECTRICITY_DESCRIPTION                   = 'Electricity';
    const ELECTRONICS_AND_FURNITURE_DESCRIPTION     = 'Electronics and Furniture';
    const END_TO_END_LOGISTICS_DESCRIPTION          = 'End-to-end logistics';
    const ESPORTS_DESCRIPTION                       = 'E-sports';
    const EVENT_PLANNING_DESCRIPTION                = 'Event planning services';

    const FACILITY_MANAGEMENT_DESCRIPTION           = 'Facility Management Company';
    const FANTASY_SPORTS_DESCRIPTION                = 'Fantasy Sports';
    const FASHION_AND_LIFESTYLE_DESCRIPTION         = 'Fashion and Lifestyle';
    const FINANCIAL_ADVISOR_DESCRIPTION             = 'Financial and Investment Advisors/Financial Advisor';
    const FITNESS_DESCRIPTION                       = 'Gym and Fitness';
    const FOOD_COURT_DESCRIPTION                    = 'Food Courts/Corporate Cafetaria';
    const FOREX_DESCRIPTION                         = 'Forex';
    const FREIGHT_DESCRIPTION                       = 'Freight Consolidation/Management';

    const GAME_DEVELOPER_DESCRIPTION                = 'Game developer and publisher';
    const GAME_DISTRIBUTOR_DESCRIPTION              = 'Game distributor/Marketplace';
    const GAS_DESCRIPTION                           = 'Gas';
    const GIFTING_DESCRIPTION                       = 'Flowers and Gifts';
    const GROCERY_DESCRIPTION                       = 'Grocery';

    const HEALTH_COACHING_DESCRIPTION               = 'Health and Lifestyle Coaching';
    const HEALTH_PRODUCTS_DESCRIPTION               = 'Health Products';
    const HORIZONTAL_COMMERCE_DESCRIPTION           = 'Horizontal Commerce/Marketplace';
    const HOSPITAL_DESCRIPTION                      = 'Hospital';

    const IAAS_DESCRIPTION                          = 'Infrastructure as a service';
    const INSURANCE_DESCRIPTION                     = 'Insurance';
    const INTERIOR_DESIGN_AND_ARCHITECT_DESCRIPTION = 'Interior Designing and Architect';
    const INTERNET_PROVIDER_DESCRIPTION             = 'Internet service provider';

    const LAB_DESCRIPTION                           = 'Lab';
    const LEGAL_DESCRIPTION                         = 'Legal Services';
    const LENDING_DESCRIPTION                       = 'Lending';

    const MARKETPLACE_DESCRIPTION                   = 'Marketplace/Aggregator';
    const MATCHMAKING_DESCRIPTION                   = 'Dating and Matrimony platforms';
    const MESSAGING_DESCRIPTION                     = 'Messaging and Communication';
    const MOVERS_AND_PACKERS_DESCRIPTION            = 'Movers and Packers';
    const MULTIPLEX_DESCRIPTION                     = 'Multiplexes';
    const MUSIC_STREAMING_DESCRIPTION               = 'Music streaming services';
    const MUTUAL_FUND_DESCRIPTION                   = 'Mutual Fund';

    const NBFC_DESCRIPTION                          = 'NBFC';
    const NEIGHBOURHOOD_NETWORK_DESCRIPTION         = 'Local/Neighbourhood network';
    const NEWS_DESCRIPTION                          = 'News';

    const OFFICE_SUPPLIES_DESCRIPTION               = 'Office Supplies';
    const ONLINE_CASINO_DESCRIPTION                 = 'Online Casino';
    const ONLINE_FOOD_ORDERING_DESCRIPTION          = 'Online Food Ordering';
    const OTA_DESCRIPTION                           = 'OTA';

    const PAAS_DESCRIPTION                          = 'Platform as a service';
    const PENSION_FUND_DESCRIPTION                  = 'Pension Fund';
    const PERSONAL_DESCRIPTION                      = 'Personal';
    const PROFESSIONAL_COURSES_DESCRIPTION          = 'Professional Courses';
    const PET_PRODUCTS_DESCRIPTION                  = 'Pet Care and Supplies';
    const PHARMACY_DESCRIPTION                      = 'Pharmacy';
    const PROFESSIONAL_NETWORK_DESCRIPTION          = 'Professional Network';

    const REAL_ESTATE_DESCRIPTION                   = 'Real estate classifieds';
    const RELIGIOUS_DESCRIPTION                     = 'Religious';
    const RELIGIOUS_PRODUCTS_DESCRIPTION            = 'Religious products';
    const RENTAL_DESCRIPTION                        = 'Product Rental';
    const REPAIR_AND_CLEANING_DESCRIPTION           = 'Repair and cleaning services';
    const RESTAURANT_DESCRIPTION                    = 'Restaurants';
    const RESTAURANT_SEARCH_AND_BOOKING_DESCRIPTION = 'Restaurant search and reservations';
    const RWA_DESCRIPTION                           = 'RWA';

    const SAAS_DESCRIPTION                          = 'SaaS (Software as a service)';
    const SCHOOLS_DESCRIPTION                       = 'Schools';
    const SECURITIES_DESCRIPTION                    = 'Securities';
    const SERVICE_CENTRE_DESCRIPTION                = 'Service Centre';
    const SOCIAL_NETWORK_DESCRIPTION                = 'Social Network';
    const SPACE_RENTAL_DESCRIPTION                  = 'Home or office rentals';
    const SPORTS_PRODUCTS_DESCRIPTION               = 'Sports goods';
    const STATE_DESCRIPTION                         = 'State Department';

    const TELECOM_DESCRIPTION                       = 'Telecom Service Provider';
    const TICKETING_DESCRIPTION                     = 'Events and movie ticketing';
    const TRADING_DESCRIPTION                       = 'Stock Brokerage and Trading';
    const TRAIN_AND_METRO_DESCRIPTION               = 'Train and metro ticketing';
    const TRAVEL_AGENCY_DESCRIPTION                 = 'Tours and Travel Agency';

    const UNIVERSITY_DESCRIPTION                    = 'University';

    const VIDEO_ON_DEMAND_DESCRIPTION               = 'Video on demand';

    const WATER_DESCRIPTION                         = 'Water';
    const WAREHOUSING_DESCRIPTION                   = 'Public/Contract Warehousing';
    const WEB_DEVELOPMENT_DESCRIPTION               = 'Web designing, development and hosting';
    const WHOLESALE_DESCRIPTION                     = 'Wholesale/Bulk trade';
}
