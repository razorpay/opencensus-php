<?php

namespace RZP\Models\Merchant;

use Config;
use RZP\Models\Payment\Gateway;
use Symfony\Component\HttpFoundation\HeaderBag as Headers;

class Preferences
{
    const MID_SOCH                  = '6QGdVzDAIpBniU';
    const MID_ZOMATO                = '6H7N6hlcv29OMG';
    const MID_IPAY                  = '6VS1z0fmis8fn6';
    const MID_CUREFIT               = '6vwsEbqse39D4d';
    const MID_DSPBLACKROCK          = '7thBRSDflu7NHL';
    const MID_GOALWISE_TPV          = '7BfRNg10LH7N6T';
    const MID_GOALWISE_NON_TPV      = '8ytYezIThlseJd';
    const MID_WEALTHAPP             = '9LYKZiz2kpFFtY';
    const MID_MONEYVIEW             = '8hXTLsmoM3F6PH';
    const MID_WEALTHY               = '8lv4idBRY4C9c0';
    const MID_PIGGY                 = '9IjdEkLQb0j2ro';
    const MID_PIGGY_TPV             = 'BADGdiwSiwi1g2';
    const MID_SHELL                 = '9LMdTQdjgMJ6uR';
    const MID_SHELL_2               = '9R0AsTqocyuP1W';
    const MID_PAISABAZAAR           = '9dhe2WRR0XCQz6';
    const MID_PAISABAZAAR_GOLD      = 'AiWdjAyyF4RKBa';
    const MID_BPCL                  = '9C04GG1wPzKCUP';
    const MID_SRI_CHAITANYA         = '8f9o3YjPGZEcdU';
    const MID_UBER                  = '82LK42BGTN2bOe';
    const MID_AMIT_MAHBUBANI        = '7SVOQZGZuwHr4I';
    const MID_ICICI_LOMBARD         = 'AXRuIp5uiz5Jsp';
    const MID_KARVY                 = 'AmReTNPu1KFKBn';
    const MID_ANGEL_BROKING         = 'AC4DJNMIX9xXOz';
    const MID_SHELLHATCH            = 'A7W1rwbYMRmn6M';
    const MID_PAISABAZAAR_MARKETING = 'B1uh6CFFBKk35S';
    const MID_AMIT_RBLCARD          = 'BcVn9Oy1aSkcOa';
    const MID_AMIT_RBLLOAN          = 'BcVzB5W2m4noKJ';
    const MID_RBLCARD               = 'BYUXW3iBH0P0zU';
    const MID_DELINQUENT_LOANS      = 'C3oXor5gWBUoWB';
    const MID_RBLLOAN               = 'BUjzZmAEXnXVJs';
    const MID_RBLBFL                = 'BjdSExY3hArAHm';
    const MID_RBL_TOTAL_BASE        = 'BoccLxCbqWFXmU';
    const MID_DMI_FINANCE           = 'BU4wKuO2IisLWY';
    const MID_VARTHANA_FINANCE      = 'BpqmTAX1XcFMvB';
    const MID_INDIABULLS_FINANCE    = 'BXdV62dMAbb869';
    const MID_DREAM11               = '6L6z7NYQywAaP0';
    const MID_RBLLENDING            = 'BOX702yaBbEfJo';

    const DEMO_ACCOUNT         = '100DemoAccount';
    const MID_ENDURANCE        = [
        '9YAQd3b47mdIQY', '9ZO8jNaR0OORNH', '9Y9m9XscC6Kh4W',
        '8WRMdGzG1z5Eqw', '9naAGQdroegWIX', '9Y9m9XscC6Kh4W',
        '9okVtwZr5vLm4K', '9oklLp2FhXTolM', 'A0ERwPs8muf9YS',
        'A0GNi6PHlqy5zX', 'A0HuEfx39zhjr9', 'A5ONBRrNJ7dS1K',
        'A5MmRVEM3qf6QJ', 'A5OZ1qi9tgwnZB', 'A5OeZOCaeyQQ8Q',
    ];

    const MID_IRCTC = [
        '8byazTDARv4Io0',
        'AEPXwjSlJJhfUl',
        '9m4CChGex4ENkR',
        'AEsxERLbWiBuUG',
        '8ST00QgEPT14cE',
        '8YPFnW5UOM91H7',
        '90xVmQJTCEJ6GH'
    ];

    const MID_CLEARTAX         = 'AGQJfLbWcmjxDX';
    const MID_APARTMENTADDA    = '9NVPPQuTqF4cYx';
    const MID_INVEZTA          = '8YQygO7pzP3Gut';

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
        self::MID_SHELL_2,
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

    //
    // Skip settlements for few merchants
    // Details in: https://github.com/razorpay/api/issues/5830
    // Temporary, until https://github.com/razorpay/api/pull/6161
    // is merged
    //
    const NO_SETTLEMENT_MIDS = [
        self::MID_GOALWISE_NON_TPV,
        self::MID_GOALWISE_TPV,
        self::MID_MONEYVIEW,
        self::MID_WEALTHY,
        self::MID_PIGGY,
        self::MID_PIGGY_TPV,
        self::MID_PAISABAZAAR,
        self::MID_PAISABAZAAR_GOLD,
        self::MID_BPCL,
        self::MID_SRI_CHAITANYA,
        self::MID_CLEARTAX,
        self::MID_APARTMENTADDA,
        self::MID_INVEZTA,
        self::MID_KARVY,
        self::MID_ANGEL_BROKING,
        self::MID_SHELLHATCH,
        self::MID_PAISABAZAAR_MARKETING,
    ];

    const ONLY_NEFT_SETTLEMENT_MIDS = [
        self::MID_PIGGY,
        self::MID_PIGGY_TPV,
    ];

    public static function checkZohoHeaders(Headers $headers)
    {
        $expectedHeader = Config::get('applications.zoho.header');

        return ($expectedHeader === $headers->get(self::X_AGGREGATOR_HEADER));
    }
}
