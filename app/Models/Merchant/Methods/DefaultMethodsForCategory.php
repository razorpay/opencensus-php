<?php

namespace RZP\Models\Merchant\Methods;

use RZP\Models\Terminal\Category;

class DefaultMethodsForCategory
{
        // map of category to auto prohibited methods i.e. methods which should not be enabled automatically by default
        // for the merchant belonging to that category, however can be enabled by admins 
        // key is currently merchant category concatanated by category2, (in future business type etc can also come)
        // data is from this sheet - https://docs.google.com/spreadsheets/d/1eZMlh007Utp8JWGYGJ7Hk6zADSVlBWspHEzcGoKOydI/edit?usp=sharing
        const CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP = [
        
            // Financial Services
            '6211'    => [
                Category::MUTUAL_FUNDS => [
                    Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::PREPAID_CARD, Entity::PAYLATER
                ],
                Category::SECURITIES => [
                    Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::PREPAID_CARD, Entity::PAYLATER
                ],
            ],
            '6051' => [
                Category::CRYPTOCURRENCY => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
            '6012' => [
                Category::LENDING => [
                    Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::PREPAID_CARD, Entity::PAYLATER
                ],
                Category::OTHERS => [
                    Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::PREPAID_CARD, Entity::PAYLATER
                ]
            ],
            '6300' => [
                Category::INSURANCE => []
            ],
            '6010'  =>  [
                Category::FOREX =>  [
                    Entity::CREDIT_CARD, Entity::AMEX, Entity::EMI, Entity::PREPAID_CARD, Entity::PAYLATER
                ]
            ],
            '8931'  =>  [
                Category::OTHERS    =>  [
                    Entity::AMEX, Entity::EMI
                ]
            ],
            '6050'  =>  [
                Category::OTHERS    =>  [
                    Entity::EMI
                ]
            ],
            '7801'  =>  [
                Category::OTHERS    =>  self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
            '7361'  =>  [
                Category::OTHERS    =>  self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
    
            // Education
            '8220'  =>  [
                Category::PVT_EDUCATION => []
            ],
            '8211'  =>  [
                Category::PVT_EDUCATION => []
            ],
            '8299'  =>  [
                Category::PVT_EDUCATION => []
            ],
            '8351'  =>  [
                Category::OTHERS => []
            ],
    
            // Healthcare
            '5912'  =>  [
                Category::PHARMA    =>  self::CATEGORY_DEPENDENT_METHODS
            ],
            '8062'  =>  [
                Category::OTHERS    =>  []
            ],
            '8071'  =>  [
                Category::OTHERS    =>  []
            ],
            '7298'  =>  [
                Category::OTHERS    =>  []
            ],
            '5499'  =>  [
                Category::ECOMMERCE =>  []
            ],
    
            // Utilities
            '4900'  =>  [
                Category::OTHERS    =>  []
            ],
            '4814'  =>  [
                Category::OTHERS    =>  []
            ],
            '4899'  =>  [
                Category::OTHERS    =>  []
            ],
            '4816'  =>  [
                Category::OTHERS    =>  []
            ],
            '4814'  =>  [
                Category::OTHERS    =>  []
            ],
    
            // Government Bodies
            '9399'  =>  [
                Category::GOVERNMENT    =>  []
            ],
    
            // Logistics
            '4214'  =>  [
                Category::LOGISTICS    =>  []
            ],
            '4215'  =>  [
                Category::LOGISTICS    =>  []
            ],
            '4225'  =>  [
                Category::OTHERS       =>  []
            ],
    
            // Tours and Travel
            '4511'  =>  [
                Category::TRAVEL_AGENCY     =>  [
                    Entity::AMEX
                ]
            ],
            '7011'  =>  [
                Category::HOSPITALITY       =>  []
            ],
            '4722'  =>  [
                Category::TRAVEL_AGENCY     =>  [
                    Entity::AMEX
                ]
            ],
    
            // Transport
            '4121'  =>  [
                Category::OTHERS  =>  []
            ],
            '4131'  =>  [
                Category::OTHERS  =>  []
            ],
            '4112'  =>  [
                Category::OTHERS  =>  []
            ],
    
            // Ecommerce
            // 5399, ecommerce is in Healthcare as well, but have same value
            '5399'  =>  [
                Category::ECOMMERCE => []
            ],
            '5193'  =>  [
                Category::ECOMMERCE => []
            ],
            '5942'  =>  [
                Category::ECOMMERCE => []
            ],
            '5732'  =>  [
                Category::ECOMMERCE => []
            ],
            '7311'  =>  [
                Category::ECOMMERCE => []
            ],
            '7394'  =>  [
                Category::ECOMMERCE => []
            ],
            '5691'  =>  [
                Category::ECOMMERCE => []
            ],
            '5193'  =>  [
                Category::ECOMMERCE => []
            ],
            '5411'  =>  [
                Category::GROCERY   => []
            ],
            '5945'  =>  [
                Category::ECOMMERCE => []
            ],
            '5111'  =>  [
                Category::ECOMMERCE => []
            ],
            '5300'  =>  [
                Category::ECOMMERCE => []
            ],
            '5973'  =>  [
                Category::ECOMMERCE => []
            ],
            '5995'  =>  [
                Category::ECOMMERCE => []
            ],
            '5941'  =>  [
                Category::ECOMMERCE => []
            ],
            '5971'  =>  [
                Category::ECOMMERCE => [
                    Entity::EMI
                ]
            ],
            '5999'  => [
                Category::OTHERS => []
            ],
            '5993'  => [
                Category::OTHERS => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
    
    
            // Food and Beverage
            '5811'  =>  [
                Category::OTHERS    =>  []
            ],
            '5812'  =>  [
                Category::OTHERS    =>  []
            ],
            '5814'  =>  [
                Category::OTHERS    =>  []
            ],
            '5813'  =>  [
                Category::OTHERS => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
            '7299'  =>  [
                Category::OTHERS    =>  []
            ],
    
            // IT and Software
            '5817'  =>  [
                Category::OTHERS    =>  []
            ],
            '7372'  =>  [
                Category::OTHERS    =>  []
            ],
            '7379'  => [
                Category::OTHERS => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
    
            // Games
            '5816'  =>  [
                Category::OTHERS    =>  [
                    Entity::EMI, Entity::FREECHARGE
                ]
            ],
            '7801'  =>  [
                Category::OTHERS => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
            
            // Media and Entertainment
            '5815'  =>  [
                Category::OTHERS    =>  []
            ],
            '7832'  =>  [
                Category::OTHERS    =>  []
            ],
            '2741'  =>  [
                Category::OTHERS    =>  []
            ],
            '5994'  =>  [
                Category::OTHERS    =>  []
            ],
    
            // Services
            '7531'  =>  [
                Category::OTHERS    =>  []
            ],
            '8911'  =>  [
                Category::OTHERS    =>  []
            ],
            '4214'  =>  [
                Category::OTHERS    =>  []
            ],
            '8111'  =>  [
                Category::OTHERS    =>  []
            ],
            '8999'  =>  [
                Category::OTHERS    =>  []
            ],
            '5511'  =>  [
                Category::OTHERS    =>  []
            ],
            // 7392, others is in 'IT and Software' as well, have same value
            '7392'  =>  [
                Category::OTHERS    =>  []
            ],
            '7311'  =>  [
                Category::OTHERS    =>  []
            ],
            '5964'  =>  [
                Category::OTHERS => self::CATEGORY_DEPENDENT_METHODS // blacklisted
            ],
    
            // Housing and Real Estate
            '6513'  =>  [
                Category::HOUSING   =>  [
                    Entity::AMEX
                ]
            ],
            '7349'  =>  [
                Category::OTHERS    =>  []
            ],
            '6513'  =>  [
                Category::OTHERS    =>  []
            ],
    
            // Not for profit
            '8398'  =>  [
                Category::OTHERS    =>  [
                    Entity::EMI
                ]
            ],
            '8661'  =>  [
                Category::OTHERS    =>  [
                    Entity::EMI
                ]
            ],
    
            // Social
            '7273'  =>  [
                Category::OTHERS    =>  []
            ],
            '8641'  =>  [
                Category::OTHERS    =>  []
            ],
            '4821'  =>  [
                Category::OTHERS    =>  []
            ],
            '8699'  =>  [
                Category::OTHERS    =>  []
            ],
    
            // Others
            '5399'  =>  [
                Category::OTHERS    =>  [
                    Entity::EMI
                ]
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
    ];

    public static function getDefaultMethodsFromMerchantCategories($category, $category2)
    {
        if ((empty($category) === true) or (empty($category2) === true))
        {
            return null;
        }

        $prohibitedMethods = self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$category][$category2] ?? (self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$category][Category::OTHERS] ?? null);
        
        // If $prohibitedMethods is null, it means category is not there in the above map
        if ($prohibitedMethods === null)
        {
            return null;
        }

        $methodData = [];

        foreach(self::CATEGORY_DEPENDENT_METHODS as $method)
        {
            $methodData[$method] = true;
        }

        foreach($prohibitedMethods as $prohibitedMethod)
        {
            $methodData[$prohibitedMethod] = false;

        }

        return $methodData;
    }

    public static function getDefaultDisabledMethodsFromMerchantCategories($category, $category2)
    {
        return self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$category][$category2] ?? (self::CATEGORY_DEFAULT_PROHIBITED_METHODS_MAP[$category][Category::OTHERS] ?? null);
    }
}