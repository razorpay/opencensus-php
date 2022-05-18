<?php

namespace RZP\Models\Payment\Processor;

class App
{
    const CRED   = 'cred';
    const TWID   = 'twid';
    const TRUSTLY  = 'trustly';
    const EMERCHANTPAY = 'emerchantpay';
    const POLI = 'poli';
    const GIROPAY = 'giropay';
    const SOFORT = 'sofort';

    /**
     * all apps are disabled by default
     * 0000000
     */
    const DEFAULT_APPS = 0;

    public static $fullName = [
        self::CRED    => 'Cred',
        self::TWID    => 'Twid',
        self::TRUSTLY => 'Trustly',
        self::POLI    => 'Poli',
        self::SOFORT  => 'Sofort',
        self::GIROPAY => 'Giropay',
    ];

    public static $appMap = [
        self::CRED  => 1,
        self::TWID  => 2,
        self::TRUSTLY => 4,
        self::POLI => 8,
        self::SOFORT => 16,
        self::GIROPAY => 32,
    ];

     public static $apps = [
        self::CRED,
        self::TWID,
        self::TRUSTLY,
        self::POLI,
        self::SOFORT,
        self::GIROPAY,
    ];

    public static $supportedApps = [
        self::CRED  =>  [
            self::CRED
        ],
        self::TWID  =>  [
            self::TWID
        ],
        self::EMERCHANTPAY  =>  [
            self::TRUSTLY,
            self::POLI,
            self::SOFORT,
            self::GIROPAY,
        ],
    ];

    public static function isValidApp($app)
    {
        return (in_array($app, self::$apps, true));
    }

    public static function checkApp($app)
    {
        if (self::isValidApp($app) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid app given');
        }
    }

    public static function getName($app)
    {
        return self::$fullName[$app];
    }

    /**
     * Iterates through apps and returns the hex value to be stored
     */
    public static function getHexValue(array $apps): int
    {
        $appValue = 0;

        foreach ($apps as $app => $value)
        {
            $value = (int) $value;

            $bitPosition = self::$appMap[$app];

            // Set the bit
            if ($value === 1)
            {
                $appValue = $appValue | $bitPosition;
            }
            // Reset the bit
            else
            {
                $appValue = $appValue & (~$bitPosition);
            }
        }

        return $appValue;
    }

    public static function getEnabledApps($apps): array
    {
        $appProviders = [];

        foreach (self::$appMap as $app => $value)
        {
            if (($apps & $value) > 0)
            {
                $appProviders[$app] = 1;
            }
            else
            {
                $appProviders[$app] = 0;
            }
        }

        return $appProviders;
    }

    public static function getFullName($app)
    {
        if (array_key_exists($app, self::$fullName))
        {
            return self::$fullName[$app];
        }
    }

    public static function getSupportedAppsForGateway($gateway)
    {
        return self::$supportedApps[$gateway];
    }
}
