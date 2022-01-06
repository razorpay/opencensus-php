<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    const DEFAULT         = 'default';

    const SECURITIES      = 'securities';
    const COMMODITIES     = 'commodities';
    const GROCERY         = 'grocery';
    const ECOMMERCE       = 'ecommerce';
    const EDUCATION       = 'education';
    const GAMING          = 'gaming';
    const GOVERNMENT      = 'government';
    const GOVT_EDUCATION  = 'govt_education';
    const PVT_EDUCATION   = 'pvt_education';
    const UTILITIES       = 'utilities';
    const CORPORATE       = 'corporate';
    const INSURANCE       = 'insurance';
    const HOUSING         = 'housing';
    const MUTUAL_FUNDS    = 'mutual_funds';
    const TRAVEL_AGENCY   = 'travel_agency';
    const RETAIL_SERVICES = 'retail_services';
    const PHARMA          = 'pharma';
    const LENDING         = 'lending';
    const CRYPTOCURRENCY  = 'cryptocurrency';
    const FINANCE         = 'finance';
    const FOREX           = 'forex';
    const HOSPITALITY     = 'hospitality';
    const LOGISTICS       = 'logistics';
    const OTHERS          = 'others';

    // newly added categories
    const FINANCIAL_SERVICES      = 'financial_services';
    const FOOD_AND_BEVERAGE       = 'food_and_beverage';
    const HEALTHCARE              = 'healthcare';
    const IT_AND_SOFTWARE         = 'it_and_software';
    const MEDIA_AND_ENTERTAINMENT = 'media_and_entertainment';
    const MONEY_TRANSFER          = 'money_transfer';
    const NOT_FOR_PROFIT          = 'not_for_profit';
    const REAL_ESTATE             = 'real_estate';
    const RECHARGES               = 'recharges';
    const SERVICES                = 'services';
    const SOCIAL                  = 'social';
    const TRANSPORT               = 'transport';
    const FUEL_GOVERNMENT         = 'fuel_government';
    const COMMUNICATION           = 'communication';
    const AUTO_RENTAL             = 'auto_rental';
    const FUEL_NON_GOVERNMENT     = 'fuel_nongovernment';
    const FUEL_HPCL               = 'fuel_hpcl';

    /**
     * Categories mapped to invalid will not find an
     * appropriate category to override. Only the category
     * allowed by default will be chosen
     */
    const INVALID = 'invalid';

    /**
     * The list of all possible categories that can be chosen
     * Broking requires an incompatible flag to be added to
     * prevent incompatible methods from choosing the default
     */
    const CATEGORIES_ALL = [
        self::SECURITIES,
        self::COMMODITIES,
        self::GROCERY,
        self::ECOMMERCE,
        self::GAMING,
        self::GOVERNMENT,
        self::GOVT_EDUCATION,
        self::PVT_EDUCATION,
        self::UTILITIES,
        self::CORPORATE,
        self::INSURANCE,
        self::HOUSING,
        self::MUTUAL_FUNDS,
        self::TRAVEL_AGENCY,
        self::PHARMA,
        self::LENDING,
        self::CRYPTOCURRENCY,
        self::FOREX,
        self::HOSPITALITY,
        self::LOGISTICS,
        self::OTHERS,
        self::FINANCIAL_SERVICES,
        self::FOOD_AND_BEVERAGE,
        self::HEALTHCARE,
        self::IT_AND_SOFTWARE,
        self::MEDIA_AND_ENTERTAINMENT,
        self::MONEY_TRANSFER,
        self::NOT_FOR_PROFIT,
        self::REAL_ESTATE,
        self::RECHARGES,
        self::SERVICES,
        self::SOCIAL,
        self::TRANSPORT,
    ];


    /**
     * INCOMPATIBLE categories will not allow terminals that
     * do not match the corresponding categories.
     * The list below is of the category2 on merchant entity.
     * The terminals marked null or default will be filtered
     * out.
     */
    const TPV = [
        self::SECURITIES,
        self::COMMODITIES,
    ];


    /**
     * By default Check for the name that is mentioned as is.
     * On Adding a new Key, a default should mandatorily be present
     * If it is renamed, then the new name that is mentioned will be
     * used to check for a network category
     *
     * Keys are Merchant categories, values are Network categories
     */

    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    const CATEGORIES = [
        Method::NETBANKING => [
            self::DEFAULT => self::ECOMMERCE,
            Gateway::NETBANKING_KOTAK => [
                // In Kotak, a utilities terminal needs to be added
                // which will be used to accept lending as well.
                self::DEFAULT   => self::ECOMMERCE,
                self::UTILITIES => self::UTILITIES,
                self::LENDING   => self::UTILITIES,
                // self::FOREX     => self::FINANCE,
            ],
            Gateway::BILLDESK => [
                self::DEFAULT => self::ECOMMERCE,
                self::FOREX   => self::HOUSING,
            ],
        ],
        Method::CARD => [
            self::DEFAULT        => self::ECOMMERCE,
            self::PHARMA         => self::ECOMMERCE,
            Gateway::AMEX     => [
                self::DEFAULT                => self::RETAIL_SERVICES,
                self::GROCERY                => 'sup_hypermrkt_deptstore',
                self::ECOMMERCE              => self::RETAIL_SERVICES,
                self::GOVT_EDUCATION         => self::EDUCATION,
                self::PVT_EDUCATION          => self::EDUCATION,
                self::CORPORATE              => self::INVALID,
                self::INSURANCE              => self::INSURANCE,
                self::HOUSING                => self::HOUSING,
                self::UTILITIES              => self::UTILITIES,
                self::FUEL_GOVERNMENT        => self::FUEL_GOVERNMENT,
                self::COMMUNICATION          => self::COMMUNICATION,
                self::AUTO_RENTAL            => self::AUTO_RENTAL,
                self::FUEL_NON_GOVERNMENT    => self::FUEL_NON_GOVERNMENT,
                self::FUEL_HPCL              => self::FUEL_HPCL,

            ],
        ],
        Method::EMANDATE => [
            self::DEFAULT => self::ECOMMERCE,
        ]
    ];

    public static function isMerchantCategoryValid($category)
    {
        return in_array($category, self::CATEGORIES_ALL, true);
    }

    public static function isNetworkCategoryValid(string $networkCategory, $method, string $gateway): bool
    {
        if ($networkCategory === self::INVALID)
        {
            return false;
        }

        // Get the correct constant for the terminal
        // get the values array and check in array
        $allCategories = array_combine(self::CATEGORIES_ALL, self::CATEGORIES_ALL);

        if (isset(self::CATEGORIES[$method][$gateway]) === true)
        {
            foreach (self::CATEGORIES[$method][$gateway] as $category2 => $networkCategory)
            {
                $allCategories[$category2] = $networkCategory;
            }
        }

        // No need to worry about duplicates. we only need values
        $values = array_values($allCategories);

        return in_array($networkCategory, $values, true);
    }

    public static function getDefaultForMethodAndGateway($method, $gateway)
    {
        return self::getCategoryForMethodAndGateway($method, $gateway, self::DEFAULT);
    }

    public static function getCategoryForMethodAndGateway($method, $gateway, $category2)
    {
        $networkCategory = self::getDefaultNetworkCategory($category2);

        if (isset(self::CATEGORIES[$method][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$category2];
        }

        if (isset(self::CATEGORIES[$method][$gateway][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$gateway][$category2];
        }

        return $networkCategory;
    }

    public static function getTPVCategories()
    {
        return self::TPV;
    }

    public static function getIncompatibleCategories()
    {
        return self::TPV;
    }

    public static function isMerchantCategoryTPV($category2)
    {
        return in_array($category2, self::TPV, true);
    }

    public static function isMerchantCategoryIncompatible($category)
    {
        $incompatibleCategories = self::getIncompatibleCategories();

        return in_array($category, $incompatibleCategories, true);
    }

    protected static function getDefaultNetworkCategory($category2)
    {
        $networkCategory = null;

        if ($category2 !== self::DEFAULT)
        {
            $networkCategory = $category2;
        }

        return $networkCategory;
    }
}
