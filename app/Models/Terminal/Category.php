<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    protected static $DEFAULT_METHOD = [
        Method::NETBANKING => 'ecommerce',
    ];

    protected static $DEFAULT_NETWORK = [
        Network::AMEX   => 'retail_services',
    ];

    // The list of all possible categories that can be chosen
    protected static $CATEGORIES_ALL = [
        'auto',
        'car_rental',
        'corporate',
        'education',
        'education_services',
        'entertainment',
        'government_business',
        'healthcare',
        'hospitals',
        'insurance',
        'lodging',
        'prof_and_financial_serv',
        'retail_jgwcag',
        'retail_services',
        'sup_hypermrkt_deptstore',
        'travel_agency',
        'utilities',
    ];

    // For netbanking, each of the categories on the left will
    // be mapped to the category on the right.
    protected static $METHOD_NETBANKING = [
        'corporate'               => 'corporate',
        'education'               => 'education',
        'education_services'      => 'education',
        'government_business'     => 'government',
        'insurance'               => 'insurance',

        'auto'                    => 'ecommerce',
        'car_rental'              => 'ecommerce',
        'entertainment'           => 'ecommerce',
        'healthcare'              => 'ecommerce',
        'hospitals'               => 'ecommerce',
        'lodging'                 => 'ecommerce',
        'prof_and_financial_serv' => 'ecommerce',
        'retail_jgwcag'           => 'ecommerce',
        'retail_services'         => 'ecommerce',
        'sup_hypermrkt_deptstore' => 'ecommerce',
        'travel_agency'           => 'ecommerce',
        'utilities'               => 'ecommerce',
    ];

    // The Network specific override for each of the
    // allowed categories.
    // Only Overrides are allowed to have empty categories
    // This implies that the default or specific from the previous
    // will be used instead of this.
    protected static $NETWORK_AMEX = [
        'auto'                    => 'auto',
        'car_rental'              => 'car_rental',
        'corporate'               => '', // empty implies no override. implies use the previous default or specific
        'education'               => 'education',
        'education_services'      => 'education_services',
        'entertainment'           => 'entertainment',
        'government_business'     => 'government_business',
        'healthcare'              => 'healthcare',
        'hospitals'               => 'hospitals',
        'insurance'               => 'insurance',
        'lodging'                 => 'lodging',
        'prof_and_financial_serv' => 'prof_and_financial_serv',
        'retail_jgwcag'           => 'retail_jgwcag',
        'retail_services'         => 'retail_services',
        'sup_hypermrkt_deptstore' => 'sup_hypermrkt_deptstore',
        'travel_agency'           => 'travel_agency',
        'utilities'               => 'utilities',
    ];

    public static function getDefaultForMethod($method)
    {
        $category = null;

        if (isset(self::$DEFAULT_METHOD[$method]))
        {
            $category = self::$DEFAULT_METHOD[$method];
        }

        return $category;
    }

    public static function getDefaultForNetwork($network)
    {
        $category = null;

        if (isset(self::$DEFAULT_NETWORK[$network]))
        {
            $category = self::$DEFAULT_NETWORK[$network];
        }

        return $category;
    }

    public static function isCategoryValidFor($category, $name)
    {
        $name = strtoupper($name);

        if (isset(self::$$name) === false)
        {
            return false;
        }

        return in_array($category, self::$$name);
    }

    public static function getCategoryForMethod($method, $category)
    {
        $returnCategory = null;

        if (self::isConstantDefined('method', $method))
        {
            $name = self::getConstantName('method', $method);

            $returnCategory = self::$$name[$category];
        }

        return $returnCategory;
    }

    public static function getCategoryForNetwork($network, $category)
    {
        $returnCategory = null;

        if (self::isConstantDefined('network', $network))
        {
            $name = self::getConstantName('network', $network);

            $returnCategory = self::$$name[$category];
        }

        return $returnCategory;
    }

    public static function getCategoryForMethodAndNetwork($method, $network, $category)
    {
        $methodCategory = self::getCategoryForMethod($method, $category$);

        $defaultMethodCategory = self::getDefaultForMethod($method);


        $networkCategory = self::getCategoryForNetwork($network, $category);

        $defaultNetworkCategory = self::getDefaultForNetwork($network);

        $returnCategory = $methodCategory;

        if (is_null($returnCategory) === true)
        {
            $returnCategory = $defaultMethodCategory;
        }

        // Don't perform network override if the networkCategory is ''
        if ((is_null($returnCategory) === true) && ($networkCategory !== ''))
        {
            $returnCategory = $networkCategory;

            if (is_null($returnCategory) === true)
            {
                $returnCategory = $defaultNetworkCategory;
            }
        }

        return $returnCategory;
    }

    protected static function isConstantDefined($type, $name)
    {
        $name = self::getConstantName($type, $name);

        return isset(self::$$name);
    }

    protected static function getConstantName($type, $name)
    {
        return strtoupper($type.'_'.$name);
    }
}
