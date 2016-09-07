<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    const DEFAULT_METHOD = [
        Method::NETBANKING => 'ecommerce',
    ];

    const DEFAULT_NETWORK = [
        Network::AMEX   => 'retail_services',
    ];

    /**
     * The list of all possible categories that can be chosen
     * */
    const CATEGORIES_ALL = [
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

    /**
     * For netbanking, each of the categories on the left will
     * be mapped to the category on the right.
     * */
    const METHOD_NETBANKING = [
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

    /**
     * The default categories allowed are the ones specified in method.
     * Anything defined on network, or otherwise is an override.
     * If for some category an override is not required,
     * i.e the category decided by the method is to be used,
     * then it shoould be left empty
     * */
    const NETWORK_AMEX = [
        'auto'                    => 'auto',
        'car_rental'              => 'car_rental',
        'corporate'               => '',
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
        return self::getDefaultForType('method', $method);
    }

    public static function getDefaultForNetwork($network)
    {
        return self::getDefaultForType('network', $network);
    }

    protected static function getDefaultForType($type, $item)
    {
        $category = null;

        $constantName = self::getConstantName('default', $type);

        if ((self::isConstantDefined('default', $type) === true) and
            (in_array($item, constant('self::'.$constantName)) === true))
        {
            $category = constant('self::'.$constantName)[$item];
        }

        return $category;
    }

    public static function getDefaultForMethodAndNetwork($method, $network)
    {
        $category = null;

        $category = self::getDefaultForMethod($method);

        $networkCategory = self::getDefaultForNetwork($network);

        if (empty($networkCategory) === false)
        {
            $category = $networkCategory;
        }

        return $category;
    }

    public static function getCategoryForMethod($method, $category)
    {
        return self::getCategoryForType('method', $method, $category);
    }

    public static function getCategoryForNetwork($network, $category)
    {
        return self::getCategoryForType('network', $network, $category);
    }

    protected static function getCategoryForType($type, $item, $category)
    {
        $returnCategory = null;

        if ((self::isConstantDefined($type, $item)) and
            (is_null($category) === false))
        {
            $name = self::getConstantName($type, $item);

            $returnCategory = constant('self::'.$name)[$category];
        }

        return $returnCategory;
    }

    public static function getCategoryForMethodAndNetwork($method, $network, $category)
    {
        $methodCategory = self::getCategoryForMethod($method, $category);

        $defaultMethodCategory = self::getDefaultForMethod($method);

        $networkCategory = self::getCategoryForNetwork($network, $category);

        $defaultNetworkCategory = self::getDefaultForNetwork($network);

        $returnCategory = $methodCategory;

        if (is_null($returnCategory) === true)
        {
            $returnCategory = $defaultMethodCategory;
        }

        // Don't perform network override if the networkCategory is ''
        if ((is_null($networkCategory) === false) && ($networkCategory !== ''))
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

        return defined('self::'.$name);
    }

    protected static function getConstantName($type, $name)
    {
        return strtoupper($type.'_'.$name);
    }

    public static function isMerchantCategoryValid($category)
    {
        return in_array($category, constant('self::CATEGORIES_ALL'));
    }

    public static function isTerminalCategoryValid($category, $method = null, $network = null)
    {
        // get the correct constant for the terminal
        // get the values array and check in array
        $values = [];

        if(self::isConstantDefined('method', $method) === true)
        {
            $methodConstantName = self::getConstantName('method', $method);

            $values = array_values(constant('self::'.$methodConstantName));
        }

        if(self::isConstantDefined('network', $network) === true)
        {
            $networkConstantName = self::getConstantName('network', $network);

            $values = array_values(constant('self::'.$networkConstantName));
        }

        return in_array($category, $values);
    }
}
