<?php

namespace RZP\Tests\Functional\Merchant\helpers;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Constants;

return [
    'testMerchantActivationCategoriesResponseForAdminAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/activation/business_categories',
        ],
        'response' => [
            'content'     => [
                'financial_services'      => [
                    'description'   => 'Financial Services',
                    'subcategories' => [
                        'mutual_fund'    => [
                            'category'                 => '6211',
                            'description'              => 'Mutual Fund',
                            'category2'                => 'mutual_funds',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist',
                        ],
                        'lending'        => [
                            'category'                 => '6012',
                            'description'              => 'Lending',
                            'category2'                => 'lending',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist'
                        ],
                        'cryptocurrency' => [
                            'category'                 => '6051',
                            'description'              => 'Cryptocurrency',
                            'category2'                => 'cryptocurrency',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'insurance'      => [
                            'category'                 => '6300',
                            'description'              => 'Insurance',
                            'category2'                => 'insurance',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'nbfc'           => [
                            'category'                 => '6012',
                            'description'              => 'NBFC',
                            'category2'                => 'lending',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'cooperatives'      => [
                            'category'                 => '6012',
                            'description'              => 'Cooperatives',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'pension_fund'      => [
                            'category'                 => '6012',
                            'description'              => 'Pension Fund',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'forex'             => [
                            'category'                 => '6010',
                            'description'              => 'Forex',
                            'category2'                => 'forex',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'securities'        => [
                            'category'                 => '6211',
                            'description'              => 'Securities',
                            'category2'                => 'securities',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist',
                        ],
                        'commodities'       => [
                            'category'                 => '6211',
                            'description'              => 'Commodities',
                            'category2'                => 'securities',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist',
                        ],
                        'accounting'        => [
                            'category'                 => '8931',
                            'description'              => 'Accounting and Taxes',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'financial_advisor' => [
                            'category'                 => '8931',
                            'description'              => 'Financial and Investment Advisors/Financial Advisor',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'crowdfunding'      => [
                            'category'                 => '6050',
                            'description'              => 'Crowdfunding Platform',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'trading'           => [
                            'category'                 => '6211',
                            'description'              => 'Stock Brokerage and Trading',
                            'category2'                => 'securities',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist',
                        ],
                        'betting'           => [
                            'category'                 => '7801',
                            'description'              => 'Betting',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'get_rich_schemes'  => [
                            'category'                 => '7361',
                            'description'              => 'Get Rich Schemes',
                            'category2'                => 'services',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                    ],
                ],
                'education'               => [
                    'description'   => 'Education',
                    'subcategories' => [
                        'college'              => [
                            'category'                 => '8220',
                            'description'              => 'College',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'schools'              => [
                            'category'                 => '8211',
                            'description'              => 'Schools',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'university'           => [
                            'category'                 => '8220',
                            'description'              => 'University',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'professional_courses' => [
                            'category'                 => '8299',
                            'description'              => 'Professional Courses',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'distance_learning'    => [
                            'category'                 => '8299',
                            'description'              => 'Distance Learning',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'coaching'             => [
                            'category'                 => '8299',
                            'description'              => 'Coaching Institute',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'elearning'            => [
                            'category'                 => '8299',
                            'description'              => 'E-Learning',
                            'category2'                => 'pvt_education',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'healthcare'              => [
                    'description'   => 'Healthcare',
                    'subcategories' =>
                        [
                            'pharmacy'               => [
                                'category'                 => '5912',
                                'description'              => 'Pharmacy',
                                'category2'                => 'pharma',
                                'activation_flow'          => 'greylist',
                                'international_activation' => 'blacklist',
                            ],
                            'clinic'                 => [
                                'category'                 => '8062',
                                'description'              => 'Clinic',
                                'category2'                => 'healthcare',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'hospital'               => [
                                'category'                 => '8062',
                                'description'              => 'Hospital',
                                'category2'                => 'healthcare',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'lab'                    => [
                                'category'                 => '8071',
                                'description'              => 'Lab',
                                'category2'                => 'healthcare',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'dietician'              => [
                                'category'                 => '7298',
                                'description'              => 'Dietician/Diet Services',
                                'category2'                => 'healthcare',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'fitness'                => [
                                'category'                 => '7298',
                                'description'              => 'Gym and Fitness',
                                'category2'                => 'services',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'health_coaching'        => [
                                'category'                 => '7298',
                                'description'              => 'Health and Lifestyle Coaching',
                                'category2'                => 'services',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'health_products'        => [
                                'category'                 => '5499',
                                'description'              => 'Health Products',
                                'category2'                => 'ecommerce',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                            'healthcare_marketplace' => [
                                'category'                 => '5399',
                                'description'              => 'Marketplace/Aggregator',
                                'category2'                => 'ecommerce',
                                'activation_flow'          => 'whitelist',
                                'international_activation' => 'greylist',
                            ],
                        ],
                ],
                'utilities'               => [
                    'description'   => 'Utilities-General',
                    'subcategories' => [
                        'electricity'                   => [
                            'category'                 => '4900',
                            'description'              => 'Electricity',
                            'category2'                => 'utilities',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'gas'                           => [
                            'category'                 => '4900',
                            'description'              => 'Gas',
                            'category2'                => 'utilities',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'telecom'                       => [
                            'category'                 => '4814',
                            'description'              => 'Telecom Service Provider',
                            'category2'                => 'recharges',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'water'                         => [
                            'category'                 => '4900',
                            'description'              => 'Water',
                            'category2'                => 'utilities',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'cable'                         => [
                            'category'                 => '4899',
                            'description'              => 'Cable operator',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'broadband'                     => [
                            'category'                 => '4899',
                            'description'              => 'Broadband',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'dth'                           => [
                            'category'                 => '4899',
                            'description'              => 'DTH',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'internet_provider'             => [
                            'category'                 => '4816',
                            'description'              => 'Internet service provider',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'bill_and_recharge_aggregators' => [
                            'category'                 => '4814',
                            'description'              => 'Bill Payment and Recharge Aggregators',
                            'category2'                => 'recharges',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
                'government'              => [
                    'description'   => 'Government Bodies',
                    'subcategories' => [
                        'central' => [
                            'category'                 => '9399',
                            'description'              => 'Central Department',
                            'category2'                => 'government',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'state'   => [
                            'category'                 => '9399',
                            'description'              => 'State Department',
                            'category2'                => 'government',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'logistics'               => [
                    'description'   => 'Logistics',
                    'subcategories' => [
                        'freight'              => [
                            'category'                 => '4214',
                            'description'              => 'Freight Consolidation/Management',
                            'category2'                => 'logistics',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'courier'              => [
                            'category'                 => '4215',
                            'description'              => 'Courier Shipping',
                            'category2'                => 'logistics',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'warehousing'          => [
                            'category'                 => '4225',
                            'description'              => 'Public/Contract Warehousing',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'distribution'         => [
                            'category'                 => '4214',
                            'description'              => 'Distribution Management',
                            'category2'                => 'logistics',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'end_to_end_logistics' => [
                            'category'                 => '4214',
                            'description'              => 'End-to-end logistics',
                            'category2'                => 'logistics',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'tours_and_travel'        => [
                    'description'   => 'Tours and Travel',
                    'subcategories' => [
                        'aviation'      => [
                            'category'                 => '4511',
                            'description'              => 'Aviation',
                            'category2'                => 'travel_agency',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'accommodation' => [
                            'category'                 => '7011',
                            'description'              => 'Lodging and Accommodation',
                            'category2'                => 'hospitality',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'ota'           => [
                            'category'                 => '4722',
                            'description'              => 'OTA',
                            'category2'                => 'travel_agency',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'travel_agency' => [
                            'category'                 => '4722',
                            'description'              => 'Tours and Travel Agency',
                            'category2'                => 'travel_agency',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
                'transport'               => [
                    'description'   => 'Transport',
                    'subcategories' => [
                        'cab_hailing'     => [
                            'category'                 => '4121',
                            'description'              => 'Cab/auto hailing',
                            'category2'                => 'transport',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'bus'             => [
                            'category'                 => '4131',
                            'description'              => 'Bus ticketing',
                            'category2'                => 'transport',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'train_and_metro' => [
                            'category'                 => '4112',
                            'description'              => 'Train and metro ticketing',
                            'category2'                => 'transport',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'ecommerce'               => [
                    'description'   => 'Ecommerce',
                    'subcategories' => [
                        'ecommerce_marketplace'     => [
                            'category'                 => '5399',
                            'description'              => 'Horizontal Commerce/Marketplace',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'agriculture'               => [
                            'category'                 => '5193',
                            'description'              => 'Agricultural products',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'books'                     => [
                            'category'                 => '5942',
                            'description'              => 'Books and Publications',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'electronics_and_furniture' => [
                            'category'                 => '5732',
                            'description'              => 'Electronics and Furniture',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'coupons'                   => [
                            'category'                 => '7311',
                            'description'              => 'Coupons and deals',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'rental'                    => [
                            'category'                 => '7394',
                            'description'              => 'Product Rental',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'fashion_and_lifestyle'     => [
                            'category'                 => '5691',
                            'description'              => 'Fashion and Lifestyle',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'gifting'                   => [
                            'category'                 => '5193',
                            'description'              => 'Flowers and Gifts',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'grocery'                   => [
                            'category'                 => '5411',
                            'description'              => 'Grocery',
                            'category2'                => 'grocery',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'baby_products'             => [
                            'category'                 => '5945',
                            'description'              => 'Baby Care and Toys',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'office_supplies'           => [
                            'category'                 => '5111',
                            'description'              => 'Office Supplies',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'wholesale'                 => [
                            'category'                 => '5300',
                            'description'              => 'Wholesale/Bulk trade',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'religious_products'        => [
                            'category'                 => '5973',
                            'description'              => 'Religious products',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'pet_products'              => [
                            'category'                 => '5995',
                            'description'              => 'Pet Care and Supplies',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'sports_products'           => [
                            'category'                 => '5941',
                            'description'              => 'Sports goods',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'arts_and_collectibles'     => [
                            'category'                 => '5971',
                            'description'              => 'Arts, crafts and collectibles',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'sexual_wellness_products'  => [
                            'category'                 => '5999',
                            'description'              => 'Sexual Wellness Products',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'drop_shipping'             => [
                            'category'                 => '5399',
                            'description'              => 'Dropshipping',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'crypto_machinery'          => [
                            'category'                 => '5999',
                            'description'              => 'Crypto Machinery',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'tobacco'                   => [
                            'category'                 => '5993',
                            'description'              => 'Tobacco',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'weapons_and_ammunitions'   => [
                            'category'                 => '5999',
                            'description'              => 'Weapons and Ammunitions',
                            'category2'                => 'ecommerce',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                    ],
                ],
                'food'                    => [
                    'description'   => 'Food and Beverage',
                    'subcategories' => [
                        'online_food_ordering'          => [
                            'category'                 => '5811',
                            'description'              => 'Online Food Ordering',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'restaurant'                    => [
                            'category'                 => '5812',
                            'description'              => 'Restaurants',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'food_court'                    => [
                            'category'                 => '5814',
                            'description'              => 'Food Courts/Corporate Cafetaria',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'catering'                      => [
                            'category'                 => '5811',
                            'description'              => 'Catering Services',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'alcohol'                       => [
                            'category'                 => '5813',
                            'description'              => 'Alcoholic Beverages',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'restaurant_search_and_booking' => [
                            'category'                 => '7299',
                            'description'              => 'Restaurant search and reservations',
                            'category2'                => 'food_and_beverage',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'it_and_software'         => [
                    'description'   => 'IT and Software',
                    'subcategories' => [
                        'saas'                       => [
                            'category'                 => '5817',
                            'description'              => 'SaaS (Software as a service)',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'paas'                       => [
                            'category'                 => '5817',
                            'description'              => 'Platform as a service',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'iaas'                       => [
                            'category'                 => '5817',
                            'description'              => 'Infrastructure as a service',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'consulting_and_outsourcing' => [
                            'category'                 => '7392',
                            'description'              => 'Consulting and Outsourcing',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'web_development'            => [
                            'category'                 => '7372',
                            'description'              => 'Web designing, development and hosting',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'technical_support'          => [
                            'category'                 => '7379',
                            'description'              => 'Technical Support',
                            'category2'                => 'it_and_software',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                    ],
                ],
                'gaming'                  => [
                    'description'   => 'Gaming',
                    'subcategories' => [
                        'game_developer'     => [
                            'category'                 => '5816',
                            'description'              => 'Game developer and publisher',
                            'category2'                => 'gaming',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'esports'            => [
                            'category'                 => '5816',
                            'description'              => 'E-sports',
                            'category2'                => 'gaming',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'online_casino'      => [
                            'category'                 => '7801',
                            'description'              => 'Online Casino',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                        'fantasy_sports'     => [
                            'category'                 => '5816',
                            'description'              => 'Fantasy Sports',
                            'category2'                => 'gaming',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                        'gaming_marketplace' => [
                            'category'                 => '5816',
                            'description'              => 'Game distributor/Marketplace',
                            'category2'                => 'gaming',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
                'media_and_entertainment' => [
                    'description'   => 'Media and Entertainment',
                    'subcategories' => [
                        'video_on_demand'        => [
                            'category'                 => '5815',
                            'description'              => 'Video on demand',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'music_streaming'        => [
                            'category'                 => '5815',
                            'description'              => 'Music streaming services',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'multiplex'              => [
                            'category'                 => '7832',
                            'description'              => 'Multiplexes',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'content_and_publishing' => [
                            'category'                 => '2741',
                            'description'              => 'Content and Publishing',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'ticketing'              => [
                            'category'                 => '7832',
                            'description'              => 'Events and movie ticketing',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'news'                   => [
                            'category'                 => '5994',
                            'description'              => 'News',
                            'category2'                => 'media_and_entertainment',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                    ],
                ],
                'services'                => [
                    'description'   => 'Services',
                    'subcategories' => [
                        'repair_and_cleaning'           => [
                            'category'                 => '7531',
                            'description'              => 'Repair and cleaning services',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'interior_design_and_architect' => [
                            'category'                 => '8911',
                            'description'              => 'Interior Designing and Architect',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'movers_and_packers'            => [
                            'category'                 => '4214',
                            'description'              => 'Movers and Packers',
                            'category2'                => 'logistics',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'legal'                         => [
                            'category'                 => '8111',
                            'description'              => 'Legal Services',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'event_planning'                => [
                            'category'                 => '8999',
                            'description'              => 'Event planning services',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'service_centre'                => [
                            'category'                 => '5511',
                            'description'              => 'Service Centre',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'whitelist',
                        ],
                        'consulting'                    => [
                            'category'                 => '7392',
                            'description'              => 'Consulting Services',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'ad_and_marketing'              => [
                            'category'                 => '7311',
                            'description'              => 'Ad and marketing agencies',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'services_classifieds'          => [
                            'category'                 => '7311',
                            'description'              => 'Services Classifieds',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'multi_level_marketing'         => [
                            'category'                 => '5964',
                            'description'              => 'Multi-level Marketing',
                            'category2'                => 'financial_services',
                            'activation_flow'          => 'blacklist',
                            'international_activation' => 'blacklist',
                        ],
                    ],
                ],
                'housing'                 => [
                    'description'   => 'Housing and Real Estate',
                    'subcategories' => [
                        'developer'              => [
                            'category'                 => '6513',
                            'description'              => 'Developer',
                            'category2'                => 'real_estate',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'facility_management'    => [
                            'category'                 => '7349',
                            'description'              => 'Facility Management Company',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'rwa'                    => [
                            'category'                 => '7349',
                            'description'              => 'RWA',
                            'category2'                => 'housing',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'coworking'              => [
                            'category'                 => '6513',
                            'description'              => 'Co-working spaces',
                            'category2'                => 'housing',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'realestate_classifieds' => [
                            'category'                 => '6513',
                            'description'              => 'Real estate classifieds',
                            'category2'                => 'services',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'space_rental'           => [
                            'category'                 => '6513',
                            'description'              => 'Home or office rentals',
                            'category2'                => 'real_estate',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
                'not_for_profit'          => [
                    'description'   => 'Not-For-Profit',
                    'subcategories' => [
                        'charity'     => [
                            'category'                 => '8398',
                            'description'              => 'Charity',
                            'category2'                => 'not_for_profit',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'blacklist',
                        ],
                        'educational' => [
                            'category'                 => '8398',
                            'description'              => 'Educational',
                            'category2'                => 'not_for_profit',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'religious'   => [
                            'category'                 => '8661',
                            'description'              => 'Religious',
                            'category2'                => 'not_for_profit',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'personal'    => [
                            'category'                 => '8398',
                            'description'              => 'Personal',
                            'category2'                => 'not_for_profit',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
                'social'                  => [
                    'description'   => 'Social',
                    'subcategories' => [
                        'matchmaking'           => [
                            'category'                 => '7273',
                            'description'              => 'Dating and Matrimony platforms',
                            'category2'                => 'social',
                            'activation_flow'          => 'greylist',
                            'international_activation' => 'blacklist',
                        ],
                        'social_network'        => [
                            'category'                 => '8641',
                            'description'              => 'Social Network',
                            'category2'                => 'social',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'messaging'             => [
                            'category'                 => '4821',
                            'description'              => 'Messaging and Communication',
                            'category2'                => 'social',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'professional_network'  => [
                            'category'                 => '8699',
                            'description'              => 'Professional Network',
                            'category2'                => 'social',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                        'neighbourhood_network' => [
                            'category'                 => '8699',
                            'description'              => 'Local/Neighbourhood network',
                            'category2'                => 'social',
                            'activation_flow'          => 'whitelist',
                            'international_activation' => 'greylist',
                        ],
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantActivationCategoriesResponseForNonAdminAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/activation/business_categories',
        ],
        'response' => [
            'content'     => [
                'financial_services' => [
                    'description'   => 'Financial Services',
                    'subcategories' => [
                        'mutual_fund' => [
                            'description' => 'Mutual Fund',
                        ],
                        'lending'     => [
                            'description' => 'Lending',
                        ],
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPostInstantActivationRequiredField' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/merchant/instant_activation',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'The business category field is required.',
        ],
    ],

    'testInstantActivationOfSubscriptionsForActiveMerchants' => [
        'request'       => [
            'method'    => 'POST',
            'url'       => '/merchant/requests',
            'content'   => [
                'submissions'           => [
                    'business_model'    => 'bc',
                    'sample_plans'      => 'sp',
                    'website_details'   => 'http://fs.com'
                ],
                'name' => 'subscriptions',
                'type' => 'product'
            ]
        ],
        'response'  => [
            'content'     => [
                  'status'   => 'activated',
                                 "merchant"        => [
                                     'activated'    => true,
                                     'live'         => true,
                                     'hold_funds'   => false,
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testInstantActivationOfSubscriptionsForInActiveMerchants' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/requests',
            'content' => [
                'submissions' => [
                    'business_model'    => 'bc',
                    'sample_plans'      => 'sp',
                    'website_details'   => 'http://fs.com'
                ],
                'name' => 'subscriptions',
                'type' => 'product'
            ]
        ],
        'response'  => [
            'content'     => [
                'status' => 'under_review',
                "merchant" => [
                    'activated'  => false,
                    'live'       => false,
                    'hold_funds' => false,
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testSmartCollectActivationForUnregisteredMerchants' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/requests',
            'content' => [
                'submissions' => [
                    'business_model'    => 'bc',
                    'sample_plans'      => 'sp',
                ],
                'name' => 'virtual_accounts',
                'type' => 'product'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
            'description'         => 'Virtual account feature cannot be enabled for unregistered merchants.',
        ],
    ],

    'testInstantActivationOfRoutesForActiveMerchants' => [
        'request'         => [
                'method'  => 'POST',
                'url'     => '/merchant/requests',
                'content' => [
                'submissions' => [
                    'use_case'      => 'bc',
                    'settling_to'   => 'Businesses'
                ],
                'name' => 'marketplace',
                'type' => 'product'
            ]
        ],
        'response'  => [
            'content'     => [
                'status'    => 'activated',
                "merchant"  => [
                    'activated'     => true,
                    'live'          => true,
                    'hold_funds'    => false,
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testInstantActivationOfRoutesForInActiveMerchants' => [
        'request'   => [
            'method'    => 'POST',
            'url'       => '/merchant/requests',
            'content'   => [
                'submissions'   => [
                            'use_case'      => 'bc',
                            'settling_to'   => 'Businesses'
                ],
                'name' => 'marketplace',
                'type' => 'product'
            ]
        ],
        'response'          => [
                'content'   => [
                    'status'    => 'under_review',
                    "merchant"  => [
                        'activated'     => false,
                        'live'          => false,
                        'hold_funds'    => false,
                ]
            ],
            'status_code'   => 200,
        ]
    ],

    'testPostInstantActivation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'company_cin'                 => 'U65999KA2018PTC114468',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'JAMMU AND KASHMIR',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'company_cin'                 => 'U65999KA2018PTC114468',
                'promoter_pan'                => 'ABCPE0000Z',
                'gstin'                       => null,
                'p_gstin'                     => null,
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'archived'                    => 0,
                'submitted_at'                => null,
                'activation_status'           => 'instantly_activated',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'JK',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'stakeholder'                 => [
                    'poi_status'                => 'verified',
                    'poi_identification_number' => 'ABCPE0000Z',
                ],
                'verification'                => [
                    'status'              => 'disabled',
                    'disabled_reason'     => 'required_fields',
                    'required_fields'     => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                    ],
                    'activation_progress' => 60,
                ],
                'can_submit'                  => false,
                'activated'                   => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testPostInstantActivationDefaultMethodsBasedOnCategory' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
            ]
        ],
        'status_code' => 200,
    ],

    'testPostInstantActivationWithBalanceCreation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'               => 'ABCPE0000Z',
                'gstin'                      => null,
                'p_gstin'                    => null,
                'business_category'          => 'ecommerce',
                'business_subcategory'       => 'fashion_and_lifestyle',
                'archived'                   => 0,
                'submitted_at'               => null,
                'activation_status'          => 'instantly_activated',
                'business_operation_address' => 'My Addres is somewhere',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'verification'               => [
                    'status'              => 'disabled',
                    'disabled_reason'     => 'required_fields',
                    'required_fields'     => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                    ],
                    'activation_progress' => 60,
                ],
                'can_submit'                 => false,
                'activated'                  => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationForForUnRegisteredTORegisteredSwitch' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'test123',
                'business_type'               => 3,
                'business_model'              => '1245',
                'promoter_pan_name'           => 'Test123',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                => 'ABCPE0000Z',
                'gstin'                       => null,
                'p_gstin'                     => null,
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'archived'                    => 0,
                'submitted_at'                => null,
                'activation_status'           => 'instantly_activated',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'business_type'               => "3",
                'international'               => true,
                'verification'                => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'                  => false,
                'activated'                   => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationForUnregisteredBusinessWithBlacklistCategories' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'weapons_and_ammunitions',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'test123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'promoter_pan_name'           => 'Test123',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNSUPPORTED_BUSINESS_CATEGORY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_CATEGORY,
        ],
    ],

    'testInstantActivationForUnregisteredBusinessForOlderMerchant' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'test123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'promoter_pan_name'           => 'Test123',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                => 'ABCPE0000Z',
                'gstin'                       => null,
                'p_gstin'                     => null,
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'archived'                    => 0,
                'submitted_at'                => null,
                'activation_status'           => null,
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'business_type'               => "11",
                'can_submit'                  => false,
                'activated'                   => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForUnregisteredBusinessFeatureEnabled' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'Test123',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'            => 'ABCPE0000Z',
                'archived'                => 0,
                'submitted_at'            => null,
                'activation_status'       => 'instantly_activated',
                'poi_verification_status' => 'verified',
                'stakeholder'             => [
                    'poi_status' => 'verified',
                    'poi_identification_number' => 'ABCPE0000Z',
                ],
                'cin_verification_status' => null,
                'business_type'           => "11",
                'can_submit'              => false,
                'activated'               => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForUnregisteredBusinessFeatureEnabledNameMisMatch' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'promoter pan name',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'            => null,
                'activation_status'       => null,
                'poi_verification_status' => 'not_matched',
                'business_type'           => "11",
                'stakeholder'             => [
                    'poi_status' => 'not_matched',
                ],
                'verification'            => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                    'required_fields' => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'contact_mobile',
                        'contact_name',
                        'aadhar_front',
                        'aadhar_back',
                    ],
                ],
                'can_submit'              => false,
                'activated'               => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForUnregisteredBusinessFeatureEnabledIncorrectDetails' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'test name',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'            => null,
                'activation_status'       => null,
                'poi_verification_status' => 'incorrect_details',
                'stakeholder'             => [
                    'poi_status' => 'incorrect_details',
                ],
                'business_type'           => "11",
                'can_submit'              => false,
                'activated'               => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForUnregisteredBusinessFeatureEnabledTimeout' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'promoter pan names',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'            => null,
                'activation_status'       => null,
                'poi_verification_status' => 'failed',
                'stakeholder'             => [
                    'poi_status' => 'failed',
                ],
                'business_type'           => "11",
                'can_submit'              => false,
                'activated'               => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForRegisteredBusinessFeatureEnabledNameMisMatch' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'company_pan'                 => 'ABCCE0000Z',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'promoter pan name',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 4,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'                    => null,
                'activation_status'               => 'instantly_activated',
                'poi_verification_status'         => 'not_matched',
                'company_pan_verification_status' => 'not_matched',
                'can_submit'                      => false,
                'activated'                       => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForRegisteredBusinessFeatureEnabledIncorrectDetails' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'company_pan'                 => 'ABCCE0000Z',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'promoter pan name',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 4,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response' => [
            'content' => [
                'submitted_at'                    => null,
                'activation_status'               => 'instantly_activated',
                'poi_verification_status'         => 'incorrect_details',
                'company_pan_verification_status' => 'incorrect_details',
                'business_type'                   => "4",
                'can_submit'                      => false,
                'activated'                       => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForRegisteredBusinessFeatureEnabledTimeout' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'company_pan'                 => 'ABCCE0000Z',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'promoter pan name',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 4,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'                    => null,
                'activation_status'               => 'instantly_activated',
                'poi_verification_status'         => 'failed',
                'company_pan_verification_status' => 'failed',
                'business_type'                   => "4",
                'can_submit'                      => false,
                'activated'                       => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testIAForRegisteredBusinessSuccessCase' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'company_pan'                 => 'ABCCE0000Z',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'test123',
                'business_name'               => 'test123',
                'business_dba'                => 'tsest123',
                'business_type'               => 4,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'                    => null,
                'activation_status'               => 'instantly_activated',
                'poi_verification_status'         => 'verified',
                'company_pan_verification_status' => 'verified',
                'business_type'                   => "4",
                'can_submit'                      => false,
                'activated'                       => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationWithBlacklistedCategoryForEmi' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCPE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'archived'                         => 0,
                'allowed_next_activation_statuses' => [
                    'under_review'
                ],
                'submitted_at'                     => null,
                'verification'                     => [
                    'status'              => 'disabled',
                    'disabled_reason'     => 'required_fields',
                    'required_fields'     => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',

                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',

                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                    ],
                    'activation_progress' => 60,
                ],
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationWithWhitelistedCategoryForEmi' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'services',
                'business_subcategory' => 'legal',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCPE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'services',
                'business_subcategory'             => 'legal',
                'archived'                         => 0,
                'allowed_next_activation_statuses' => [
                    'under_review'
                ],
                'submitted_at'                     => null,
                'activation_status'                => 'instantly_activated',
                'verification'                     => [
                    'status'              => 'disabled',
                    'disabled_reason'     => 'required_fields',
                    'required_fields'     => [

                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',

                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',

                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',

                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                    ],
                    'activation_progress' => 60,
                ],
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testUpdateActivationFlow' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'mutual_fund',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'business_category'    => 'financial_services',
                'business_subcategory' => 'mutual_fund',
                'can_submit'           => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testUpdateCategoryDetails' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'mutual_fund',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'business_category'    => 'financial_services',
                'business_subcategory' => 'mutual_fund',
                'can_submit'           => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testPostInstantActivationLinkedAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_INSTANTLY_ACTIVATED,
        ],
    ],

    'testSaveCommonMerchantDetailsWhenPartnerActivationLocked' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '+919123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'contact_name'        => 'test',
                'contact_mobile'      => '+919123456789',
                'promoter_pan'        => 'ABCPE0000Z',
                'bank_account_number' => '123456789012345',
                'bank_branch_ifsc'    => 'ICIC0000001',
                'can_submit'          => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testSaveUncommonMerchantDetailsWhenPartnerActivationLocked' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'        => 'ABCPE0000Z',
                'bank_account_number' => '123456789012345',
                'bank_branch_ifsc'    => 'ICIC0000001',
                'can_submit'          => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testPostInstantActivationByActivatedMerchant' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'services',
                'business_subcategory' => 'event_planning',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED,
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED,
        ]
    ],

    'testBlacklistInstantActivation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'betting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response' => [
            'content' => [
                'contact_email'                    => 'test@razorpay.com',
                'promoter_pan'                     => 'ABCPE0000Z',
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'betting',
                'activation_flow'                  => 'blacklist',
                'activation_progress'              => 60,
                'archived'                         => 0,
                'allowed_next_activation_statuses' => [],
                'submitted_at'                     => null,
            ],
        ],
    ],

    'testGreylistInstantActivation' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'mutual_fund',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response' => [
            'content' => [
                'contact_email'                    => 'test@razorpay.com',
                'promoter_pan'                     => 'ABCPE0000Z',
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'mutual_fund',
                'activation_flow'                  => 'greylist',
                'activation_progress'              => 60,
                'archived'                         => 0,
                'allowed_next_activation_statuses' => [],
                'submitted_at'                     => null,
            ],
        ],
    ],

    'testL1ResubmissionForBlacklist' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCPE0000Z',
                'activation_progress'              => 60,
                'archived'                         => 0,
                'allowed_next_activation_statuses' => [],
                'submitted_at'                     => null,
                'activation_flow'                  => 'whitelist',
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'can_submit'                       => false,
                //'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionForInstantlyActivatedMerchantAovAbsent' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionForInstantlyActivatedMerchantBankAccountNameAbsent' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionForInstantlyActivatedBusinessParentCategory' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                  => 'test',
                'contact_mobile'                => '9123456789',
                'business_type'                 => '1',
                'business_name'                 => 'Acme',
                'business_dba'                  => 'Acme',
                'bank_account_number'           => '123456789012345',
                'bank_branch_ifsc'              => 'ICIC0000001',
                'business_operation_address'    => 'Test address',
                'business_operation_state'      => 'Karnataka',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_pin'        => '560030',
                'business_registered_address'   => 'Test address',
                'business_registered_state'     => 'Karnataka',
                'business_registered_city'      => 'Bengaluru',
                'business_registered_pin'       => '560030',
                'blacklisted_products_category' => 'none of the above',
                'business_parent_category'      => 'ecommerce',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'              => 'ABCPE0000Z',
                'promoter_pan_name'         => 'John Doe',
                'gstin'                     => null,
                'p_gstin'                   => null,
                'business_parent_category'  => 'ecommerce',
                'business_category'         => 'ecommerce',
                'business_subcategory'      => 'fashion_and_lifestyle',
                'archived'                  => 0,
                'activation_status'         => 'instantly_activated',
                'verification'              => [
                    'status' => 'pending',
                ],
                'can_submit'                => true,
                'activated'                 => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionBusinessParentCategoryChange' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                  => 'test',
                'contact_mobile'                => '9123456789',
                'business_type'                 => '1',
                'business_name'                 => 'Acme',
                'business_dba'                  => 'Acme',
                'bank_account_number'           => '123456789012345',
                'bank_branch_ifsc'              => 'ICIC0000001',
                'business_operation_address'    => 'Test address',
                'business_operation_state'      => 'Karnataka',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_pin'        => '560030',
                'business_registered_address'   => 'Test address',
                'business_registered_state'     => 'Karnataka',
                'business_registered_city'      => 'Bengaluru',
                'business_registered_pin'       => '560030',
                'blacklisted_products_category' => 'none of the above',
                'business_parent_category'      => 'ecommerce',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'              => 'ABCPE0000Z',
                'promoter_pan_name'         => 'John Doe',
                'gstin'                     => null,
                'p_gstin'                   => null,
                'business_parent_category'  => 'ecommerce',
                'business_category'         => 'ecommerce',
                'business_subcategory'      => 'fashion_and_lifestyle',
                'archived'                  => 0,
                'activation_status'         => 'instantly_activated',
                'verification'              => [
                    'status' => 'pending',
                ],
                'can_submit'                => true,
                'activated'                 => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionForInstantlyActivatedMerchantForRazorpayOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testAadhaarNotLinkedWithoutStakeholderEntity' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'stakeholder'   => [
                    'aadhaar_linked'    => 0
                ]
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],

    'testAadhaarNotLinkedWithStakeholderEntity' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'stakeholder'   => [
                    'aadhaar_linked'    => 0
                ]
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],

    'testAddingVirtualAccountInLinkedAccount' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'bank_account_number'         => '22233312312',
                'bank_branch_ifsc'            => 'YESB0000719',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_VIRTUAL_BANK_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_BANK_ACCOUNT,
        ],
    ],

    'testHardLimitReachedWithLevelThree' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'isHardLimitReached'            => true,
            ],
        ],
        'status_code' => 200,
    ],

    'testHardLimitNotReached' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'isHardLimitReached'            => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testHardLimitEmailSent' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/auto-kyc-cron/escalations',
        ],

        'status_code' => 200,
    ],

    'testHardLimitEmailNotSent' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/auto-kyc-cron/escalations',
        ],

        'status_code' => 200,
    ],

    'testKycSubmissionForInstantlyActivatedMerchantForCustomOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testKycSubmissionWhenPoaIsVerified' => [
        'request'     => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'url'     => '/merchant/activation',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '2',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan_name'    => 'John Doe',
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'changeActivationStatus' => [
        'request'  => [
            'content' => [
                'activation_status' => 'under_review',
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status' => 'under_review',
            ],
        ],
    ],

    'testKycSubmissionWhenPoaIsFailed' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '2',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan_name'    => 'John Doe',
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'submitKyc' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'under_review',
                'can_submit'        => true,
            ],
        ],
    ],

    'submitKycActivated' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'activated_mcc_pending',
                'can_submit'        => true,
            ],
        ],
    ],

    'validateCanSubmit' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetActivationDetailsForSupportRoleUserFail' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testGetActivationDetailsWithPartnerKycLockedForProprietorship' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'lock_common_fields' => [
                    'contact_name',
                    'contact_mobile',
                    'contact_email',
                    'business_type',
                    'bank_account_name',
                    'bank_account_number',
                    'bank_branch_ifsc',
                    'promoter_pan',
                    'promoter_pan_name',
                    'gstin'
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetActivationDetailsWithPartnerKycLockedForPvtLtd' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'lock_common_fields' => [
                    'contact_name',
                    'contact_mobile',
                    'contact_email',
                    'business_type',
                    'bank_account_name',
                    'bank_account_number',
                    'bank_branch_ifsc',
                    'company_pan',
                    'business_name',
                    'gstin'
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testReleaseFundsWithoutBankAccount' => [
        'request'   => [
            'content' => [
                'action' => 'release_funds'
            ],
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
        ],
    ],

    'testReleaseFundsWithParntersBankAccount' => [
        'request'   => [
            'content' => [
                'action' => 'release_funds'
            ],
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_NO_BANK_ACCOUNT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_NO_BANK_ACCOUNT_FOUND,
        ],
    ],

    'testPostInstantActivationFetaureCheck' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationForOlderMerchant' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'activated'            => 1,
                'activation_status'    => 'instantly_activated',
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternational' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'international'        => true,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternationalForRiskyBusinessType' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'insurance',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                  => 'ABCPE0000Z',
                'business_category'             => 'financial_services',
                'business_subcategory'          => 'insurance',
                'international_activation_flow' => 'greylist',
                'international'                 => false,
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternationalWithNoWebsite' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => '',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternationalWithWebsiteAlreadySet' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'international'        => true,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternationalWithNoWebsiteAlreadySet' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://www.example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'international'        => true,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testBlacklistInternational' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'healthcare',
                'business_subcategory' => 'pharmacy',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'healthcare',
                'business_subcategory' => 'pharmacy',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testGreylistInternational' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'web_development',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'web_development',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testGreylistInternationalInstantlyActivated' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'wholesale',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'wholesale',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testGreylistInternationalNonInstantActivation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'not_for_profit',
                'business_subcategory' => 'educational',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'not_for_profit',
                'business_subcategory' => 'educational',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],
    'testWhitelistInternationalNonInstantActivation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'consulting_and_outsourcing',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 5,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'consulting_and_outsourcing',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testWhitelistInternationalForNonRZPOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 4,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testGreylistInternationalForNonRZPOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'web_development',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'it_and_software',
                'business_subcategory' => 'web_development',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testBlacklistInternationalForNonRZPOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'healthcare',
                'business_subcategory' => 'pharmacy',
                'promoter_pan'         => 'ABCPE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'healthcare',
                'business_subcategory' => 'pharmacy',
                'international'        => false,
                'archived'             => 0,
                'submitted_at'         => null,
                'can_submit'           => false,
                'activated'            => 0,
            ],
        ],
        'status_code' => 200,
    ],

    'testAutoSubmitPartnerKycFromMerchantKycForm' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response' => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
    ],

    'testBankDetailsVerificationStatusForUnRegisteredBusiness' => [
        'request'  => [
            'content' => [
                'submit' => '1'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',

        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testL2SegmentEventNotSentDuringNC' =>[
        'request'  => [
            'content' => [
                'submit' => true
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',

        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'under_review',
                'can_submit'        => true,
                'locked'            => true,
            ],
        ],
    ],

    'testValidateNeedsClarificationStatusChange' => [
        'request'  => [
            'content' => [
                'submit' => true
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',

        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'under_review',
                'can_submit'        => true,
                'locked'            => true,
            ],
        ],
    ],

    'testNeedsClarificationResponseForAdminAuth' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/merchant/activation/clarification_reasons',
        ],
        'response' => [
            'content'     => [
                'contact_name'         => [
                    'reasons' => [
                        'provide_poc' => [
                            'description' => 'Please provide a POC that we can reach out to in case of issues associated with your account.',
                        ],
                    ],
                ],
                'contact_mobile'       => [
                    'reasons' => [
                        'invalid_contact_number' => [
                            'description' => 'Please provide a valid contact number',
                        ],
                    ],
                ],
                'business_type'        => [
                    'reasons' => [
                        'is_company_reg' => [
                            'description' => 'Is your company a registered entity?',
                        ],
                    ],
                ],
                'business_website'     => [
                    'reasons' => [
                        'website_not_live' => [
                            'description' => 'Your website/app is currently not live. When will your website go live?',
                        ],
                    ],
                ],
                'promoter_pan'         => [
                    'reasons' => [
                        'update_director_pan'   => [
                            'description' => 'Please update PAN details of a director listed by MCA',
                        ],
                        'update_proprietor_pan' => [
                            'description' => 'Please update PAN of the Proprietor.'
                        ]
                    ],
                ],
                'company_pan_name'     => [
                    'reasons' => [
                        'update_director_pan' => [
                            'description' => 'Please update PAN details of a director listed by MCA',
                        ],
                    ],
                ],
                'bank_account_number'  => [
                    'reasons' => [
                        'bank_account_change_request_for_unregistered' => [
                            'description' => 'Entered bank details are incorrect, please share signatory personal account details',
                        ],
                        'bank_account_change_request_for_pvt_public_llp' => [
                            'description' => 'Entered bank details are incorrect, please share company bank account details.',
                        ],
                        'bank_account_change_request_for_prop_ngo_trust' => [
                            'description' => 'Entered bank details are incorrect, please share company bank account details or authorised signatory details.',
                        ],
                    ],
                ],
                'business_proof_url'   => [
                    'reasons' => [
                        'submit_incorporation_certificate'          => [
                            'description' => 'Please submit the Certificate of Incorporation',
                        ],
                        'submit_complete_partnership_deed'          => [
                            'description' => 'Please submit all the pages of the Partnership Deed merged as one document.',
                        ],
                        'submit_gstin_msme_shops_estab_certificate' => [
                            'description' => 'Please submit the GSTIN/MSME/Shops and Establishment Certificate',
                        ],
                        'submit_complete_trust_deed'                => [
                            'description' => 'Please submit all the pages of the Trust Deed merged as one document',
                        ],
                        'submit_society_reg_certificate'            => [
                            'description' => 'Please submit the Society registration certificate',
                        ],
                        'business_proof_outdated'                   => [
                            'description' => 'The validity of the business proof attached has elapsed. Please submit the updated registration certificate',
                        ],
                        'illegible_doc'                             => [
                            'description' => 'The document attached is not legible. Please resubmit a clearer copy',
                        ],
                        'submit_reg_business_pan_card'              => [
                            'description' => 'Please submit a copy of the PAN Card[in the name of registered business]',
                        ],
                    ],
                ],
                'address_proof_url'    => [
                    'reasons' => [
                        'unable_to_validate_acc_number'       => [
                            'description' => 'We\'re unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',
                        ],
                        'unable_to_validate_beneficiary_name' => [
                            'description' => 'We\'re unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',
                        ],
                        'unable_to_validate_ifsc'             => [
                            'description' => 'We\'re unable to validate the IFSC from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',
                        ],
                        'resubmit_cancelled_cheque'           => [
                            'description' => 'We\'re unable to validate your bank account details. Kindly submit a copy of cancelled cheque by logging into your Razorpay dashboard.',
                        ],
                    ],
                ],
                'promoter_address_url' => [
                    'reasons' => [
                        'submit_complete_director_address_proof' => [
                            'description' => 'Please submit address proof[both photo ID and address page merged as one document] of a director listed on the MCA website whose PAN details have been submitted under the Tab- Registration Details',
                        ],
                        'submit_complete_aadhaar'                => [
                            'description' => 'Please submit both photo ID and address page of the Aadhaar Card- merged as one document ',
                        ],
                        'submit_complete_passport'               => [
                            'description' => 'Please submit both photo ID and address page of the Passport- merged as one document ',
                        ],
                        'submit_complete_election_card'          => [
                            'description' => 'Please submit both photo ID and address page of the Election Card- merged as one document ',
                        ],
                        'address_proof_outdated'                 => [
                            'description' => 'The validity of the address proof attached has elapsed. Please submit the updated document',
                        ],
                        'submit_driving_license'                 => [
                            'description' => 'Please submit both photo ID and address page of the driving license- merged as one document.',
                        ],
                    ],
                ],
                'business_pan_url'     => [
                    'reasons' => [
                        'submit_company_pan'    => [
                            'description' => 'Please submit a copy of the Company PAN Card',
                        ],
                        'submit_proprietor_pan' => [
                            'description' => 'Please submit a copy of the Proprietor PAN Card.',
                        ],
                    ],
                ],
            ],
            'status_code' => 200,
        ]
    ],

    'testBusinessWebsiteUpdate' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'activation_status' => 'instantly_activated',
                'business_website'  => 'https://example.com',
            ],
        ],
        'status_code' => 200,
    ],

    'testPennyTestingRetryCron' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchants/retry_penny_testing',
            'content' => [
            ],
        ],
        'response'    => [
            'content' => [],
        ],
        'status_code' => 200,
    ],

    'testStoreLegalDocumentsRetryCron' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/retry_store_legal_documents',
            'content' => [
            ],
        ],
        'response'    => [
            'content' => [],
        ],
        'status_code' => 200,
    ],

    'cinVerification' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/verify/cin',
            'content' => [
                "company_cin" => "U65999KA2018PTC114468",
            ],
        ],
        'response'    => [
            'content' => [
            ],
        ],
        'status_code' => 200,
    ],

    'testInternalInstrumentStatusUpdateRequestedOnMerchantActivationFormSubmission' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPostInstantActivationBlockedOrg' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'promoter_pan_name'           => 'test123',
                'business_name'               => 'business_name',
                'business_dba'                => 'test123',
                'business_type'               => 4,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'submitted_at'            => null,
                'activation_status'       => null,
                'poi_verification_status' => null,
                'business_type'           => "4",
                'can_submit'              => false,
                'activated'               => 0,
                'activation_flow'         => 'greylist'
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationWithNullActivationFormMilestone' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'activation_form_milestone'   => null,
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'               => 'ABCPE0000Z',
                'gstin'                      => null,
                'p_gstin'                    => null,
                'business_category'          => 'ecommerce',
                'business_subcategory'       => 'fashion_and_lifestyle',
                'archived'                   => 0,
                'submitted_at'               => null,
                'business_operation_address' => 'My Addres is somewhere',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'can_submit'                  => false,
                'activated'                   => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInstantActivationWithActivationFormMilestoneAsL1Submission' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'activation_form_milestone'   => 'L1',
                'company_cin'                 => 'U65999KA2018PTC114468',
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'tsest123',
                'business_type'               => 1,
                'business_model'              => '1245',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
            ],
        ],
        'response'    => [
            'content' => [
                'activation_form_milestone'   => 'L1',
                'company_cin'                 => 'U65999KA2018PTC114468',
                'promoter_pan'                => 'ABCPE0000Z',
                'gstin'                       => null,
                'p_gstin'                     => null,
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'archived'                    => 0,
                'submitted_at'                => null,
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'can_submit'                  => false,
                'activated'                   => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testAddAppUrls' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                "playstore_url"=> "https://play.google.com/store/apps/details?id=com.cricbuzz.android.vernacular"
            ],
        ],
        'response'    => [
            'content' => [
                'merchant_business_detail' => [
                    "merchant_id"=> "10000000000000",
                    "app_urls" => [
                        "appstore_url"=> null,
                        "playstore_url"=> "https://play.google.com/store/apps/details?id=com.cricbuzz.android.vernacular"
                    ],
                ],
                "playstore_url"=> "https://play.google.com/store/apps/details?id=com.cricbuzz.android.vernacular"
            ],
        ],
        'status_code' => 200,
    ],

    'testAddPaymentsAvenue' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                "physical_store"=> true,
                "social_media"  => true,
                "live_website_or_app" => false
            ],
        ],
        'response'    => [
            'content' => [
                'merchant_business_detail' => [
                    "merchant_id"=> "10000000000000",
                    "website_details" => [
                        "physical_store"=> "1",
                        "social_media"  => "1",
                        "live_website_or_app" => ""
                    ],
                ],
            ],
        ],
        'status_code' => 200,
    ],

    'testAddComplianceConsentAndWebsiteNotReady' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                "website_not_ready"=> true,
                "website_compliance_consent"  => true,
                "others" => "Other payment acceptance means"
            ],
        ],
        'response'    => [
            'content' => [
                'merchant_business_detail' => [
                    "merchant_id"=> "10000000000000",
                    "website_details" => [
                        "website_not_ready"=> "1",
                        "website_compliance_consent"  => "1",
                        "others" => "Other payment acceptance means"
                    ],
                ],
            ],
        ],
        'status_code' => 200,
    ],

    'testAddSocialMediaAndWhatsappUrls' => [
        'request'  => [
            'content' => [
                'whatsapp_sms_email' => true,
                'social_media_urls' => [
                    [
                        'platform' => 'facebook',
                        'url' => 'https://www.facebook.com/Meta/'
                    ],
                    [
                        'platform' => 'twitter',
                        'url' => 'https://www.twitter.com/_anant_mishra/'
                    ]
                ],
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_business_detail' => [
                    'website_details' => [
                        'whatsapp_sms_email' => '1',
                        'social_media_urls' => [
                            [
                                'platform' => 'facebook',
                                'url' => 'https://www.facebook.com/Meta/'
                            ],
                            [
                                'platform' => 'twitter',
                                'url' => 'https://www.twitter.com/_anant_mishra/'
                            ]
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testMerchantActivationOtpSendBanking'  => [
        'request'  => [
            'url'     => '/merchant/activation/otp/send',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'hello123@c.com',
                'token'                 => 'MOCK_TOKEN1234'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' =>  config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => []
        ],
        'status_code' => 200
    ],



    'testMerchantActivationOtpSendPrimary'  => [
        'request'  => [
            'url'     => '/merchant/activation/otp/send',
            'method'  => 'POST',
            'content' => [
                'email'                 => 'hello123@c.com',
                'token'                 => 'MOCK_TOKEN1234'
            ],
        ],
        'response' => [
            'content' => []
        ],
        'status_code' => 200
    ],


    'testInstantActivationWithInvalidActivationFormMilestone' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
            'content' => [
                'business_category'           => 'ecommerce',
                'business_subcategory'        => 'fashion_and_lifestyle',
                'promoter_pan'                => 'ABCPE0000Z',
                'business_name'               => 'business_name',
                'business_dba'                => 'test123',
                'business_type'               => 11,
                'business_model'              => '1245',
                'promoter_pan_name'           => 'Test123',
                'business_website'            => 'https://example.com',
                'business_operation_address'  => 'My Addres is somewhere',
                'business_operation_state'    => 'KA',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560095',
                'business_registered_address' => 'Registered Address',
                'business_registered_state'   => 'DL',
                'business_registered_city'    => 'Delhi',
                'business_registered_pin'     => '560050',
                'activation_form_milestone'   => 'invalid_value',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Activation Form milestone',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description'         => 'Invalid Activation Form milestone',
        ],
    ],

    'testKycSubmissionForInstantlyActivatedMerchantWithL2Milestone' => [
        'request'  => [
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
                'activation_form_milestone'   => 'L2',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'submitted'                   => true,
                'activation_status'           => 'under_review',
                'can_submit'                  => true,
                'activation_form_milestone'   => 'L2',
            ],
        ],
    ],

    'testMerchantActivationWithEmptyBusinessCategoryInX' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'server'                 => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'                => [
                'business_category'    => null,
                'business_subcategory' => null
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'             => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'      => 'The business category field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyBusinessCategoryInPg' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'content'                => [
                'business_category'    => null,
                'business_subcategory' => null
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'             => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'      => 'The business category field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyIfscInX' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'server'                 => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'                => [
                'bank_branch_ifsc' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'         => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'  => 'The bank branch ifsc field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyIfscInPg' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'content'                => [
                'bank_branch_ifsc' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'             => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'      => 'The bank branch ifsc field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptySubCategoryInX' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'server'                 => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'                => [
                'business_subcategory' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testMerchantActivationWithEmptySubCategoryInPg' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'content'                => [
                'business_subcategory' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testMerchantActivationWithEmptyCompanyPanInX' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'server'                 => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'                => [
                'company_pan' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'         => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'  => 'The company pan field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyCompanyPanInPg' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'content'                => [
                'company_pan' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'         => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'  => 'The company pan field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyBusinessRegisteredAddressInX' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'server'                 => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'                => [
                'business_registered_address' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'         => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'  => 'The business registered address field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantActivationWithEmptyBusinessRegisteredAddressInPg' => [
        'request'     => [
            'method'                 => 'POST',
            'url'                    => '/merchant/activation',
            'content'                => [
                'business_registered_address' => null,
            ],
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'         => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'  => 'The business registered address field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInternalMerchantGetRejectionReasonsWithRejectionOptionDisableSettlement' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/rejection_reasons',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'reason_type'        => 'rejection',
                        'reason_category'    => 'risk_related_rejections',
                        'reason_description' => 'Merchant rejected based on Risk team\'s remarks',
                    ],
                ],
            ],
        ],
    ],


    'testInternalMerchantGetRejectionReasonsWithRejectionOptionEnableSettlement' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/rejection_reasons',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'reason_type'        => 'rejection',
                        'reason_category'    => 'risk_related_rejections',
                        'reason_description' => 'Merchant rejected based on Risk team\'s remarks',
                    ],
                ],
            ],
        ],
    ],

    'testInternalMerchantGetRejectionReasonsWithRejectionOptionProofOfDeliveryMail' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/rejection_reasons',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'reason_type'        => 'rejection',
                        'reason_category'    => 'risk_related_rejections',
                        'reason_description' => 'Merchant rejected based on Risk team\'s remarks',
                    ],
                ],
            ],
        ],
    ],

    'testStorageConsentForInstantlyActivatedMerchantWithL2Milestone' => [
        'request'  => [
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
                'activation_form_milestone'   => 'L2',
                'consent'                     => true,
                'documents_detail'            => [
                    [
                        'type'    => 'Privacy Policy',
                        'url'=>'https://razorpay.com/terms/'
                    ],
                    [
                        'type'=>'Service Agreement',
                        'url'=>'https://razorpay.com/terms/'
                    ]
                ]
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'submitted'                   => true,
                'activation_status'           => 'under_review',
                'can_submit'                  => true,
                'activation_form_milestone'   => 'L2',
            ],
        ],
    ],

    'testConsentDetailsForTnCValidation' => [
        'request'  => [
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
                'activation_form_milestone'   => 'L2',
                'consent'                     => true,
                'documents_detail'            => [
                    [
                        'type'    => 'Privacy Policy',
                        'url'=>'https://razorpay.com/privacy/'
                    ],
                    [
                        'type'=>'Service Agreement',
                        'url'=>'https://razorpay.com/agreement/'
                    ],
                    [
                        "type" => "Terms and Conditions",
                        "url" => "https://razorpay.com/terms/"
                    ]
                ]
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'submitted'                   => true,
                'activation_status'           => 'under_review',
                'can_submit'                  => true,
                'activation_form_milestone'   => 'L2',
            ],
        ],
    ],

    'testStorageConsentNullForMerchantWithL2Milestone' => [
        'request'  => [
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
                'activation_form_milestone'   => 'L2',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'submitted'                   => true,
                'activation_status'           => 'under_review',
                'can_submit'                  => true,
                'activation_form_milestone'   => 'L2',
            ],
            'status_code' => 200,
        ]
    ],

    'testSubmitPartnerKycFromMerchantKycFormForRiskyMerchant' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'contact_name'                => 'test',
                'contact_mobile'              => '9123456789',
                'business_type'               => '1',
                'business_name'               => 'Acme',
                'business_dba'                => 'Acme',
                'bank_account_name'           => 'test',
                'bank_account_number'         => '123456789012345',
                'bank_branch_ifsc'            => 'ICIC0000001',
                'business_operation_address'  => 'Test address',
                'business_operation_state'    => 'Karnataka',
                'business_operation_city'     => 'Bengaluru',
                'business_operation_pin'      => '560030',
                'business_registered_address' => 'Test address',
                'business_registered_state'   => 'Karnataka',
                'business_registered_city'    => 'Bengaluru',
                'business_registered_pin'     => '560030',
            ],
        ],
        'response' => [
            'content' => [
                'promoter_pan'         => 'ABCPE0000Z',
                'promoter_pan_name'    => 'John Doe',
                'gstin'                => null,
                'p_gstin'              => null,
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'archived'             => 0,
                'activation_status'    => 'instantly_activated',
                'verification'         => [
                    'status' => 'pending',
                ],
                'can_submit'           => true,
                'activated'            => 1,
            ],
        ],
    ],
];
