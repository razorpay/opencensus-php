<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Payment\Gateway;
use Symfony\Component\HttpFoundation\HeaderBag as Headers;

class Preferences
{
    const MID_SOCH             = '6QGdVzDAIpBniU';
    const MID_ZOMATO           = '6H7N6hlcv29OMG';
    const MID_IPAY             = '6VS1z0fmis8fn6';
    const MID_DSPBLACKROCK     = '7thBRSDflu7NHL';
    const MID_GOALWISE_TPV     = '7BfRNg10LH7N6T';
    const MID_GOALWISE_NON_TPV = '8ytYezIThlseJd';
    const MID_WEALTHAPP        = '9LYKZiz2kpFFtY';
    const MID_MONEYVIEW        = '8hXTLsmoM3F6PH';
    const MID_WEALTHY          = '8lv4idBRY4C9c0';
    const MID_PIGGY            = '9IjdEkLQb0j2ro';
    const MID_ENDURANCE        = ['9YAQd3b47mdIQY', '9ZO8jNaR0OORNH'];
    const MID_SHELL            = '9LMdTQdjgMJ6uR';
    const MID_PAISABAZAAR      = '9dhe2WRR0XCQz6';
    const DEMO_ACCOUNT         = '100DemoAccount';

    /**
     * This needs to go in DB, for hotfix we are keeping it here
     * Maintains lists of gateways excluded for a merchant
     */
    const MERCHANT_GATEWAY_BLACKLIST = [
        // Soch
        self::MID_SOCH => [
            Gateway::HDFC,
        ],

        // Zomato
        // Merchant does not allow gateways that send card info from client side
        self::MID_ZOMATO => [
            Gateway::AXIS_MIGS,
            Gateway::FIRST_DATA,
        ],
    ];

    const CUSTOMER_TRANSACTION_HISTORY_ENABLED_MID = [
        self::MID_SHELL,
        self::DEMO_ACCOUNT,
    ];

    public static $merchantSharedTerminalsBlackList = [
        self::MID_DSPBLACKROCK,
    ];

    /**
     * Maintains lists of gateways allowed for a merchant
     */
    const MERCHANT_GATEWAY_WHITELIST = [
        // Ipay
        // Merchant requires gateways with Dynamic Merchant Descriptor
        self::MID_IPAY => [
            Gateway::FIRST_DATA,
            Gateway::CYBERSOURCE,
        ],
    ];

    /**
     * We do not want to reject cybersource for these merchants
     */
    const CYBERSOURCE_MERCHANT_WHITELIST = [
        self::MID_ZOMATO,
        self::MID_IPAY,
    ];

    const X_AGGREGATOR_HEADER = 'x-aggregator';

    public static function checkZohoHeaders(Headers $headers)
    {
        $expectedHeader = Config::get('applications.zoho.header');

        return ($expectedHeader === $headers->get(self::X_AGGREGATOR_HEADER));
    }
}
