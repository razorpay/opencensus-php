<?php

namespace RZP\Models\Terminal;

use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;

class MinAmount
{

    const MIN_AMOUNT = [

        'netbanking' => [
            'default' => [
                'default'   => 1000,
                'utilities' => 1000,
                'grocery'   => 1000,
            ],
            'billdesk' => [
                'default'   => 1000,
                'grocery'   => 1000,
                'utilities' => 1000,
            ],
            'top_six' => [
                'default'   => 1000,
                'grocery'   => 1000,
                'ecommerce' => 1000,
            ],
            'kkbk' => [
                'default'   => 1000,
                'grocery'   => 1000,
                'ecommerce' => 1000,
            ],
        ],

        'network' => [
            'default' => [
                'default'           => 1000,
                'utilities'         => 1000,
                'retail_services'   => 1000,
            ],
            Network::AMEX => [
                'default'   => 1000,
                'retail_services' => 1000,
                'utilities'       => 1000,
            ],
            Network::MC => [
                'default'   => 1000,
                'education' => 1000,
                'utilities' => 1000,
            ],
        ],

        'default' => [
            'default'   => 1000,
            'ecommerce' => 1000,
            'utilities' => 1000,
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
    public static function getMinAmount($filterParams)
    {
        $minAmount = 0;

        $methodName = null;

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
            $methodName = $method;

            $minAmount = self::netbankingMinAmount($category, $gateway);
        }

        else if (empty($network) === false)
        {
            $methodName = $network;

            $minAmount = self::networkMinAmount($category, $network);
        }

        // default case when no method is mentioned
        if (empty($methodName) === true)
        {
            $minAmount = self::defaultMinAmount($category);
        }

        return $minAmount;
    }

    protected static function netbankingMinAmount($category, $gateway)
    {
        $minAmount = 0;

        $gatewayTag = null;

        $netbankingMap = constant('self::MIN_AMOUNT')['netbanking'];

        // check gateway exists in top_six
        if (in_array($gateway, constant('self::TOP_SIX')) === true)
        {
            $gatewayTag = 'top_six';
        }

        // over-write filter for top-6
        // & billdesk etc specific cases
        elseif (array_key_exists($gateway, $netbankingMap))
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
        $minAmount = 0;

        $networkTag = null;

        $networkArray = constant('self::MIN_AMOUNT')['network'];

        if (array_key_exists($network, $networkArray) === true)
        {
            $networkTag = $network;
        }

        else
        {
            $networkTag = 'default';
        }

        $minAmount = self::minAmountFromArray($netbankingMap, $networkTag, $category);

        return $minAmount;
    }

    protected static function minAmountFromArray($amountMap, $tag, $category)
    {
        $minAmount = 0;

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

    protected static function defaultMinAmount($category)
    {
        $minAmount = 0;

        $defaultArray = constant('self::MIN_AMOUNT')['default'];

        if (array_key_exists($category, $defaultArray))
        {
            $minAmount = $defaultArray[$category];
        }

        else
        {
            $minAmount = $defaultArray['default'];
        }

        return $minAmount;
    }
}
