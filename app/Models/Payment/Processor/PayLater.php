<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Bank\IFSC;

class PayLater
{
    const EPAYLATER    = 'epaylater';
    const GETSIMPL     = 'getsimpl';
    const ICICI        = 'icic';
    const FLEXMONEY    = 'flexmoney';
    const LAZYPAY      = 'lazypay';

    const HDFC         = 'hdfc';
    const KKBK         = 'kkbk';

    /** @var int[] The minimum order/transaction amount in paisa for each paylater provider. */
    public const MIN_AMOUNTS = [
        self::GETSIMPL => 100,    // Rs. 1
        self::LAZYPAY  => 100,    // Rs. 1
        self::HDFC     => 100000, // Rs. 1000
        self::ICICI    => 100,    // Rs. 1
        self::KKBK     => 200000, // Rs. 2000
    ];

    /** @var string[] The order in which pay later providers should be displayed on checkout */
    public const CHECKOUT_DISPLAY_ORDER = [
        self::GETSIMPL,
        self::LAZYPAY,
        self::ICICI,
        self::HDFC,
        self::KKBK,
    ];

    public static $fullName = [
        self::EPAYLATER    => 'ePayLater',
        self::GETSIMPL     => 'getsimpl',
        self::ICICI        => 'icic',
        self::FLEXMONEY    => 'flexmoney',
        self::LAZYPAY      => 'lazypay',
    ];

    public static $fullNameForSupportedBanks = [
        self::HDFC    => 'hdfc',
        self::KKBK    => 'kkbk',
    ];

    public static $supportedBanks = [
        self::FLEXMONEY => [
            IFSC::HDFC,
            IFSC::KKBK
        ]
    ];

    public static $defaultDisabledBanks = [
        self::FLEXMONEY => [
            IFSC::KKBK
        ]
    ];

    public static function exists($provider)
    {
        if (self::isMultilenderProvider($provider) === true)
        {
            return false;
        }

        if (self::getProviderForBank($provider) != null)
        {
            return true;
        }

        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getPaylaterDirectAquirers()
    {
        return array_keys(self::$fullName);
    }

    public static function getName($provider)
    {
        if (in_array($provider, self::getPaylaterDirectAquirers()) === true)
        {
            return self::$fullName[$provider];
        }

        return self::$fullNameForSupportedBanks[$provider];
    }

    public static function getProviderForBank($bank)
    {
        foreach (self::$supportedBanks as $provider => $supportedBanks)
        {
            if (in_array(strtoupper($bank), $supportedBanks) === true)
            {
                return $provider;
            }
        }
        return null;
    }

    public static function isMultilenderProvider($provider)
    {
        return array_key_exists($provider, self::$supportedBanks);
    }

    public static function getSupportedBanksForMultilenderProvider($provider)
    {
        return self::$supportedBanks[$provider];
    }

    public static function getDefaultDisabledBanksForMultilenderProvider($provider)
    {
        return self::$defaultDisabledBanks[$provider];
    }
}
