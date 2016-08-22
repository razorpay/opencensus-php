<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment;

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

    public static function isCategoryValidForGateway($category, $gateway)
    {
        $methods = Method::getAllPaymentMethods();

        $flag = false;

        foreach($methods as $method)
        {
            $methodMap = strtoupper('method_'.$method);

            if ((Gateway::isMethodSupported($method, $gateway)) and
                (self::isCategoryValidForName($category, $methodMap)))
            {
                $flag = true;
                break;
            }
        }

        if ($flag === false)
        {
            $gatewayMap = strtoupper('gateway_'.$gateway);

            $flag = self::isCategoryValidForName($category, $gatewayMap);
        }

        return $flag;
    }

    public static function isCategoryValidForName($category, $name)
    {
        $name = strtoupper($name);

        if (isset(self::$$name) === false)
        {
            return false;
        }

        return in_array($category, self::$$name);
    }
}
