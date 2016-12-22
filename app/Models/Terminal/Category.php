<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    const DEFAULT = 'default';

    // Categories mapped to invalid will not find an
    // appropriate category to override. Only the category
    // allowed by default will be chosen
    const INVALID = 'invalid';

    /**
     * The list of all possible categories that can be chosen
     * Broking requires an incompatible flag to be added to
     * prevent incompatible methods from choosing the default
     */
    const CATEGORIES_ALL = [
        'securities',
        'commodities',
        'grocery',
        'ecommerce',
        'govt_education',
        'pvt_education',
        'utilities',
        'corporate',
        'insurance',
        'housing',
        'mutual_funds',
        'travel_agency',
    ];


    /**
     * INCOMPATIBLE categories will not allow terminals that
     * do not match the corresponding categories.
     * The list below is of the category2 on merchant entity.
     * The terminals marked null or default will be filtered
     * out.
     **/
    const INCOMPATIBLE = [
        'securities',
        'commodities',
    ];

    // By default Check for the name that is mentioned as is.
    // If it is renamed, then the new name that is mentioned will be
    // used to check for a network category
    const CATEGORIES = [
        Method::NETBANKING => [
            self::DEFAULT => 'ecommerce',
        ],
        Method::CARD => [
            self::DEFAULT => 'ecommerce',
            Network::AMEX => [
                self::DEFAULT    => 'retail_services',
                'grocery'        => 'sup_hypermrkt_deptstore',
                'ecommerce'      => 'retail_services',
                'govt_education' => 'education',
                'pvt_education'  => 'education',
                'corporate'      => self::INVALID,
                'insurance'      => 'insurance',
                'housing'        => 'housing',
            ],
        ]
    ];

    public static function isMerchantCategoryIncompatible($category)
    {
        return in_array($category, self::INCOMPATIBLE, true);
    }

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
        $checkArray = array_combine(self::CATEGORIES_ALL, self::CATEGORIES_ALL);

        $method = self::getMethod($input);

        $network = self::getNetwork($input);

        if ((isset(self::CATEGORIES[$method]) === true) and
            (isset(self::CATEGORIES[$method][$network]) === true))
        {
            foreach (self::CATEGORIES[$method][$network] as $category2 => $networkCategory)
            {
                $checkArray[$category2] = $networkCategory;
            }
        }

        // No need to worry about duplicates. we only need values
        $values = array_values($checkArray);

        return in_array($category, $values, true);
    }

    public static function getDefaultForMethodAndNetwork($method, $network)
    {
        return self::getCategoryForMethodAndNetwork($method, $network, self::DEFAULT);
    }

    public static function getCategoryForMethodAndNetwork($method, $network, $category2)
    {
        $networkCategory = null;

        if (isset(self::CATEGORIES[$method]) === true)
        {
            if (isset(self::CATEGORIES[$method][$category2]) === true)
            {
                $networkCategory = self::CATEGORIES[$method][$category2];
            }

            if ((isset(self::CATEGORIES[$method][$network]) === true) and
                (isset(self::CATEGORIES[$method][$network][$category2]) === true))
            {
                $networkCategory = self::CATEGORIES[$method][$network][$category2];
            }
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
