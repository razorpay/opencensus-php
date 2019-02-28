<?php

namespace RZP\Tests\Functional\Merchant\helpers;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;

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
                        'mutual_fund'       => [
                            'category'                  => 6211,
                            'description'               => 'Mutual Fund',
                            'category2'                 => 'mutual_funds',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'lending'           => [
                            'category'                  => 6012,
                            'description'               => 'Lending',
                            'category2'                 => 'lending',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'cryptocurrency'    => [
                            'category'                  => 6051,
                            'description'               => 'Cryptocurrency',
                            'category2'                 => 'cryptocurrency',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'insurance'         => [
                            'category'                  => 6300,
                            'description'               => 'Insurance',
                            'category2'                 => 'insurance',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'nbfc'              => [
                            'category'                  => 6012,
                            'description'               => 'NBFC',
                            'category2'                 => 'lending',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'cooperatives'      => [
                            'category'                  => 6012,
                            'description'               => 'Cooperatives',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'pension_fund'      => [
                            'category'                  => 6012,
                            'description'               => 'Pension Fund',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'forex'             => [
                            'category'                  => 6010,
                            'description'               => 'Forex',
                            'category2'                 => 'forex',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'securities'        => [
                            'category'                  => 6211,
                            'description'               => 'Securities',
                            'category2'                 => 'securities',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'commodities'       => [
                            'category'                  => 6211,
                            'description'               => 'Commodities',
                            'category2'                 => 'securities',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'accounting'        => [
                            'category'                  => 8931,
                            'description'               => 'Accounting and Taxes',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'financial_advisor' => [
                            'category'                  => 8931,
                            'description'               => 'Financial and Investment Advisors/Financial Advisor',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'crowdfunding'      => [
                            'category'                  => 6050,
                            'description'               => 'Crowdfunding Platform',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'trading'           => [
                            'category'                  => 6211,
                            'description'               => 'Stock Brokerage and Trading',
                            'category2'                 => 'securities',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'betting'           => [
                            'category'                  => 7801,
                            'description'               => 'Betting',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'get_rich_schemes'  => [
                            'category'                  => 7361,
                            'description'               => 'Get Rich Schemes',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                    ],
                ],
                'education'               => [
                    'description'   => 'Education',
                    'subcategories' => [
                        'college'              => [
                            'category'                  => 8220,
                            'description'               => 'College',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'schools'              => [
                            'category'                  => 8211,
                            'description'               => 'Schools',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'university'           => [
                            'category'                  => 8220,
                            'description'               => 'University',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'professional_courses' => [
                            'category'                  => 8299,
                            'description'               => 'Professional Courses',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'distance_learning'    => [
                            'category'                  => 8299,
                            'description'               => 'Distance Learning',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'coaching'             => [
                            'category'                  => 8299,
                            'description'               => 'Coaching Institute',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'elearning'            => [
                            'category'                  => 8299,
                            'description'               => 'E-Learning',
                            'category2'                 => 'pvt_education',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'healthcare'              => [
                    'description'   => 'Healthcare',
                    'subcategories' =>
                        [
                            'pharmacy'               => [
                                'category'                  => 5912,
                                'description'               => 'Pharmacy',
                                'category2'                 => 'pharma',
                                'activation_flow'           => 'greylist',
                                'international_activation'  => 'blacklist',
                            ],
                            'clinic'                 => [
                                'category'                  => 8062,
                                'description'               => 'Clinic',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'hospital'               => [
                                'category'                  => 8062,
                                'description'               => 'Hospital',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'lab'                    => [
                                'category'                  => 8071,
                                'description'               => 'Lab',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'dietician'              => [
                                'category'                  => 7298,
                                'description'               => 'Dietician/Diet Services',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'fitness'                => [
                                'category'                  => 7298,
                                'description'               => 'Gym and Fitness',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'health_coaching'        => [
                                'category'                  => 7298,
                                'description'               => 'Health and Lifestyle Coaching',
                                'category2'                 => 'others',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'health_products'        => [
                                'category'                  => 5499,
                                'description'               => 'Health Products',
                                'category2'                 => 'ecommerce',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                            'healthcare_marketplace' => [
                                'category'                  => 5399,
                                'description'               => 'Marketplace/Aggregator',
                                'category2'                 => 'ecommerce',
                                'activation_flow'           => 'whitelist',
                                'international_activation'  => 'greylist',
                            ],
                        ],
                ],
                'utilities'               => [
                    'description'   => 'Utilities',
                    'subcategories' => [
                        'electricity'                   => [
                            'category'                  => 4900,
                            'description'               => 'Electricity',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'gas'                           => [
                            'category'                  => 4900,
                            'description'               => 'Gas',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'telecom'                       => [
                            'category'                  => 4814,
                            'description'               => 'Telecom Service Provider',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'water'                         => [
                            'category'                  => 4900,
                            'description'               => 'Water',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'cable'                         => [
                            'category'                  => 4899,
                            'description'               => 'Cable operator',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'broadband'                     => [
                            'category'                  => 4899,
                            'description'               => 'Broadband',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'dth'                           => [
                            'category'                  => 4899,
                            'description'               => 'DTH',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'internet_provider'             => [
                            'category'                  => 4816,
                            'description'               => 'Internet service provider',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'bill_and_recharge_aggregators' => [
                            'category'                  => 4814,
                            'description'               => 'Bill Payment and Recharge Aggregators',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                    ],
                ],
                'government'              => [
                    'description'   => 'Government Bodies',
                    'subcategories' => [
                        'central' => [
                            'category'                  => 9399,
                            'description'               => 'Central Department',
                            'category2'                 => 'government',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'state'   => [
                            'category'                  => 9399,
                            'description'               => 'State Department',
                            'category2'                 => 'government',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'logistics'               => [
                    'description'   => 'Logistics',
                    'subcategories' => [
                        'freight'              => [
                            'category'                  => 4214,
                            'description'               => 'Freight Consolidation/Management',
                            'category2'                 => 'logistics',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'courier'              => [
                            'category'                  => 4215,
                            'description'               => 'Courier Shipping',
                            'category2'                 => 'logistics',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'warehousing'          => [
                            'category'                  => 4225,
                            'description'               => 'Public/Contract Warehousing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'distribution'         => [
                            'category'                  => 4214,
                            'description'               => 'Distribution Management',
                            'category2'                 => 'logistics',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'end_to_end_logistics' => [
                            'category'                  => 4214,
                            'description'               => 'End-to-end logistics',
                            'category2'                 => 'logistics',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'tours_and_travel'        => [
                    'description'   => 'Tours and Travel',
                    'subcategories' => [
                        'aviation'      => [
                            'category'                  => 4511,
                            'description'               => 'Aviation',
                            'category2'                 => 'travel_agency',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'accommodation' => [
                            'category'                  => 7011,
                            'description'               => 'Lodging and Accommodation',
                            'category2'                 => 'hospitality',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'ota'           => [
                            'category'                  => 4722,
                            'description'               => 'OTA',
                            'category2'                 => 'travel_agency',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'travel_agency' => [
                            'category'                  => 4722,
                            'description'               => 'Tours and Travel Agency',
                            'category2'                 => 'travel_agency',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                    ],
                ],
                'transport'               => [
                    'description'   => 'Transport',
                    'subcategories' => [
                        'cab_hailing'     => [
                            'category'                  => 4121,
                            'description'               => 'Cab/auto hailing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'bus'             => [
                            'category'                  => 4131,
                            'description'               => 'Bus ticketing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'train_and_metro' => [
                            'category'                  => 4112,
                            'description'               => 'Train and metro ticketing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'ecommerce'               => [
                    'description'   => 'Ecommerce',
                    'subcategories' => [
                        'ecommerce_marketplace'     => [
                            'category'                  => 5399,
                            'description'               => 'Horizontal Commerce/Marketplace',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'agriculture'               => [
                            'category'                  => 5193,
                            'description'               => 'Agricultural products',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'books'                     => [
                            'category'                  => 5942,
                            'description'               => 'Books and Publications',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'electronics_and_furniture' => [
                            'category'                  => 5732,
                            'description'               => 'Electronics and Furniture',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'coupons'                   => [
                            'category'                  => 7311,
                            'description'               => 'Coupons and deals',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'rental'                    => [
                            'category'                  => 7394,
                            'description'               => 'Product Rental',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'fashion_and_lifestyle'     => [
                            'category'                  => 5691,
                            'description'               => 'Fashion and Lifestyle',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'gifting'                   => [
                            'category'                  => 5193,
                            'description'               => 'Flowers and Gifts',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'grocery'                   => [
                            'category'                  => 5411,
                            'description'               => 'Grocery',
                            'category2'                 => 'grocery',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'baby_products'             => [
                            'category'                  => 5945,
                            'description'               => 'Baby Care and Toys',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'office_supplies'           => [
                            'category'                  => 5111,
                            'description'               => 'Office Supplies',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'wholesale'                 => [
                            'category'                  => 5300,
                            'description'               => 'Wholesale/Bulk trade',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'religious_products'        => [
                            'category'                  => 5973,
                            'description'               => 'Religious products',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'pet_products'              => [
                            'category'                  => 5995,
                            'description'               => 'Pet Care and Supplies',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'sports_products'           => [
                            'category'                  => 5941,
                            'description'               => 'Sports goods',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'arts_and_collectibles'     => [
                            'category'                  => 5971,
                            'description'               => 'Arts, crafts and collectibles',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'sexual_wellness_products'  => [
                            'category'                  => 5999,
                            'description'               => 'Sexual Wellness Products',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'drop_shipping'             => [
                            'category'                  => 5399,
                            'description'               => 'Dropshipping',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'crypto_machinery'          => [
                            'category'                  => 5999,
                            'description'               => 'Crypto Machinery',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'tobacco'                   => [
                            'category'                  => 5993,
                            'description'               => 'Tobacco',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'weapons_and_ammunitions'   => [
                            'category'                  => 5999,
                            'description'               => 'Weapons and Ammunitions',
                            'category2'                 => 'ecommerce',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                    ],
                ],
                'food'                    => [
                    'description'   => 'Food and Beverage',
                    'subcategories' => [
                        'online_food_ordering'          => [
                            'category'                  => 5811,
                            'description'               => 'Online Food Ordering',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'restaurant'                    => [
                            'category'                  => 5812,
                            'description'               => 'Restaurants',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'food_court'                    => [
                            'category'                  => 5814,
                            'description'               => 'Food Courts/Corporate Cafetaria',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'catering'                      => [
                            'category'                  => 5811,
                            'description'               => 'Catering Services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'alcohol'                       => [
                            'category'                  => 5813,
                            'description'               => 'Alcoholic Beverages',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'restaurant_search_and_booking' => [
                            'category'                  => 7299,
                            'description'               => 'Restaurant search and reservations',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'it_and_software'         => [
                    'description'   => 'IT and Software',
                    'subcategories' => [
                        'saas'                       => [
                            'category'                  => 5817,
                            'description'               => 'SaaS (Software as a service)',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'paas'                       => [
                            'category'                  => 5817,
                            'description'               => 'Platform as a service',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'iaas'                       => [
                            'category'                  => 5817,
                            'description'               => 'Infrastructure as a service',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'consulting_and_outsourcing' => [
                            'category'                  => 7392,
                            'description'               => 'Consulting and Outsourcing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'web_development'            => [
                            'category'                  => 7372,
                            'description'               => 'Web designing, development and hosting',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'technical_support'          => [
                            'category'                  => 7379,
                            'description'               => 'Technical Support',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                    ],
                ],
                'gaming'                  => [
                    'description'   => 'Gaming',
                    'subcategories' => [
                        'game_developer'     => [
                            'category'                  => 5816,
                            'description'               => 'Game developer and publisher',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'esports'            => [
                            'category'                  => 5816,
                            'description'               => 'E-sports',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'online_casino'      => [
                            'category'                  => 7801,
                            'description'               => 'Online Casino',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                        'fantasy_sports'     => [
                            'category'                  => 5816,
                            'description'               => 'Fantasy Sports',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'gaming_marketplace' => [
                            'category'                  => 5816,
                            'description'               => 'Game distributor/Marketplace',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                    ],
                ],
                'media_and_entertainment' => [
                    'description'   => 'Media and Entertainment',
                    'subcategories' => [
                        'video_on_demand'        => [
                            'category'                  => 5815,
                            'description'               => 'Video on demand',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'music_streaming'        => [
                            'category'                  => 5815,
                            'description'               => 'Music streaming services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'multiplex'              => [
                            'category'                  => 7832,
                            'description'               => 'Multiplexes',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'content_and_publishing' => [
                            'category'                  => 2741,
                            'description'               => 'Content and Publishing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'ticketing'              => [
                            'category'                  => 7832,
                            'description'               => 'Events and movie ticketing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'news'                   => [
                            'category'                  => 5994,
                            'description'               => 'News',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                    ],
                ],
                'services'                => [
                    'description'   => 'Services',
                    'subcategories' => [
                        'repair_and_cleaning'           => [
                            'category'                  => 7531,
                            'description'               => 'Repair and cleaning services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'interior_design_and_architect' => [
                            'category'                  => 8911,
                            'description'               => 'Interior Designing and Architect',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'movers_and_packers'            => [
                            'category'                  => 4214,
                            'description'               => 'Movers and Packers',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'legal'                         => [
                            'category'                  => 8111,
                            'description'               => 'Legal Services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'event_planning'                => [
                            'category'                  => 8999,
                            'description'               => 'Event planning services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'service_centre'                => [
                            'category'                  => 5511,
                            'description'               => 'Service Centre',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'whitelist',
                        ],
                        'consulting'                    => [
                            'category'                  => 7392,
                            'description'               => 'Consulting Services',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'ad_and_marketing'              => [
                            'category'                  => 7311,
                            'description'               => 'Ad and marketing agencies',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'services_classifieds'          => [
                            'category'                  => 7311,
                            'description'               => 'Services Classifieds',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'multi_level_marketing'         => [
                            'category'                  => 5964,
                            'description'               => 'Multi-level Marketing',
                            'category2'                 => 'others',
                            'activation_flow'           => 'blacklist',
                            'international_activation'  => 'blacklist',
                        ],
                    ],
                ],
                'housing'                 => [
                    'description'   => 'Housing and Real Estate',
                    'subcategories' => [
                        'developer'              => [
                            'category'                  => 6513,
                            'description'               => 'Developer',
                            'category2'                 => 'housing',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'facility_management'    => [
                            'category'                  => 7349,
                            'description'               => 'Facility Management Company',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'rwa'                    => [
                            'category'                  => 7349,
                            'description'               => 'RWA',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'coworking'              => [
                            'category'                  => 6513,
                            'description'               => 'Co-working spaces',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'realestate_classifieds' => [
                            'category'                  => 6513,
                            'description'               => 'Real estate classifieds',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                        'space_rental'           => [
                            'category'                  => 6513,
                            'description'               => 'Home or office rentals',
                            'category2'                 => 'others',
                            'activation_flow'           => 'whitelist',
                            'international_activation'  => 'greylist',
                        ],
                    ],
                ],
                'not_for_profit'          => [
                    'description'   => 'Not-For-Profit',
                    'subcategories' => [
                        'charity'     => [
                            'category'                  => 8398,
                            'description'               => 'Charity',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'educational' => [
                            'category'                  => 8398,
                            'description'               => 'Educational',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'religious'   => [
                            'category'                  => 8661,
                            'description'               => 'Religious',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'personal'    => [
                            'category'                  => 8398,
                            'description'               => 'Personal',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                    ],
                ],
                'social'                  => [
                    'description'   => 'Social',
                    'subcategories' => [
                        'matchmaking'           => [
                            'category'                  => 7273,
                            'description'               => 'Dating and Matrimony platforms',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'blacklist',
                        ],
                        'social_network'        => [
                            'category'                  => 8641,
                            'description'               => 'Social Network',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'messaging'             => [
                            'category'                  => 4821,
                            'description'               => 'Messaging and Communication',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'professional_network'  => [
                            'category'                  => 8699,
                            'description'               => 'Professional Network',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
                        ],
                        'neighbourhood_network' => [
                            'category'                  => 8699,
                            'description'               => 'Local/Neighbourhood network',
                            'category2'                 => 'others',
                            'activation_flow'           => 'greylist',
                            'international_activation'  => 'greylist',
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
        'request'  => [
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

    'testPostInstantActivation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'ecommerce',
                'business_subcategory'             => 'fashion_and_lifestyle',
                'archived'                         => 0,
                'submitted_at'                     => null,
                'activation_status'                => 'instantly_activated',
                'verification'                     => [
                    'status'              => 'disabled',
                    'disabled_reason'     => 'required_fields',
                    'required_fields'     => [
                        'address_proof_url',
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'business_pan_url',
                        'business_proof_url',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_address_url',
                    ],
                    'activation_progress' => 22,
                ],
                'can_submit'                       => false,
                'activated'                        => 1,
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
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
                        'address_proof_url',
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'business_pan_url',
                        'business_proof_url',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_address_url',
                    ],
                    'activation_progress' => 22,
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
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
                        'address_proof_url',
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'business_pan_url',
                        'business_proof_url',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_address_url',
                    ],
                    'activation_progress' => 22,
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCDE0000Z',
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCDE0000Z',
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
                'promoter_pan'         => 'ABCDE0000Z',
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

    'testPostInstantActivationByActivatedMerchant' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'services',
                'business_subcategory' => 'event_planning',
                'promoter_pan'         => 'ABCDE0000Z',
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response' => [
            'content' => [
                'contact_email'                    => 'test@razorpay.com',
                'promoter_pan'                     => 'ABCDE0000Z',
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'betting',
                'activation_flow'                  => 'blacklist',
                'activation_progress'              => 0,
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response' => [
            'content' => [
                'contact_email'                    => 'test@razorpay.com',
                'promoter_pan'                     => 'ABCDE0000Z',
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'mutual_fund',
                'activation_flow'                  => 'greylist',
                'activation_progress'              => 0,
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'activation_progress'              => 0,
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

    'testKycSubmissionForInstantlyActivatedMerchant' => [
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
                'promoter_pan'         => 'ABCDE0000Z',
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

    'changeActivationStatus' => [
        'request' => [
            'content' => [
                'activation_status'  => 'under_review',
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status'  => 'under_review',
            ],
        ],
    ],

    'submitKyc' => [
        'request'  => [
            'content' => [
                'submit' => true,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'submitted'         => true,
                'activation_status' => 'under_review',
                'can_submit'        => true,
            ],
        ],
    ],

    'testReleaseFundsWithoutBankAccount' => [
        'request' => [
            'content' => [
                'action' => 'release_funds'
            ],
            'method' => 'PUT',
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

    'testPostInstantActivationFetaureCheck' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'promoter_pan'         => 'ABCDE0000Z',
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
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'         => 'ABCDE0000Z',
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'fashion_and_lifestyle',
                'activated'            => 1,
                'activation_status'    => 'instantly_activated',
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithWhitelistedCategory' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'international'                    => true,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithWhitelistedCategoryAndNoWebsite' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => '',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'international'                    => false,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithWhitelistedCategoryAndWebsiteAlreadySet' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => '',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'international'                    => true,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithWhitelistedCategoryAndNoWebsiteAlreadySet' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://www.example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'financial_services',
                'business_subcategory'             => 'accounting',
                'international'                    => true,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithBlacklistedCategory' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'housing',
                'business_subcategory' => 'realestate_classifieds',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'housing',
                'business_subcategory'             => 'realestate_classifieds',
                'international'                    => false,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithGreylistedCategory' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'ecommerce',
                'business_subcategory' => 'wholesale',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'ecommerce',
                'business_subcategory'             => 'wholesale',
                'international'                    => false,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 1,
            ],
        ],
        'status_code' => 200,
    ],

    'testInternationalWithGreylistedCategoryAndNonInstantActivation' => [
        'request'     => [
            'method'  => 'POST',
            'url'     => '/merchant/instant_activation',
            'content' => [
                'business_category'    => 'not_for_profit',
                'business_subcategory' => 'charity',
                'promoter_pan'         => 'ABCDE0000Z',
                'business_name'        => 'business_name',
                'business_dba'         => 'test123',
                'business_type'        => 1,
                'business_model'       => '1245',
                'business_website'     => 'https://example.com',
            ],
        ],
        'response'    => [
            'content' => [
                'promoter_pan'                     => 'ABCDE0000Z',
                'gstin'                            => null,
                'p_gstin'                          => null,
                'business_category'                => 'not_for_profit',
                'business_subcategory'             => 'charity',
                'international'                    => false,
                'archived'                         => 0,
                'submitted_at'                     => null,
                'can_submit'                       => false,
                'activated'                        => 0,
            ],
        ],
        'status_code' => 200,
    ],

];
