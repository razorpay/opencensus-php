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
    const MID_SHELL            = '9LMdTQdjgMJ6uR';
    const MID_SHELL_2          = '9R0AsTqocyuP1W';
    const MID_PAISABAZAAR      = '9dhe2WRR0XCQz6';
    const MID_BPCL             = '9C04GG1wPzKCUP';
    const MID_SRI_CHAITANYA    = '8f9o3YjPGZEcdU';
    const MID_UBER             = '82LK42BGTN2bOe';
    const MID_AMIT_MAHBUBANI   = '7SVOQZGZuwHr4I';
    const DEMO_ACCOUNT         = '100DemoAccount';
    const MID_ENDURANCE        = [
        '9YAQd3b47mdIQY', '9ZO8jNaR0OORNH', '9Y9m9XscC6Kh4W',
        '8WRMdGzG1z5Eqw', '9naAGQdroegWIX', '9Y9m9XscC6Kh4W',
        '9okVtwZr5vLm4K', '9oklLp2FhXTolM', 'A0ERwPs8muf9YS',
        'A0GNi6PHlqy5zX', 'A0HuEfx39zhjr9', 'A5ONBRrNJ7dS1K',
        'A5MmRVEM3qf6QJ', 'A5OZ1qi9tgwnZB', 'A5OeZOCaeyQQ8Q',
    ];

    //Allowing intent on following mindgate and hulk terminals
    //A3TQCfNLIQQ3bA
    //ABhzFzt7QtANx1
    //9q750D1dN5sa3X
    //ADnrGoH9h6COgJ
    const MID_INTENT_WHITELIST = ['9aHC1hl4PNU3sn', '6ZJzxyLFWrGs74', 'A3KnXGBotPXCYC', '2aTeFCKTYWwfrF',
                                  '8fgkljXsVg4lSh', '9ac01GMEGfyU2o', '9ac01tGbLrFpSV', '9ac02H8xqcdFQh',
                                  '9ac02hmncjWTgM', '9ac0379Ep1r0J3', '9ac03W9VWLetCt', '9ac03yT0APTh1z',
                                  '9ac04NsWxC3tRt', '9ac04oe4MKbOXV', '9ac05IfbWfIGJo', '9ac05lO6vSzhor',
                                  '9ac06DbLwe9v4u', '9ac06hnFv0HRGG', '9ac07EP4SuA1KO', '9ac07oOsTamskQ',
                                  '9ac08GZgcokhYc', '9ac08i6gJgDVq2', '9ac0976yFgKTzq', '9ac09UxoDTe1dC',
                                  '9ac09ucR1vEvph', '9ac0AMR1H3n18A', '9ac0ApXdgwOurS', '9ac0BFkiA29q8o',
                                  '9ac0Bihc4F9qnW', '9ac0CBXZ5Oszgy', '9ac0CbzTsptnFU', '9ac0D4UcMgbTZV',
                                  '9ac0DT7Epk7Dre', '9ac0DtwA1clRpg', '9ac0EIkPFZHWYR', '9ac0EnB7f5eyU7',
                                  '9ac0FFIRLXx83B', '9ac0FcOUFdiZWP', '9ac0FzCLhwFBx4', '9ac0GVIQRhPapZ',
                                  '9ac0Gy7qAbaXk3', '9ac0HPqkLkY6tR', '9ac0HotNhSMJzK', '9ac0INQnpskegm',
                                  '9ac0Imf8IB87Fh', '9ac0JErnD2Ro2U', '9ac0JlJ59uylbj', '9ac0KBJpur4TWH',
                                  '9ac0KYoafmULYX', '9ac0KxjsLehKbe', '9ac0LNVeQv3hwO', '9ac0LnF27xkXSy',
                                  '9ac0MGfpVXM1rX', '9ac0MhpX7a0s80', '9ac0NHWnXaAQzB', '9ac0NmLhDuYhiw',
                                  '9ac0OJXuuHPLFr', '9ac0OjIAZ8F5Er', '9ac0P8x82pOwdA', '9ac0PY46Hi7cA0',
                                  '9ac0Pw41WpACh6', '9ac0QKavrGzQnG', '9ac0QisoFm888E', '9ac0R6snbutUvv',
                                  '9ac0RZym55VGsd', '9ac0S0RCy2Zy6T', '41Bvcs1BYUHRzJ', '4uBYoaxgEV9Vpq',
                                  '5DT4a51hWyB2S2', '5yvFZKqbBjEBsr', '67a0aqRNfZCdrS', '6LCgLZgRjTI8ws',
                                  '6e9vU1F6c16Wgy', '6q0BL9DgjgHdIv', '6tRJ7zRKzY3hU9', '7ovwlfjBfzv8jT',
                                  '7yoaGgYGbq6GoN', '8JMwdqJg5w84ES', '8JPzPesuDW7HQX', '8Ph17vkslEBeIu',
                                  '9Am5NzeJvtuBFy', '9cVCrt48C7hz4A', 'A3KnXGBotPXCYC',];

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

    public static function checkZohoHeaders(Headers $headers)
    {
        $expectedHeader = Config::get('applications.zoho.header');

        return ($expectedHeader === $headers->get(self::X_AGGREGATOR_HEADER));
    }
}
