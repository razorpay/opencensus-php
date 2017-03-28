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
     * If it is renamed, then the new name that is mentioned will be
     * used to check for a network category
     *
     * Keys are Merchant categories, values are Network categories
     */
    const CATEGORIES = [
        Method::NETBANKING => [
            self::DEFAULT => self::ECOMMERCE,
            IFSC::KKBK => [
                // In Kotak, a utilities terminal needs to be added
                // which will be used to accept lending as well.
                self::DEFAULT   => self::ECOMMERCE,
                self::UTILITIES => self::UTILITIES,
                self::LENDING   => self::UTILITIES,
            ]
        ],
        Method::CARD => [
            self::DEFAULT => self::ECOMMERCE,
            self::PHARMA  => self::ECOMMERCE,
            Network::AMEX     => [
                self::DEFAULT        => self::RETAIL_SERVICES,
                self::GROCERY        => 'sup_hypermrkt_deptstore',
                self::ECOMMERCE      => self::RETAIL_SERVICES,
                self::GOVT_EDUCATION => self::EDUCATION,
                self::PVT_EDUCATION  => self::EDUCATION,
                self::CORPORATE      => self::INVALID,
                self::INSURANCE      => self::INSURANCE,
                self::HOUSING        => self::HOUSING,
            ],
        ]
    ];

    public static function isMerchantCategoryValid($category)
    {
        return in_array($category, self::CATEGORIES_ALL, true);
    }

    public static function isNetworkCategoryValid($input)
    {
        $category = $input[Entity::NETWORK_CATEGORY];

        if ($category === self::INVALID)
        {
            return false;
        }

        // Get the correct constant for the terminal
        // get the values array and check in array
        $allCategories = array_combine(self::CATEGORIES_ALL, self::CATEGORIES_ALL);

        $method = self::getMethod($input);

        $network = self::getNetwork($input);

        if (isset(self::CATEGORIES[$method][$network]) === true)
        {
            foreach (self::CATEGORIES[$method][$network] as $category2 => $networkCategory)
            {
                $allCategories[$category2] = $networkCategory;
            }
        }

        // No need to worry about duplicates. we only need values
        $values = array_values($allCategories);

        return in_array($category, $values, true);
    }

    public static function getDefaultForMethodAndNetwork($method, $network)
    {
        return self::getCategoryForMethodAndNetwork($method, $network, self::DEFAULT);
    }

    public static function getCategoryForMethodAndNetwork($method, $network, $category2)
    {
        $networkCategory = self::getDefaultNetworkCategory($category2);

        if (isset(self::CATEGORIES[$method][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$category2];
        }

        if (isset(self::CATEGORIES[$method][$network][$category2]) === true)
        {
            $networkCategory = self::CATEGORIES[$method][$network][$category2];
        }

        return $networkCategory;
    }

    public static function getTPVCategories()
    {
        return self::TPV;
    }

    public static function getIncompatibleCategories()
    {
        return array_merge(self::TPV, []);
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

    protected static function getNetwork($input)
    {
        $network = null;

        if ($input[Entity::GATEWAY] === Gateway::AMEX)
        {
            $network = Network::AMEX;
        }

        return $network;
    }

    protected static function getMethod($input)
    {
        if ((isset($input[Entity::CARD]) === true) and
            (empty($input[Entity::CARD]) === false))
        {
            return Method::CARD;
        }

        if ((isset($input[Entity::NETBANKING]) === true) and
            (empty($input[Entity::NETBANKING]) === false))
        {
            return Method::NETBANKING;
        }

        if ((isset($input[Entity::EMI]) === true) and
            (empty($input[Entity::EMI]) === false))
        {
            return Method::EMI;
        }

        return null;
    }
}
