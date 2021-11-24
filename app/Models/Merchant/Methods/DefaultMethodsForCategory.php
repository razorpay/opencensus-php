<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Terminal\Category;
use RZP\Models\Admin\Org\Entity as OrgEntity;

class DefaultMethodsForCategory
{
        const BLACKLISTED_METHODS = 'blacklisted_methods';
        const GREYLISTED_METHODS = 'greylisted_methods';
        const IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS = 'ignore_blacklisted_for_instrument_requests_methods'; // methods are blacklisted for auto enablement at the time of merchant activation but merchant should be able to request it via insttruments(Payment Methods) dashboard

        // map of category to auto prohibited methods i.e. methods which should not be enabled automatically by default
        // for the merchant belonging to that category, however can be enabled by admins
        // key is currently merchant category concatanated by category2, (in future business type etc can also come)
        // data is from this sheet - https://docs.google.com/spreadsheets/d/1eZMlh007Utp8JWGYGJ7Hk6zADSVlBWspHEzcGoKOydI/edit?usp=sharing
        const CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP = [
            'default' => [
                // Financial Services
                '6211'    => [
                    Category::MUTUAL_FUNDS => [
                        self::BLACKLISTED_METHODS   => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX],
                    ],
                    Category::SECURITIES => [
                        self::BLACKLISTED_METHODS   => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX],
                    ],
                ],
                '6051' => [
                    Category::CRYPTOCURRENCY => [
                        self::BLACKLISTED_METHODS =>  self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => []
                    ],
                ],
                '6012' => [
                    Category::LENDING => [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX, Entity::PHONEPE],
                    ],
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX, Entity::PHONEPE],
                    ],
                ],
                '6011' => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                ],

                '6300' => [
                    Category::INSURANCE => [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::PHONEPE],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX, Entity::PHONEPE],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS   =>  [Entity::AMEX],
                    ]
                ],
                '6010'  =>  [
                    Category::FOREX =>  [
                        self::BLACKLISTED_METHODS => [Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PREPAID_CARD, Entity::PAYLATER, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX,Entity::PHONEPE],
                    ],
                ],
                '8931'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                ],
                '6050'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [Entity::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::PHONEPE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX, Entity::PHONEPE],
                    ]
                ],
                '7361'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // Education
                '8220'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8211'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8299'  =>  [
                    Category::PVT_EDUCATION => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8351'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Healthcare
                '5912'  =>  [
                    Category::PHARMA    =>  [
                        // need to support phonepe.
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5122'  =>  [
                    Category::PHARMA    =>  [
                        // need to support phonepe.
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8062'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8071'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7298'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5499'  =>  [
                    Category::ECOMMERCE =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Utilities
                '4900'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4814'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4899'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4816'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                // Government Bodies
                '9399'  =>  [
                    Category::GOVERNMENT    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Logistics
                '4214'  =>  [
                    Category::LOGISTICS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4215'  =>  [
                    Category::LOGISTICS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4225'  =>  [
                    Category::OTHERS       =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Tours and Travel
                '4511'  =>  [
                    Category::TRAVEL_AGENCY     =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7011'  =>  [
                    Category::HOSPITALITY       =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4722'  =>  [
                    Category::TRAVEL_AGENCY     =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Transport
                '4121'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4131'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4112'  =>  [
                    Category::OTHERS  =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Ecommerce
                // 5399, ecommerce is in Healthcare as well, but have same value
                '5399'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5193'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5942'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5732'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7311'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7394'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5691'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5411'  =>  [
                    Category::GROCERY   => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5945'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5111'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5300'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5973'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5995'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5941'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5971'  =>  [
                    Category::ECOMMERCE => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5999'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::PHONEPE],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5993'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],


                // Food and Beverage
                '5811'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5812'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5814'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5813'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],

                    ]
                ],
                '7299'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // IT and Software
                '5817'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7372'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7379'  => [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // Games
                '5816'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::FREECHARGE, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7801'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],

                // Media and Entertainment
                '5815'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7832'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7994'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '2741'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5994'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Services
                '7531'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8911'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8111'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8999'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5511'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                // 7392, others is in 'IT and Software' as well, have same value
                '7392'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5964'  =>  [
                    Category::OTHERS => [
                        self::BLACKLISTED_METHODS => self::CATEGORY_DEPENDENT_METHODS, // cateogory is blacklisted
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::PHONEPE],
                    ],
                ],

                // Housing and Real Estate
                '6513'  =>  [
                    Category::HOUSING   =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ],
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '7349'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Not for profit
                '8398'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8661'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],

                // Social
                '7273'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8641'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4821'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '4829'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '8699'  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5094'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '5172'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5921'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5983'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
                '5944'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
                '7631'  =>  [
                    Category::ECOMMERCE    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::EMI, Entity::CARDLESS_EMI, Entity::HDFC_DEBIT_EMI],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],


                // if category, category2 does not matches with any of above, add methods which are to be blacklised for all category merchants in this
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [ENTITY::AMEX],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [Entity::AMEX],
                    ]
                ],
            ],

            // For VAS orgs, adding only category [others, other] as blacklisted methods are same for all categories
            OrgEntity::AXIS_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::EMI,
                            Entity::CARDLESS_EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE,
                            Entity::PAYTM,
                            Entity::PAYPAL,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
            OrgEntity::HDFC_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS => [
                            Entity::EMI,
                            Entity::CARDLESS_EMI,
                            Entity::PREPAID_CARD,
                            Entity::PAYLATER,
                            Entity::AIRTELMONEY,
                            Entity::FREECHARGE,
                            Entity::JIOMONEY,
                            Entity::MOBIKWIK,
                            Entity::MPESA,
                            Entity::OLAMONEY,
                            Entity::PAYUMONEY,
                            Entity::PAYZAPP,
                            Entity::SBIBUDDY,
                            Entity::PHONEPE,
                            Entity::PAYTM,
                            Entity::PAYPAL,
                            Entity::PHONEPE_SWITCH,
                            Entity::BANK_TRANSFER,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
            OrgEntity::ICICI_ORG_ID => [
                Category::OTHERS  =>  [
                    Category::OTHERS    =>  [
                        self::BLACKLISTED_METHODS =>[
                            Entity::PAYLATER,
                            Entity::PREPAID_CARD,
                        ],
                        self::GREYLISTED_METHODS =>[],
                        self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS => [],
                    ]
                ],
            ],
        ];

    // These are all the methods provided in the sheet https://docs.google.com/spreadsheets/d/1eZMlh007Utp8JWGYGJ7Hk6zADSVlBWspHEzcGoKOydI
    // This is needed because the sheet does not provide info about some other methods like nach, amazonpay etc. Letting default get picked for them
    const CATEGORY_DEPENDENT_METHODS = [
        Entity::CREDIT_CARD,
        Entity::DEBIT_CARD,
        Entity::AMEX,
        Entity::NETBANKING,
        Entity::UPI,
        Entity::EMI,
        Entity::CARDLESS_EMI, // should be enabled if EMI is enabled, have placed in all the same places where Entity::EMI is there in the above map
        Entity::PREPAID_CARD,
        Entity::PAYLATER,
        Entity::AIRTELMONEY,
        Entity::FREECHARGE,
        Entity::JIOMONEY,
        Entity::MOBIKWIK,
        Entity::MPESA,
        Entity::OLAMONEY,
        Entity::PAYUMONEY,
        Entity::PAYZAPP,
        Entity::SBIBUDDY,
        Entity::PHONEPE,
        Entity::PAYTM,
        Entity::PAYPAL,
        Entity::HDFC_DEBIT_EMI,
    ];

    const AUTO_DISABLED_METHODS = [
        Entity::PAYTM,
        Entity::PAYPAL,
    ];

    // Refer: https://docs.google.com/spreadsheets/d/1SbG4Zi29QFBjwN8QjKS47V13DW6LFbDk0U-OXQ1FLQo
    const ORG_WISE_METHODS_ENABLEMENT = [
        'default' => self::CATEGORY_DEPENDENT_METHODS,
        OrgEntity::AXIS_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
        // All except prepaid card and paylater
        OrgEntity::ICICI_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::AMEX,
            Entity::NETBANKING,
            Entity::UPI,
            Entity::EMI,
            Entity::CARDLESS_EMI, // should be enabled if EMI is enabled, have placed in all the same places where Entity::EMI is there in the above map
            Entity::AIRTELMONEY,
            Entity::FREECHARGE,
            Entity::JIOMONEY,
            Entity::MOBIKWIK,
            Entity::MPESA,
            Entity::OLAMONEY,
            Entity::PAYUMONEY,
            Entity::PAYZAPP,
            Entity::SBIBUDDY
        ],
        OrgEntity::HDFC_ORG_ID => [
            Entity::CREDIT_CARD,
            Entity::DEBIT_CARD,
            Entity::NETBANKING,
            Entity::UPI,
        ],
    ];

    public static function getDefaultMethodsFromMerchantCategories($category, $category2, string $orgId = 'default')
    {
        $orgLevelMethodsMap = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$orgId] ?? self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP['default'];

        $prohibitedMethods = $orgLevelMethodsMap[$category][$category2][self::BLACKLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::BLACKLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::BLACKLISTED_METHODS]);

        $orgWiseMethodsForEnablement = self::ORG_WISE_METHODS_ENABLEMENT[$orgId] ?? self::ORG_WISE_METHODS_ENABLEMENT['default'];

        $methodData = [];

        foreach($orgWiseMethodsForEnablement as $method)
        {
            $methodData[$method] = true;
        }

        foreach($prohibitedMethods as $prohibitedMethod)
        {
            $methodData[$prohibitedMethod] = false;
        }

        foreach(self::AUTO_DISABLED_METHODS as $disabledMethod)
        {
            $methodData[$disabledMethod] = false;
        }

        return $methodData;
    }

    // This is being used in get_auto_disabled_methods/<merchant_id> which TS calls to validate if we should create instrument or not
    public static function getDefaultDisabledMethodsForInstrumentRequestFromMerchantCategories($category, $category2)
    {
        // if $category,$category2 matches in above map, pick that
        // else if $category is there but $category2 doesn't matches, pick map[$category]['others']
        // else it means nothing matches, then we pick map['others']['others']
        $orgLevelMethodsMap = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP['default'];

        $disabledMethods = $orgLevelMethodsMap[$category][$category2][self::BLACKLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::BLACKLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::BLACKLISTED_METHODS]);

        $greyedMethods = $orgLevelMethodsMap[$category][$category2][self::GREYLISTED_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::GREYLISTED_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::GREYLISTED_METHODS]) ?? [];

        $ignoreMethods = $orgLevelMethodsMap[$category][$category2][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS] ?? ($orgLevelMethodsMap[$category][Category::OTHERS][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS] ?? $orgLevelMethodsMap[Category::OTHERS][Category::OTHERS][self::IGNORE_BLACKLIST_FOR_INSTRUMENT_REQUEST_METHODS]);

        $disabledMethods = array_merge($disabledMethods, $greyedMethods);

        $disabledMethodsForInstrumentRequest = array_diff($disabledMethods, $ignoreMethods);

        return array_values($disabledMethodsForInstrumentRequest);
    }
}
