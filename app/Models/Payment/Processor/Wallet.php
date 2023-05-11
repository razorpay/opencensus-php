<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;

class Wallet
{
    const PAYTM             = 'paytm';
    const PAYZAPP           = 'payzapp';
    const MOBIKWIK          = 'mobikwik';
    const PAYUMONEY         = 'payumoney';
    const OLAMONEY          = 'olamoney';
    const AIRTELMONEY       = 'airtelmoney';
    const AMAZONPAY         = 'amazonpay';
    const FREECHARGE        = 'freecharge';
    const BAJAJPAY          = 'bajajpay';
    const JIOMONEY          = 'jiomoney';
    const SBIBUDDY          = 'sbibuddy';
    const OPENWALLET        = 'openwallet';
    const RAZORPAYWALLET    = 'razorpaywallet';
    const MPESA             = 'mpesa';
    const PHONEPE           = 'phonepe';
    const PAYPAL            = 'paypal';
    const PHONEPE_SWITCH    = 'phonepeswitch';

    // non supported wallets by rzp [directly]
    const ITZCASH              = 'itzcash';
    const OXIGEN               = 'oxigen';
    const AMEXEASYCLICK        = "amexeasyclick";
    const PAYCASH              = "paycash";
    const CITIBANKREWARDS      = "citibankrewards";

    // wallets supported in MY
    const MCASH    = 'mcash';
    const BOOST    = 'boost';
    const TOUCHNGO = 'touchngo';
    const GRABPAY  = 'grabpay';

    public static $supportedWalletsForRearch = array(
        self::MCASH,
        self::TOUCHNGO,
        self::GRABPAY,
        self::BOOST
    );

    public static $fullName = array(
        self::MOBIKWIK          => 'Mobikwik',
        self::OLAMONEY          => 'Olamoney (Postpaid + Wallet)',
        self::PAYTM             => 'Paytm',
        self::PAYUMONEY         => 'Payumoney',
        self::PAYZAPP           => 'Payzapp',
        self::AIRTELMONEY       => 'Airtelmoney',
        self::FREECHARGE        => 'Freecharge',
        self::BAJAJPAY          => 'Bajaj Pay',
        self::JIOMONEY          => 'JioMoney',
        self::SBIBUDDY          => 'SBI Buddy',
        self::OPENWALLET        => 'RZP Open Wallet',
        self::RAZORPAYWALLET    => 'Razorpay Wallet',
        self::MPESA             => 'Vodafone mPesa',
        self::AMAZONPAY         => 'AmazonPay',
        self::PHONEPE           => 'PhonePe',
        self::PAYPAL            => 'PayPal',
        self::PHONEPE_SWITCH    => 'PhonePe Switch',
        self::ITZCASH           => 'Itzcash',
        self::OXIGEN            => 'Oxigen',
        self::AMEXEASYCLICK     => 'Amex Easy Click',
        self::PAYCASH           => 'Paycash',
        self::CITIBANKREWARDS   => 'Citibank Reward Points',
        self::MCASH             => 'MCash',
        self::BOOST             => 'Boost',
        self::TOUCHNGO          => 'TouchNGo',
        self::GRABPAY           => 'GrabPay'
    );

    public static $emailRequiredWallets = array(
        self::MOBIKWIK,
        self::OLAMONEY,
        self::PAYUMONEY,
        self::PAYZAPP,
        self::AIRTELMONEY,
        self::FREECHARGE,
        self::JIOMONEY,
        self::SBIBUDDY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::MPESA,
    );

    public static $phoneNotRequiredWallets = array(
        self::PAYPAL,
    );

    public static $indianContactWallets = array(
        self::AIRTELMONEY,
        self::AMAZONPAY,
        self::FREECHARGE,
        self::JIOMONEY,
        self::MOBIKWIK,
        self::MPESA,
        self::OLAMONEY,
        self::OPENWALLET,
        self::RAZORPAYWALLET,
        self::PAYTM,
        self::PAYZAPP,
        self::PAYUMONEY,
        self::PHONEPE,
        self::SBIBUDDY,
        self::PHONEPE_SWITCH,
    );

    public static function exists($wallet)
    {
        return (isset(self::$fullName[$wallet]) === true);
    }

    public static function validateExists($wallet)
    {
        if (self::exists($wallet) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                Payment\Entity::WALLET);
        }
    }

    public static function isEmailRequired(string $wallet)
    {
        return (in_array($wallet, self::$emailRequiredWallets) === true);
    }

    public static function isPhoneRequired(string $wallet)
    {
        return (in_array($wallet, self::$phoneNotRequiredWallets) === false);
    }

    public static function getWalletNetworkNamesMap()
    {
        return self::$fullName;
    }

    public static function getName($wallet)
    {
        return self::$fullName[$wallet];
    }
}
