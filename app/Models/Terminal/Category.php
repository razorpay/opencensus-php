<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Category
{
    protected static $DEFAULT_METHOD = [
        METHOD::NETBANKING => 'ecommerce',
    ];

    protected static $DEFAULT_GATEWAY = [
        GATEWAY::AMEX   => 'retail_services',
    ];

    protected static $GATEWAY_AMEX = [
        'entertainment',
        'government_business',
        'healthcare',
        'education',
        'education_services',
        'car_rental',
        'auto',
        'sup_hypermrkt_deptstore',
        'travel_agency',
        'utilities',
        'hospitals',
        'insurance',
        'lodging',
        'prof_and_financial_serv',
        'retail_jgwcag',
        'retail_services',
    ];

    protected static $METHOD_NETBANKING = [
        'government',
        'ecommerce',
        'wallet',
        'education',
        'corporate',
    ];

    public static function getDefaultForMethodAndGateway($method, $gateway)
    {
        $category = null;

        if (isset(self::$DEFAULT_METHOD[$method]))
        {
            $category = self::$DEFAULT_METHOD[$method];
        }

        if (isset(self::$DEFAULT_GATEWAY[$gateway]))
        {
            $category = self::$DEFAULT_GATEWAY[$gateway];
        }

        return $category;
    }

    public static function isCategoryValidForMethodAndGateway($category, $method, $gateway)
    {
        $methodMap = self::getMethodCategoriesMap($method);

        $gatewayMap = self::getGatewayCategoriesName($gateway);

        return ((in_array($category, $methodMap)) or
                (in_array($category, $gatewayMap)));
    }

    protected static function getMethodCategoriesMap($method)
    {
        $map = 'METHOD_'.strtoupper($method);

        if (isset(self::$map))
        {
            return self::$map;
        }

        return [];
    }

    protected static function getGatewayCategoriesName($gateway)
    {
        $map = 'GATEWAY_'.strtoupper($gateway);

        if (isset(self::$map))
        {
            return self::$map;
        }

        return [];
    }
}
