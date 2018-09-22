<?php

namespace RZP\Tests\Functional\Merchant\helpers;

return array(
        'financial_services' =>
            array(
                'description' => 'Financial Services',
                'subcategories' =>
                    array(
                        'mutual_fund' =>
                            array(
                                'mcc_code' => 6211,
                                'description' => 'Mutual Fund',
                                'category' => 'Mutual funds',
                                'activation_category' => 'Greylisted',
                            ),
                        'lending' =>
                            array(
                                'mcc_code' => 6012,
                                'description' => 'Lending',
                                'category' => 'Lending',
                                'activation_category' => 'Greylisted',
                            ),
                        'cryptocurrency' =>
                            array(
                                'mcc_code' => 6051,
                                'description' => 'Cryptocurrency',
                                'category' => 'Cryptocurrency',
                                'activation_category' => 'Blacklisted',
                            ),
                        'insurance' =>
                            array(
                                'mcc_code' => 6300,
                                'description' => 'Insurance',
                                'category' => 'Insurance',
                                'activation_category' => 'Greylisted',
                            ),
                        'nbfc' =>
                            array(
                                'mcc_code' => 6012,
                                'description' => 'NBFC',
                                'category' => 'Lending',
                                'activation_category' => 'Greylisted',
                            ),
                        'cooperatives' =>
                            array(
                                'mcc_code' => 6012,
                                'description' => 'Cooperatives',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'pension_fund' =>
                            array(
                                'mcc_code' => 6012,
                                'description' => 'Pension Fund',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'forex' =>
                            array(
                                'mcc_code' => 6010,
                                'description' => 'Forex',
                                'category' => 'Forex',
                                'activation_category' => 'Greylisted',
                            ),
                        'securities' =>
                            array(
                                'mcc_code' => 6211,
                                'description' => 'Securities',
                                'category' => 'Securities',
                                'activation_category' => 'Greylisted',
                            ),
                        'commodities' =>
                            array(
                                'mcc_code' => 6211,
                                'description' => 'Commodities',
                                'category' => 'Securities',
                                'activation_category' => 'Greylisted',
                            ),
                        'accounting' =>
                            array(
                                'mcc_code' => 8931,
                                'description' => 'Accounting and Taxes',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'financial_advisor' =>
                            array(
                                'mcc_code' => 8931,
                                'description' => 'Financial and Investment Advisors/Financial Advisor',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'crowdfunding' =>
                            array(
                                'mcc_code' => 6050,
                                'description' => 'Crowdfunding Platform',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'trading' =>
                            array(
                                'mcc_code' => 6211,
                                'description' => 'Stock Brokerage and Trading',
                                'category' => 'Securities',
                                'activation_category' => 'Greylisted',
                            ),
                        'betting' =>
                            array(
                                'mcc_code' => 7801,
                                'description' => 'Betting',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                        'get_rich_schemes' =>
                            array(
                                'mcc_code' => 7361,
                                'description' => 'Get Rich Schemes',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                    ),
            ),
        'education' =>
            array(
                'description' => 'Education',
                'subcategories' =>
                    array(
                        'college' =>
                            array(
                                'mcc_code' => 8220,
                                'description' => 'College',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'schools' =>
                            array(
                                'mcc_code' => 8211,
                                'description' => 'Schools',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'university' =>
                            array(
                                'mcc_code' => 8220,
                                'description' => 'University',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'professional_courses' =>
                            array(
                                'mcc_code' => 8299,
                                'description' => 'Professional Courses',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'distance_learning' =>
                            array(
                                'mcc_code' => 8299,
                                'description' => 'Distance Learning',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'coaching' =>
                            array(
                                'mcc_code' => 8299,
                                'description' => 'Coaching Institute',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                        'elearning' =>
                            array(
                                'mcc_code' => 8299,
                                'description' => 'E-Learning',
                                'category' => 'Pvt Education',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'healthcare' =>
            array(
                'description' => 'Healthcare',
                'subcategories' =>
                    array(
                        'pharmacy' =>
                            array(
                                'mcc_code' => 5912,
                                'description' => 'Pharmacy',
                                'category' => 'Pharma',
                                'activation_category' => 'Greylisted',
                            ),
                        'clinic' =>
                            array(
                                'mcc_code' => 8062,
                                'description' => 'Clinic',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'hospital' =>
                            array(
                                'mcc_code' => 8062,
                                'description' => 'Hospital',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'lab' =>
                            array(
                                'mcc_code' => 8071,
                                'description' => 'Lab',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'dietician' =>
                            array(
                                'mcc_code' => 7298,
                                'description' => 'Dietician/Diet Services',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'fitness' =>
                            array(
                                'mcc_code' => 7298,
                                'description' => 'Gym and Fitness',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'health_coaching' =>
                            array(
                                'mcc_code' => 7298,
                                'description' => 'Health and Lifestyle Coaching',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'health_products' =>
                            array(
                                'mcc_code' => 5499,
                                'description' => 'Health Products',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'healthcare_marketplace' =>
                            array(
                                'mcc_code' => 5399,
                                'description' => 'Marketplace/Aggregator',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'utilities' =>
            array(
                'description' => 'Utilities',
                'subcategories' =>
                    array(
                        'electricity' =>
                            array(
                                'mcc_code' => 4900,
                                'description' => 'Electricity',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'gas' =>
                            array(
                                'mcc_code' => 4900,
                                'description' => 'Gas',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'telecom' =>
                            array(
                                'mcc_code' => 4814,
                                'description' => 'Telecom Service Provider',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'water' =>
                            array(
                                'mcc_code' => 4900,
                                'description' => 'Water',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'cable' =>
                            array(
                                'mcc_code' => 4899,
                                'description' => 'Cable operator',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'broadband' =>
                            array(
                                'mcc_code' => 4899,
                                'description' => 'Broadband',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'dth' =>
                            array(
                                'mcc_code' => 4899,
                                'description' => 'DTH',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'internet_provider' =>
                            array(
                                'mcc_code' => 4816,
                                'description' => 'Internet service provider',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'bill_and_recharge_aggregators' =>
                            array(
                                'mcc_code' => 4814,
                                'description' => 'Bill Payment and Recharge Aggregators',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                    ),
            ),
        'government' =>
            array(
                'description' => 'Government Bodies',
                'subcategories' =>
                    array(
                        'central' =>
                            array(
                                'mcc_code' => 9399,
                                'description' => 'Central Department',
                                'category' => 'Government',
                                'activation_category' => 'Whitelisted',
                            ),
                        'state' =>
                            array(
                                'mcc_code' => 9399,
                                'description' => 'State Department',
                                'category' => 'Government',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'logistics' =>
            array(
                'description' => 'Logistics',
                'subcategories' =>
                    array(
                        'freight' =>
                            array(
                                'mcc_code' => 4214,
                                'description' => 'Freight Consolidation/Management',
                                'category' => 'Logistics',
                                'activation_category' => 'Whitelisted',
                            ),
                        'courier' =>
                            array(
                                'mcc_code' => 4215,
                                'description' => 'Courier Shipping',
                                'category' => 'Logistics',
                                'activation_category' => 'Whitelisted',
                            ),
                        'warehousing' =>
                            array(
                                'mcc_code' => 4225,
                                'description' => 'Public/Contract Warehousing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'distribution' =>
                            array(
                                'mcc_code' => 4214,
                                'description' => 'Distribution Management',
                                'category' => 'Logistics',
                                'activation_category' => 'Whitelisted',
                            ),
                        'end_to_end_logistics' =>
                            array(
                                'mcc_code' => 4214,
                                'description' => 'End-to-end logistics',
                                'category' => 'Logistics',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'tours_and_travel' =>
            array(
                'description' => 'Tours and Travel',
                'subcategories' =>
                    array(
                        'aviation' =>
                            array(
                                'mcc_code' => 4511,
                                'description' => 'Aviation',
                                'category' => 'Travel agency',
                                'activation_category' => 'Greylisted',
                            ),
                        'accommodation' =>
                            array(
                                'mcc_code' => 7011,
                                'description' => 'Lodging and Accommodation',
                                'category' => 'Hospitality',
                                'activation_category' => 'Whitelisted',
                            ),
                        'ota' =>
                            array(
                                'mcc_code' => 4722,
                                'description' => 'OTA',
                                'category' => 'Travel agency',
                                'activation_category' => 'Greylisted',
                            ),
                        'travel_agency' =>
                            array(
                                'mcc_code' => 4722,
                                'description' => 'Tours and Travel Agency',
                                'category' => 'Travel agency',
                                'activation_category' => 'Greylisted',
                            ),
                    ),
            ),
        'transport' =>
            array(
                'description' => 'Transport',
                'subcategories' =>
                    array(
                        'cab_hailing' =>
                            array(
                                'mcc_code' => 4121,
                                'description' => 'Cab/auto hailing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'bus' =>
                            array(
                                'mcc_code' => 4131,
                                'description' => 'Bus ticketing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'train_and_metro' =>
                            array(
                                'mcc_code' => 4112,
                                'description' => 'Train and metro ticketing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'ecommerce' =>
            array(
                'description' => 'Ecommerce',
                'subcategories' =>
                    array(
                        'ecommerce_marketplace' =>
                            array(
                                'mcc_code' => 5399,
                                'description' => 'Horizontal Commerce/Marketplace',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'agriculture' =>
                            array(
                                'mcc_code' => 5193,
                                'description' => 'Agricultural products',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'books' =>
                            array(
                                'mcc_code' => 5942,
                                'description' => 'Books and Publications',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'electronics_and_furniture' =>
                            array(
                                'mcc_code' => 5732,
                                'description' => 'Electronics and Furniture',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'coupons' =>
                            array(
                                'mcc_code' => 7311,
                                'description' => 'Coupons and deals',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'rental' =>
                            array(
                                'mcc_code' => 7394,
                                'description' => 'Product Rental',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'fashion_and_lifestyle' =>
                            array(
                                'mcc_code' => 5691,
                                'description' => 'Fashion and Lifestyle',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'gifting' =>
                            array(
                                'mcc_code' => 5193,
                                'description' => 'Flowers and Gifts',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'grocery' =>
                            array(
                                'mcc_code' => 5411,
                                'description' => 'Grocery',
                                'category' => 'Grocery',
                                'activation_category' => 'Greylisted',
                            ),
                        'baby_products' =>
                            array(
                                'mcc_code' => 5945,
                                'description' => 'Baby Care and Toys',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'office_supplies' =>
                            array(
                                'mcc_code' => 5111,
                                'description' => 'Office Supplies',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'wholesale' =>
                            array(
                                'mcc_code' => 5300,
                                'description' => 'Wholesale/Bulk trade',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'religious_products' =>
                            array(
                                'mcc_code' => 5973,
                                'description' => 'Religious products',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'pet_products' =>
                            array(
                                'mcc_code' => 5995,
                                'description' => 'Pet Care and Supplies',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'sports_products' =>
                            array(
                                'mcc_code' => 5941,
                                'description' => 'Sports goods',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'arts_and_collectibles' =>
                            array(
                                'mcc_code' => 5971,
                                'description' => 'Arts, crafts and collectibles',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Whitelisted',
                            ),
                        'sexual_wellness_product' =>
                            array(
                                'mcc_code' => 5999,
                                'description' => 'Sexual Wellness Products',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Greylisted',
                            ),
                        'drop_shipping' =>
                            array(
                                'mcc_code' => 5399,
                                'description' => 'Dropshipping',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Greylisted',
                            ),
                        'crypto_machinery ' =>
                            array(
                                'mcc_code' => 5999,
                                'description' => 'Crypto Machinery',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Blacklisted',
                            ),
                        'tobacco' =>
                            array(
                                'mcc_code' => 5993,
                                'description' => 'Tobacco',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Blacklisted',
                            ),
                        'weapons_and_ammunitions' =>
                            array(
                                'mcc_code' => 5999,
                                'description' => 'Weapons and Ammunitions',
                                'category' => 'Ecommerce',
                                'activation_category' => 'Blacklisted',
                            ),
                    ),
            ),
        'food' =>
            array(
                'description' => 'Food and Beverage',
                'subcategories' =>
                    array(
                        'online_food_ordering' =>
                            array(
                                'mcc_code' => 5811,
                                'description' => 'Online Food Ordering',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'restaurant' =>
                            array(
                                'mcc_code' => 5812,
                                'description' => 'Restaurants',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'food_court' =>
                            array(
                                'mcc_code' => 5814,
                                'description' => 'Food Courts/Corporate Cafetaria',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'catering' =>
                            array(
                                'mcc_code' => 5811,
                                'description' => 'Catering Services',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'alcohol' =>
                            array(
                                'mcc_code' => 5813,
                                'description' => 'Alcoholic Beverages',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                        'restaurant_search_and_booking' =>
                            array(
                                'mcc_code' => 7299,
                                'description' => 'Restaurant search and reservations',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'it_and_software' =>
            array(
                'description' => 'IT and Software',
                'subcategories' =>
                    array(
                        'saas' =>
                            array(
                                'mcc_code' => 5817,
                                'description' => 'SaaS (Software as a service)',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'paas' =>
                            array(
                                'mcc_code' => 5817,
                                'description' => 'Platform as a service',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'iaas' =>
                            array(
                                'mcc_code' => 5817,
                                'description' => 'Infrastructure as a service',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'consulting_and_outsourcing' =>
                            array(
                                'mcc_code' => 7392,
                                'description' => 'Consulting and Outsourcing',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'web_development' =>
                            array(
                                'mcc_code' => 7372,
                                'description' => 'Web designing, development and hosting',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'technical_support' =>
                            array(
                                'mcc_code' => 7379,
                                'description' => 'Technical Support',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                    ),
            ),
        'gaming' =>
            array(
                'description' => 'Gaming',
                'subcategories' =>
                    array(
                        'game_developer' =>
                            array(
                                'mcc_code' => 5816,
                                'description' => 'Game developer and publisher',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'esports' =>
                            array(
                                'mcc_code' => 5816,
                                'description' => 'E-sports',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'online_casino' =>
                            array(
                                'mcc_code' => 7801,
                                'description' => 'Online Casino',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                        'fantasy_sports' =>
                            array(
                                'mcc_code' => 5816,
                                'description' => 'Fantasy Sports',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'gaming_marketplace' =>
                            array(
                                'mcc_code' => 5816,
                                'description' => 'Game distributor/Marketplace',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                    ),
            ),
        'media_and_entertainment' =>
            array(
                'description' => 'Media and Entertainment',
                'subcategories' =>
                    array(
                        'video_on_demand' =>
                            array(
                                'mcc_code' => 5815,
                                'description' => 'Video on demand',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'music_streaming' =>
                            array(
                                'mcc_code' => 5815,
                                'description' => 'Music streaming services',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'multiplex' =>
                            array(
                                'mcc_code' => 7832,
                                'description' => 'Multiplexes',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'content_and_publishing' =>
                            array(
                                'mcc_code' => 2741,
                                'description' => 'Content and Publishing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'ticketing' =>
                            array(
                                'mcc_code' => 7832,
                                'description' => 'Events and movie ticketing',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'news' =>
                            array(
                                'mcc_code' => 5994,
                                'description' => 'News',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'services' =>
            array(
                'description' => 'Services',
                'subcategories' =>
                    array(
                        'repair_and_cleaning' =>
                            array(
                                'mcc_code' => 7531,
                                'description' => 'Repair and cleaning services',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'interior_design_and_architect' =>
                            array(
                                'mcc_code' => 8911,
                                'description' => 'Interior Designing and Architect',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'movers_and_packers' =>
                            array(
                                'mcc_code' => 4214,
                                'description' => 'Movers and Packers',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'legal' =>
                            array(
                                'mcc_code' => 8111,
                                'description' => 'Legal Services',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'event_planning' =>
                            array(
                                'mcc_code' => 8999,
                                'description' => 'Event planning services',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'service_centre' =>
                            array(
                                'mcc_code' => 5511,
                                'description' => 'Service Centre',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'consulting' =>
                            array(
                                'mcc_code' => 7392,
                                'description' => 'Consulting Services',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'ad_and_marketing' =>
                            array(
                                'mcc_code' => 7311,
                                'description' => 'Ad and marketing agencies',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'services_classifieds' =>
                            array(
                                'mcc_code' => 7311,
                                'description' => 'Services Classifieds',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'multi_level_marketing' =>
                            array(
                                'mcc_code' => 5964,
                                'description' => 'Multi-level Marketing',
                                'category' => 'Others',
                                'activation_category' => 'Blacklisted',
                            ),
                    ),
            ),
        'housing' =>
            array(
                'description' => 'Housing and Real Estate',
                'subcategories' =>
                    array(
                        'developer' =>
                            array(
                                'mcc_code' => 6513,
                                'description' => 'Developer',
                                'category' => 'Housing',
                                'activation_category' => 'Greylisted',
                            ),
                        'facility_management' =>
                            array(
                                'mcc_code' => 7349,
                                'description' => 'Facility Management Company',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'rwa' =>
                            array(
                                'mcc_code' => 7349,
                                'description' => 'RWA',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'coworking' =>
                            array(
                                'mcc_code' => 6513,
                                'description' => 'Co-working spaces',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'realestate_classifieds' =>
                            array(
                                'mcc_code' => 6513,
                                'description' => 'Real estate classifieds',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                        'space_rental' =>
                            array(
                                'mcc_code' => 6513,
                                'description' => 'Home or office rentals',
                                'category' => 'Others',
                                'activation_category' => 'Whitelisted',
                            ),
                    ),
            ),
        'not_for_profit' =>
            array(
                'description' => 'Not-For-Profit',
                'subcategories' =>
                    array(
                        'charity' =>
                            array(
                                'mcc_code' => 8398,
                                'description' => 'Charity',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'educational' =>
                            array(
                                'mcc_code' => 8398,
                                'description' => 'Educational',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'religious' =>
                            array(
                                'mcc_code' => 8661,
                                'description' => 'Religious',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'personal' =>
                            array(
                                'mcc_code' => 8398,
                                'description' => 'Personal',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                    ),
            ),
        'social' =>
            array(
                'description' => 'Social',
                'subcategories' =>
                    array(
                        'matchmaking' =>
                            array(
                                'mcc_code' => 7273,
                                'description' => 'Dating and Matrimony platforms',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'social_network' =>
                            array(
                                'mcc_code' => 8641,
                                'description' => 'Social Network',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'messaging' =>
                            array(
                                'mcc_code' => 4821,
                                'description' => 'Messaging and Communication',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'professional_network' =>
                            array(
                                'mcc_code' => 8699,
                                'description' => 'Professional Network',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                        'neighbourhood_network' =>
                            array(
                                'mcc_code' => 8699,
                                'description' => 'Local/Neighbourhood network',
                                'category' => 'Others',
                                'activation_category' => 'Greylisted',
                            ),
                    ),
            ));
