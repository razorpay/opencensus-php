<?php

namespace RZP\Models\Terminal;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class MinAmount
{
    /**
     * Map of min amount for network categories.
     *
     * The map uses a gateway level seperation for
     * netbanking and a network level segregation
     * for cards.
     *
     * Minimums can be defined on a method level
     * or on a gateway level.
     * */
    const MIN_AMOUNT = [
        Method::NETBANKING => [
            Gateway::BILLDESK   => [
                'govt_education' => 200000,
                'pvt_education'  => 200000,
                'corporate'      => 200000,
            ],
            self::TOP_SIX_BANKS => [
            ],
            Gateway::NETBANKING_KOTAK => [
                'govt_education' => 200000,
                'pvt_education'  => 200000,
                'corporate'      => 200000,
            ],
        ],
        Method::CARD => [
            Network::AMEX => [
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
        $key = '';

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

        if (isset(self::MIN_AMOUNT[$method][$category]) === true)
        {
            $minAmount = self::MIN_AMOUNT[$method][$category];
        }

        if (($method === Method::NETBANKING) and
            (in_array($gateway, self::TOP_SIX) === true) and
            (isset(self::MIN_AMOUNT[$method][self::TOP_SIX_BANKS][$category]) === true))
        {
            $minAmount = self::MIN_AMOUNT[$method][self::TOP_SIX_BANKS][$category];
        }

        if ((isset(self::MIN_AMOUNT[$method][$key]) === true) and
            (isset(self::MIN_AMOUNT[$method][$key][$category]) === true))
        {
            $minAmount = self::MIN_AMOUNT[$method][$key][$category];
        }

        return $minAmount;
    }
}
