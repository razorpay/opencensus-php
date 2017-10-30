<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Payment\Gateway;
use Symfony\Component\HttpFoundation\HeaderBag as Headers;

class Preferences
{
    const MID_SOCH         = '6QGdVzDAIpBniU';
    const MID_ZOMATO       = '6H7N6hlcv29OMG';
    const MID_IPAY         = '6VS1z0fmis8fn6';
    const MID_DSPBLACKROCK = '7thBRSDflu7NHL';

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
