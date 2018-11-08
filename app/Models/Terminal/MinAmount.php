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
     * The map uses a gateway level separation for
     * netbanking and a network level segregation
     * for cards.
     *
     * Minimums can be defined on a method level
     * or on a gateway level.
     * */
    const MIN_AMOUNT = [
        Method::NETBANKING => [
            Gateway::BILLDESK   => [
                Category::GOVT_EDUCATION => 200000,
                Category::PVT_EDUCATION  => 200000,
                Category::CORPORATE      => 200000,
                Category::FOREX          => 200000,
                Category::HOUSING        => 150000,
            ],
            self::TOP_SIX_BANKS => [
            ],
            Gateway::NETBANKING_KOTAK => [
                Category::GOVT_EDUCATION => 200000,
                Category::PVT_EDUCATION  => 200000,
                Category::CORPORATE      => 200000,
                Category::LENDING        => 150000,
                Category::FOREX          => 150000,
                Category::HOUSING        => 150000,
            ],
        ],
        Method::CARD => [
            Gateway::AMEX => [
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
     * A more specific combination will override a
     * less specific combination.
     * Corresponding values can be null
     *
     * @param $method
     * @param $gateway
     * @param $network
     * @param $category
     *
     * @return int $minAmount from constant(MIN_AMOUNT)
     */
    public static function getMinAmount($method, $gateway, $network, $category)
    {
        $minAmount = 0;

        // set category if not available
        if (empty($category) === true)
        {
            $category = Category::getDefaultForMethodAndGateway($method, $gateway);
        }

        // get method category combination
        if (isset(self::MIN_AMOUNT[$method][$category]) === true)
        {
            $minAmount = self::MIN_AMOUNT[$method][$category];
        }

        // get netbanking top_six category combination
        if (($method === Method::NETBANKING) and
            (in_array($gateway, self::TOP_SIX) === true) and
            (isset(self::MIN_AMOUNT[$method][self::TOP_SIX_BANKS][$category]) === true))
        {
            $minAmount = self::MIN_AMOUNT[$method][self::TOP_SIX_BANKS][$category];
        }

        // get method key category map
        if (isset(self::MIN_AMOUNT[$method][$gateway][$category]) === true)
        {
            $minAmount = self::MIN_AMOUNT[$method][$gateway][$category];
        }

        return $minAmount;
    }
}
