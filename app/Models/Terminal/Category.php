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
     * Broking requires an incompatible flag to be added to
     * prevent incompatible methods from choosing the default
     */
    const CATEGORIES_ALL = [
        // 'broking',
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
     * For netbanking, each of the categories on the left will
     * be mapped to the category on the right.
     * */
    const METHOD_NETBANKING = [
        // 'broking'     => 'broking',
        'grocery'        => 'grocery',
        'ecommerce'      => 'ecommerce',
        'govt_education' => 'govt_education',
        'pvt_education'  => 'pvt_education',
        'utilities'      => 'utilities',
        'corporate'      => 'corporate',
        'insurance'      => 'insurance',
        'housing'        =>  'housing',
        'mutual_funds'   =>  'mutual_funds',
        'travel_agency'  =>  'travel_agency',
    ];

    /**
     * The default categories allowed are the ones specified in method.
     * Anything defined on network, or otherwise is an override.
     * If for some category an override is not required,
     * i.e the category decided by the method is to be used,
     * then it shoould be left empty
     * */
    const NETWORK_AMEX = [
        // 'broking'     => 'incompatible',
        'grocery'        => 'sup_hypermrkt_deptstore',
        'ecommerce'      => 'retail_services',
        'govt_education' => 'education',
        'pvt_education'  => 'education', //confirm this is not education services
        'utilities'      => 'utilities',
        'corporate'      => '',
        'insurance'      => 'insurance',
        'housing'        =>  'housing',
        'mutual_funds'   =>  'mutual_funds',
        'travel_agency'  =>  'travel_agency',
    ];

    const MIN_AMOUNT_METHOD_NETBANKING= [
        // 'grocery'        => 10,
        // 'ecommerce'      => 10,
        // 'govt_education' => 10,
        // 'pvt_education'  => 10,
        // 'utilities'      => 10,
        // 'corporate'      => 10,
        // 'insurance'      => 10,
        // 'housing'        => 10,
        // 'mutual_funds'   => 10,
        // 'travel_agency'  => 10,
    ];

    const MIN_AMOUNT_NETWORK_AMEX = [
        // 'grocery'        => 10,
        // 'ecommerce'      => 10,
        // 'govt_education' => 10,
        // 'pvt_education'  => 10,
        // 'utilities'      => 10,
        // 'corporate'      => 10,
        // 'insurance'      => 10,
        // 'housing'        => 10,
        // 'mutual_funds'   => 10,
        // 'travel_agency'  => 10,
    ];

    const DEFAULT_MIN_AMOUNT = [
        'METHOD_NETBANKING'     => 1000,
        'NETWORK_AMEX'          => 100,
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
            (isset(constant('self::'.$constantName)[$item]) === true))
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

    /**
     * Utitlity function that is used to get the category for a
     * particular pair of (method, $method) or a (network, $network)
     * */
    protected static function getCategoryForType($type, $item, $category)
    {
        $returnCategory = null;

        $name = self::getConstantName($type, $item);

        if ((is_null($category) === false) and
            (self::isConstantDefined($type, $item)) and
            (isset(constant('self::'.$name)[$category]) === true))
        {
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
        return in_array($category, self::CATEGORIES_ALL);
    }

    public static function isNetworkCategoryValid($input)
    {
        // Get the correct constant for the terminal
        // get the values array and check in array
        $values = [];

        $category = $input[Entity::NETWORK_CATEGORY];

        $method = self::getMethod($input);

        $network = self::getNetwork($input);

        if (self::isConstantDefined('network', $network) === true)
        {
            $networkConstantName = self::getConstantName('network', $network);

            $values = array_values(constant('self::'.$networkConstantName));
        }
        else if (self::isConstantDefined('method', $method) === true)
        {
            $methodConstantName = self::getConstantName('method', $method);

            $values = array_values(constant('self::'.$methodConstantName));
        }

        return in_array($category, $values);
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

    public static function getMinAmount($method, $network, $category)
    {
        $minAmount = 0;

        $constantName = null;

        if (empty($category) === true)
        {
            $category = self::getDefaultForMethodAndNetwork($method, $network);
        }

        // set constant name
        if (self::isConstantDefined('network', $network) === true)
        {
            $constantName = self::getConstantName('network', $network);
        }

        else if (self::isConstantDefined('method', $method) === true)
        {
            $constantName = self::getConstantName('method', $method);
        }

        // min_amount from respective array
        if (empty($constantName) === false)
        {
            if (in_array($category, constant('self::MIN_AMOUNT_'.$constantName)) === false)
            {
                $minAmount = constant('self::DEFAULT_MIN_AMOUNT')[$constantName];
            }

            else
            {
                $minAmount = constant('self::MIN_AMOUNT_'.$constantName)[$category];
            }
        }
        
        return $minAmount;
    }
}
