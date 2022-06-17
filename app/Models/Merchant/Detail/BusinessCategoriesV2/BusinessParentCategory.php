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
    const HEALTHCARE                            = 'healthcare';
    const NOT_FOR_PROFIT                        = 'not_for_profit';
    const GOVERNMENT                            = 'government';
    const UTILITIES                             = 'utilities';
    const HOUSING                               = 'housing';
    const TRAVEL_AND_TRANSPORT                  = 'travel_and_transport';
    const SOCIAL                                = 'social';
    const OTHERS                                = 'others';

    // Business L0-Category Descriptions
    const DESCRIPTIONS = [
        self::RETAIL_STORE                          =>  'Physical retail store',
        self::ONLINE_STORE_MARKETPLACE              =>  'Online store',
        self::EDUCATION                             =>  'Education',
        self::SERVICE_PROVIDER                      =>  'Services',
        self::FINANCIAL_SERVICES                    =>  'Financial product or service',
        self::FOOD                                  =>  'Food and beverages',
        self::INFLUENCER_ARTIST_CONTENT_CREATOR     =>  'Artist, content creator, or influencer',
        self::MEDIA_AND_ENTERTAINMENT               =>  'Media and entertainment',
        self::TECH_SERVICE_AND_FREELANCER           =>  'Tech services',
        self::TECH_PRODUCT                          =>  'Tech products',
        self::HEALTHCARE                            =>  'Healthcare, fitness, or wellness',
        self::NOT_FOR_PROFIT                        =>  'Nonprofit',
        self::GOVERNMENT                            =>  'Public sector',
        self::UTILITIES                             =>  'Utilities provider',
        self::HOUSING                               =>  'Real estate, housing, rentals',
        self::TRAVEL_AND_TRANSPORT                  =>  'Transport or travel',
        self::SOCIAL                                =>  'Social group or platform',
        self::OTHERS                                =>  'Other',
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
        self::HEALTHCARE                        => 1,
        self::NOT_FOR_PROFIT                    => 1,
        self::GOVERNMENT                        => 1,
        self::UTILITIES                         => 1,
        self::HOUSING                           => 1,
        self::TRAVEL_AND_TRANSPORT              => 1,
        self::SOCIAL                            => 1,
        self::OTHERS                            => 1000,
    ];

    const CATEGORY_MAP = [
        self::RETAIL_STORE => [
            Category::FASHION_AND_LIFESTYLE,
            Category::GROCERY,
            Category::DROP_SHIPPING,
            Category::FOOD,
            Category::OTHERS,
        ],
        self::ONLINE_STORE_MARKETPLACE => [
            Category::ECOMMERCE,
            Category::FASHION_AND_LIFESTYLE,
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
        ],
        self::TECH_PRODUCT => [
            Category::IT_AND_SOFTWARE,
            Category::WEB_DEVELOPMENT,
            Category::GAMING,
            Category::PAAS,
            Category::SAAS,
        ],
        self::HEALTHCARE => [
            Category::HEALTHCARE,
        ],
        self::NOT_FOR_PROFIT => [
            Category::NOT_FOR_PROFIT,
        ],
        self::GOVERNMENT => [
            Category::GOVERNMENT,
        ],
        self::UTILITIES => [
            Category::UTILITIES,
        ],
        self::HOUSING => [
            Category::HOUSING,
        ],
        self::TRAVEL_AND_TRANSPORT => [
            Category::REPAIR_AND_CLEANING,
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
