<?php

namespace RZP\Models\Card;

use RZP\Exception;

class Network
{
    const AMEX  = 'AMEX';
    const DICL  = 'DICL';
    const DISC  = 'DISC';
    const JCB   = 'JCB';
    const MAES  = 'MAES';
    const MC    = 'MC';
    const RUPAY = 'RUPAY';
    const UNP   = 'UNP';
    const VISA  = 'VISA';

    // Unidentified
    const UNKNOWN = 'UNKNOWN';

    public static $fullName = array(
        self::AMEX    => 'American Express',
        self::DICL    => 'Diners Club',
        self::DISC    => 'Discover',
        self::JCB     => 'JCB',
        self::MAES    => 'Maestro',
        self::MC      => 'MasterCard',
        self::RUPAY   => 'RuPay',
        self::UNKNOWN => 'Unknown',
        self::VISA    => 'Visa',
        self::UNP     => 'Union Pay');

    public static $colorCodes = array(
        self::AMEX    => '#2584C3',
        self::DICL    => '#6C89D9',
        self::MAES    => '#25C395',
        self::MC      => '#25BAC3',
        self::RUPAY   => '#74C674',
        self::VISA    => '#C15482',
        self::UNKNOWN => '#E74C3C'
    );

   public static $networks = array(
        self::AMEX,
        self::DICL,
        self::DISC,
        self::JCB,
        self::MAES,
        self::MC,
        self::RUPAY,
        self::UNP,
        self::VISA,
    );

    public static $networkRegexes = array(
        self::MC    => '/^5[1-5][0-9]{4,}$/',
        self::VISA  => '/^4[0-9]{5,}$/',
        self::AMEX  => '/^3[47][0-9]{4,}$/',
        self::JCB   => '/^(?:2131|1800|35[0-9]{2})[0-9]{2,}$/',
        self::DICL  => '/^3(?:0[0-5]|[68][0-9])[0-9]{3,}$/',
        self::UNP   => '/^62[0-9]{4,}$/',
        self::RUPAY => '/^(508[5-9][0-9][0-9]|60698[5-9]|60699[0-9]|60738[4-9]|60739[0-9]|607[0-8][0-9][0-9]|6079[0-7][0-9]|60798[0-4]|608[0-4][0-9][0-9]|608500|6521[5-9][0-9]|652[2-9][0-9][0-9]|6530[0-9][0-9]|6531[0-4][0-9]|6070(66|90|32|74|94|27|93|02|76)|6071(26|05|65)|607243)[0-9]{0,}$/',
        self::MAES  => '/^(50[1-7,9]|508[0-4]|63|66|6[8-9]|600[0-9]|6010|601[2-9]|60[2-5]|6060|609|61|620|621|6220|6221[0-1])[0-9]{1,}$/',
        self::DISC  => '/^6(?:011|5[0-9]{2})[0-9]{2,}$/',
    );

    public static $unsupportedNetworks = array(
//        self::AMEX,
//        self::DICL,
        self::DISC,
        self::JCB,
//        self::MAES,
//        self::RUPAY,
        self::UNP,
     );

    public static $recurringNetworks = array(
        self::VISA,
        self::MC,
    );

    public static $cvvLength = array(
        self::AMEX => 4);

    /**
     * Detects network on basis of iin.
     * @todo : Right now it's detecting on number. Shift it to iin.
     */
    public static function detectNetwork($iin)
    {
        $cardNetwork = null;

        foreach (self::$networks as $network)
        {
            if (self::checkNetwork($iin, $network) === true)
            {
                $cardNetwork = $network;
                break;
            }
        }

        if ($cardNetwork === null)
        {
            $cardNetwork = self::UNKNOWN;
        }

        return $cardNetwork;
    }

    public static function checkNetwork($iin, $network)
    {
        $regex = self::$networkRegexes[$network];

        if ($regex === null)
        {
            $func = 'is'.$network;

            return self::{$func}($iin);
        }
        else
        {
            return (preg_match($regex, $iin) === 1);
        }
    }

    public static function checkNetworkValidity($network)
    {
        if (self::isValidNetwork($network) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid card network given');
        }
    }

    public static function isValidNetwork($network)
    {
        return ((defined(get_class().'::'.$network)) or
                (NetworkName::isValidNetworkFullName($network)));
    }

    public static function isValidNetworkName($network)
    {
        return (NetworkName::isValidNetworkFullName($network));
    }

    public static function isUnsupportedNetwork($network)
    {
        return (in_array($network, self::$unsupportedNetworks));
    }

    public static function getFullName($network)
    {
        if (array_key_exists($network, self::$fullName))
        {
            return self::$fullName[$network];
        }

        return self::$fullName[self::UNKNOWN];
    }

    public static function getCode($fullName)
    {
        return NetworkName::$codes[$fullName];
    }

    public static function getColorCode($networkCode)
    {
        return self::$colorCodes[$networkCode];
    }

    public static function getSupportedNetworksNamesMap()
    {
        $supported = array_diff(self::$networks, self::$unsupportedNetworks);
        return array_intersect_key(self::$fullName, array_flip($supported));
    }
}
