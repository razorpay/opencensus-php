<?php

namespace RZP\Models\Terminal;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;

class MinAmount
{

    const MIN_AMOUNT = [

        'netbanking' => [
            'default' => [
                'default'   => 100,
                'utilities' => 100,
                'grocery'   => 100,
            ],
            'billdesk' => [
                'default'   => 100,
                'grocery'   => 100,
                'utilities' => 100,
            ],
            'top_six' => [
                'default'   => 100,
                'grocery'   => 100,
                'ecommerce' => 100,
            ],
            'kkbk' => [
                'default'   => 100,
                'grocery'   => 100,
                'ecommerce' => 100,
            ],
        ],

        'card' => [
            'default' => [
                'default'           => 100,
                'utilities'         => 100,
                'retail_services'   => 100,
            ],
            Network::AMEX => [
                'default'   => 100,
                'retail_services' => 100,
                'utilities'       => 100,
            ],
            Network::MC => [
                'default'   => 100,
                'education' => 100,
                'utilities' => 100,
            ],
        ],
    ];

    const TOP_SIX = [
        'KKBK',
        'YESB',
        'HDFC',
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
    public static function getMinAmount(array $filterParams)
    {
        // unwrap filterParams
        $category = $filterParams['category'];

        $method = $filterParams['method'];

        $network = $filterParams['network'];

        $gateway = $filterParams['gateway'];

        // set category
        if (empty($category) === true)
        {
            $category = Category::getDefaultForMethodAndNetwork($method, $network);
        }

        if (empty($method) === false)
        {
            $minAmount = self::netbankingMinAmount($category, $gateway);
        }

        else if (empty($network) === false)
        {
            $minAmount = self::networkMinAmount($category, $network);
        }

        if (is_null($minAmount) === true)
        {
            $minAmount = 0;
        }

        return $minAmount;
    }

    protected static function netbankingMinAmount($category, $gateway)
    {
        $netbankingMap = constant('self::MIN_AMOUNT')['netbanking'];

        // check gateway exists in top_six
        if (in_array($gateway, constant('self::TOP_SIX')) === true)
        {
            $gatewayTag = 'top_six';
        }

        // over-write filter for top-6
        // & billdesk etc specific cases
        elseif (array_key_exists($gateway, $netbankingMap) === true)
        {
            $gatewayTag = $gateway;
        }

        else
        {
            $gatewayTag = 'default';
        }

        $minAmount = self::minAmountFromArray($netbankingMap, $gatewayTag, $category);

        return $minAmount;
    }

    protected static function networkMinAmount($category, $network)
    {
        $networkMap = constant('self::MIN_AMOUNT')['card'];

        if (array_key_exists($network, $networkMap) === true)
        {
            $networkTag = $network;
        }

        else
        {
            $networkTag = 'default';
        }

        $minAmount = self::minAmountFromArray($networkMap, $networkTag, $category);

        return $minAmount;
    }

    protected static function minAmountFromArray($amountMap, $tag, $category)
    {
        $amountArray = $amountMap[$tag];

        if (array_key_exists($category, $amountArray) === true)
        {
            $minAmount = $amountArray[$category];
        }

        else
        {
            $minAmount = $amountArray['default'];
        }

        return $minAmount;
    }
}
