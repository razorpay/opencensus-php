<?php

namespace RZP\Models\Terminal;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class MinAmount
{
    const MIN_AMOUNT = [
        Method::NETBANKING => [
            Gateway::BILLDESK   => [
                'grocery'   => 100,
                'utilities' => 100,
            ],
            self::TOP_SIX_BANKS => [
                'grocery'   => 100,
                'ecommerce' => 100,
            ],
            Gateway::NETBANKING_KOTAK => [
                'grocery'   => 100,
                'ecommerce' => 100,
            ],
        ],
        Method::CARD => [
            Network::AMEX => [
                'retail_services' => 100,
                'utilities'       => 100,
            ],
        ],
    ];

    const TOP_SIX_BANKS = 'TOP_SIX';

    const TOP_SIX = [
        Gateway::NETBANKING_KOTAK,
        Gateway::NETBANKING_HDFC,
    ];

    /**
    * Accepts array of key-val pair
    * with keys : category, method, network, gateway
    * All keys should be present
    * Corresponding values can be null
    *
    * @param $filterParams array
    * @return $minAmount from constant(MIN_AMOUNT)
    */
    public static function getMinAmount($method, $gateway, $network, $category)
    {
        $minAmount = 0;

        // set category
        if (empty($category) === true)
        {
            $category = Category::getDefaultForMethodAndNetwork($method, $network);
        }

        $minAmount = self::minAmount($method, $gateway, $network, $category);

        return $minAmount;
    }

    protected static function minAmount($method, $gateway, $network, $category)
    {
        $minAmount = 0 ;

        switch ($method)
        {
            case Method::NETBANKING:
                $key = $gateway;
                break;

            case Method::EMI:
            case Method::CARD:
                $key = $network;
                break;
        }

        if (($method === Method::NETBANKING) and
            (in_array($gateway, self::TOP_SIX) === true))
        {
            $minAmount = self::MIN_AMOUNT[self::TOP_SIX_BANKS][$category];
        }

        if ((array_key_exists($key, self::MIN_AMOUNT[$method]) === true) and
            (array_key_exists($category, self::MIN_AMOUNT[$method][$key]) === true))
        {
            $minAmount = self::MIN_AMOUNT[$gateway][$category];
        }

        return $minAmount;
    }
}
