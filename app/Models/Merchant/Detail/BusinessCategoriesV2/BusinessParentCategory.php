<?php

namespace RZP\Models\Merchant\Detail\BusinessCategoriesV2;

use RZP\Models\Merchant\Detail\BusinessCategoriesV2\BusinessCategory as Category;

class BusinessParentCategory
{
    const CODE                      = 'code';
    const DESCRIPTION               = 'description';
    const CATEGORIES                = 'categories';
    const DISPLAY_ORDER             = 'display_order';
    const PARENT_CATEGORY_NAME      = 'parent_category_name';
    const PARENT_CATEGORY_VALUE     = 'parent_category_value';

    const RETAIL_STORE                          = 'retail_store';
    const ONLINE_STORE_MARKETPLACE              = 'online_store_marketplace';
    const EDUCATION                             = 'education';
    const SERVICE_PROVIDER                      = 'service_provider';
    const FINANCIAL_SERVICES                    = 'financial_services';
    const FOOD                                  = 'food';
    const INFLUENCER_ARTIST_CONTENT_CREATOR     = 'influencer_artist_content_creator';
    const MEDIA_AND_ENTERTAINMENT               = 'media_and_entertainment';
    const TECH_SERVICE_AND_FREELANCER           = 'tech_service_and_freelancer';
    const TECH_PRODUCT                          = 'tech_product';
    const HEALTHCARE_WELLNESS_FITNESS           = 'healthcare_wellness_fitness';
    const NOT_FOR_PROFIT                        = 'not_for_profit';
    const GOVERNMENT                            = 'government';
    const UTILITIES_BILLS_PAYMENTS              = 'utilities_bills_payments';
    const HOUSING                               = 'housing';
    const TRAVEL_AND_TRANSPORT                  = 'travel_and_transport';
    const SOCIAL                                = 'social';
    const OTHERS                                = 'others';

    // Business L0-Category Descriptions
    const DESCRIPTIONS = [
        self::RETAIL_STORE                          =>  'Retail Store',
        self::ONLINE_STORE_MARKETPLACE              =>  'Online Store / Marketplace',
        self::EDUCATION                             =>  'Education',
        self::SERVICE_PROVIDER                      =>  'Service Provider',
        self::FINANCIAL_SERVICES                    =>  'Financial Services',
        self::FOOD                                  =>  'Food & Beverages',
        self::INFLUENCER_ARTIST_CONTENT_CREATOR     =>  'Influencer / Artist / Content Creator',
        self::MEDIA_AND_ENTERTAINMENT               =>  'Media/ Entertainment / Events',
        self::TECH_SERVICE_AND_FREELANCER           =>  'Tech Services / Tech Freelancer',
        self::TECH_PRODUCT                          =>  'Tech Products',
        self::HEALTHCARE_WELLNESS_FITNESS           =>  'Healthcare / Fitness / Wellness',
        self::NOT_FOR_PROFIT                        =>  'Charity / NGO',
        self::GOVERNMENT                            =>  'Government',
        self::UTILITIES_BILLS_PAYMENTS              =>  'Utilities / Bill Payments',
        self::HOUSING                               =>  'Real Estate / Office Space / Rentals',
        self::TRAVEL_AND_TRANSPORT                  =>  'Travel & Transport',
        self::SOCIAL                                =>  'Social',
        self::OTHERS                                =>  'Others',
    ];

    const DISPLAY_ORDER_LIST = [
        self::RETAIL_STORE                      => 1,
        self::ONLINE_STORE_MARKETPLACE          => 1,
        self::EDUCATION                         => 1,
        self::SERVICE_PROVIDER                  => 1,
        self::FINANCIAL_SERVICES                => 1,
        self::FOOD                              => 1,
        self::INFLUENCER_ARTIST_CONTENT_CREATOR => 1,
        self::MEDIA_AND_ENTERTAINMENT           => 1,
        self::TECH_SERVICE_AND_FREELANCER       => 1,
        self::TECH_PRODUCT                      => 1,
        self::HEALTHCARE_WELLNESS_FITNESS       => 1,
        self::NOT_FOR_PROFIT                    => 1,
        self::GOVERNMENT                        => 1,
        self::UTILITIES_BILLS_PAYMENTS          => 1,
        self::HOUSING                           => 1,
        self::TRAVEL_AND_TRANSPORT              => 1,
        self::SOCIAL                            => 1,
        self::OTHERS                            => 1,
    ];

    const CATEGORY_MAP = [
        self::RETAIL_STORE => [
            Category::ECOMMERCE,
            Category::FASHION_AND_LIFESTYLE,
            Category::GROCERY,
            Category::DROP_SHIPPING,
            Category::FOOD,
            Category::OTHERS,
        ],
        self::ONLINE_STORE_MARKETPLACE => [
            Category::ECOMMERCE,
            Category::FASHION_AND_LIFESTYLE,
            Category::GROCERY,
            Category::COUPONS,
            Category::GAMING,
            Category::DROP_SHIPPING,
            Category::FOOD,
        ],
        self::EDUCATION => [
            Category::EDUCATION,
        ],
        self::SERVICE_PROVIDER => [
            Category::SERVICES,
            Category::CONSULTING_AND_OUTSOURCING,
            Category::SERVICE_CENTRE,
            Category::TELECOMMUNICATION_SERVICE,
        ],
        self::FINANCIAL_SERVICES => [
            Category::FINANCIAL_SERVICES,
        ],
        self::FOOD => [
            Category::FOOD,
        ],
        self::INFLUENCER_ARTIST_CONTENT_CREATOR => [
            Category::FASHION_AND_LIFESTYLE,
            Category::OTHERS,
        ],
        self::MEDIA_AND_ENTERTAINMENT => [
            Category::MEDIA_AND_ENTERTAINMENT,
        ],
        self::TECH_SERVICE_AND_FREELANCER => [
            Category::IT_AND_SOFTWARE,
            Category::WEB_DEVELOPMENT,
            Category::COMPUTER_PROGRAMMING_DATA_PROCESSING,
            Category::GAMING,
            Category::PAAS,
            Category::SAAS,
            Category::TELECOMMUNICATION_SERVICE
        ],
        self::TECH_PRODUCT => [
            Category::IT_AND_SOFTWARE,
            Category::WEB_DEVELOPMENT,
            Category::GAMING,
            Category::PAAS,
            Category::SAAS,
        ],
        self::HEALTHCARE_WELLNESS_FITNESS => [
            Category::HEALTHCARE,
            Category::HEALTH_COACHING,
        ],
        self::NOT_FOR_PROFIT => [
            Category::NOT_FOR_PROFIT,
        ],
        self::GOVERNMENT => [
            Category::GOVERNMENT,
        ],
        self::UTILITIES_BILLS_PAYMENTS => [
            Category::UTILITIES,
            Category::UTILITIES_ELECTRIC_GAS_OIL_WATER,
        ],
        self::HOUSING => [
            Category::HOUSING,
        ],
        self::TRAVEL_AND_TRANSPORT => [
            Category::REPAIR_AND_CLEANING,
            Category::CAB_HAILING,
            Category::LOGISTICS,
            Category::TOURS_AND_TRAVEL,
            Category::TRANSPORT,
        ],
        self::SOCIAL => [
            Category::SOCIAL,
        ],
        self::OTHERS => [
            Category::OTHERS,
        ]
    ];
}
