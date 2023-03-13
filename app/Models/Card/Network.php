<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\Core as TerminalCore;

class Network
{
    const AMEX   = 'AMEX';
    const DICL   = 'DICL';
    const DISC   = 'DISC';
    const JCB    = 'JCB';
    const MAES   = 'MAES';
    const MC     = 'MC';
    const RUPAY  = 'RUPAY';
    const UNP    = 'UNP';
    const VISA   = 'VISA';
    const BAJAJ  = 'BAJAJ';

    // Unidentified
    const UNKNOWN = 'UNKNOWN';

    /**
     * | BAJAJ 0| RUPAY 1| JCB 0| Visa 1| MAES 1| MC 1| DICL 0| AMEX 0|
     * Bin => 1011100
     */
    const DEFAULT_CARD_NETWORKS = 92;

    public const NETWORKS_SUPPORTING_GLOBAL_TOKENS = [
        self::MC, // MasterCard
        self::VISA, // Visa
    ];

    public const NETWORKS_SUPPORTING_TOKEN_PROVISIONING = [
        self::DICL, // Diners
        self::MC, // MasterCard
        self::RUPAY, // RuPay
        self::VISA, // Visa
        self::AMEX, // Amex
    ];

    public static $fullName = [
        self::AMEX    => 'American Express',
        self::DICL    => 'Diners Club',
        self::DISC    => 'Discover',
        self::JCB     => 'JCB',
        self::MAES    => 'Maestro',
        self::MC      => 'MasterCard',
        self::RUPAY   => 'RuPay',
        self::UNKNOWN => 'Unknown',
        self::VISA    => 'Visa',
        self::UNP     => 'Union Pay',
        self::BAJAJ   => 'Bajaj Finserv',
    ];

    public static $colorCodes = [
        self::AMEX    => '#2584C3',
        self::DICL    => '#6C89D9',
        self::MAES    => '#25C395',
        self::MC      => '#25BAC3',
        self::RUPAY   => '#74C674',
        self::VISA    => '#C15482',
        self::BAJAJ   => '#0069B4',
        self::UNKNOWN => '#E74C3C'
    ];

    public static $networks = [
        self::AMEX,
        self::DICL,
        self::JCB,
        self::MAES,
        self::MC,
        self::RUPAY,
        self::UNP,
        self::VISA,
        self::DISC,
        self::BAJAJ
    ];

    public static $cardNetworkMap = [
        Network::AMEX  => 1,
        Network::DICL  => 2,
        Network::MC    => 4,
        Network::MAES  => 8,
        Network::VISA  => 16,
        Network::JCB   => 32,
        Network::RUPAY => 64,
        Network::BAJAJ => 128,
        Network::UNP => 256,

    ];

    // use https://regex101.com/
    // MC bin ranges (222100-272099,510000-559999,590000-599999)
    public static $networkRegexes = [
        self::BAJAJ => '/^203040/',
        self::MC    => '/^(5[1-5,9][0-9]{3}|222[1-8][0-9]{1}|2229[0-8]|22299|22[3-9][0-9]{2}|2[3-6][0-9]{3}|27[01][0-9]{2}|2720[0-8]|27209)[0-9]{1,}$/',
        self::VISA  => '/^4[0-9]{5,}$/',
        self::AMEX  => '/^3[47][0-9]{4,}$/',
        self::JCB   => '/^((?!35380[0,2])(?:2131|1800|35[0-9]{2}))[0-9]{2,}$/',
        self::DICL  => '/^3(?:0[0-5]|[68][0-9])[0-9]{3,}$/',
        self::UNP   => '/^62[0-9]{4,}$/',
        self::RUPAY => '/^(508[5-9]|6(069(8[5-9]|9)|07([0-8]|9([0-7]|8[0-4]))|08([0-4]|500)|52([2-9]|1[5-9])|53(0|1[0-4]))|35380[0,2])/',
        self::MAES  => '/^(50[1-7,9]|508[0-4]|63|66|6[8-9]|600[0-9]|6010|601[2-9]|60[2-5]|6060|609|61|620|621|6220|6221[0-1])[0-9]{1,}$/',
        self::DISC  => '/^6(?:011|5[0-9]{2})[0-9]{2,}$/',
    ];

    public static $unsupportedNetworks = [
//        self::AMEX,
//        self::DICL,
        self::DISC,
        self::JCB,
//        self::MAES,
//        self::RUPAY,
//        self::UNP,
    ];

    public static $recurringNetworks = [
        self::VISA,
        self::MC,
    ];

    public static $cvvLength = [
        self::AMEX => 4
    ];

    public static $dccSupportedNetworks = [
        self::MC,
        self::VISA,
    ];

    public static $avsSupportedNetworks = [
        self::MC,
        self::VISA,
    ];

    /**
     * Returns enabled networks Supporting tokenisation on global merchant.
     *
     * @return array
     */
    public static function getGlobalMerchantTokenisationNetworks(): array
    {
        $onboardedNetworks = (new TerminalCore())->getMerchantTokenisationOnboardedNetworks(
            Account::SHARED_ACCOUNT
        );

        return array_intersect(
            $onboardedNetworks,
            self::NETWORKS_SUPPORTING_GLOBAL_TOKENS
        );
    }

    private static function detectNetworkFromDatabase($iin)
    {
        $iinDetails = (new Card\Repository)->retrieveIinDetails($iin);

        if ($iinDetails === null)
        {
            return null;
        }

        $cardNetwork = $iinDetails->getNetworkCode();

        if (strtoupper($cardNetwork) === self::UNKNOWN)
        {
            return null;
        }

        return $cardNetwork;
    }

    private static function detectNetworkFromRegex($iin)
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

    /**
     * The source of truth regarding card network is as follows
     * First, we try to detect network from the iin table in database.
     * If not found in above step, we fall back on regex based matching of networks
     * If network detection fails in both the steps, we return UNKNOWN network
     *
     * @iin Detects network code on basis of iin.
     */
    public static function detectNetwork($iin)
    {
        return static::detectNetworkFromDatabase($iin) ?? static::detectNetworkFromRegex($iin) ?? self::UNKNOWN;
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

    public static function isValidNetworkCode($network)
    {
        return (in_array($network, self::$networks, true));
    }

    public static function isValidNetworkName($network)
    {
        return (NetworkName::isValidNetworkFullName($network));
    }

    public static function isUnsupportedNetwork($network)
    {
        return (in_array($network, self::$unsupportedNetworks, true));
    }

    /**
     * Iterates through cardnetwork and returns the hex value to be stored
     */
    public static function getHexValue(array $cardNetworks): int
    {
        $cardNetwork = 0;

        foreach ($cardNetworks as $network => $value)
        {
            $value = (int) $value;

            $bitPosition = self::$cardNetworkMap[strtoupper($network)];

            // Set the bit
            if ($value === 1)
            {
                $cardNetwork = $cardNetwork | $bitPosition;
            }
            // Reset the bit
            else
            {
                $cardNetwork = $cardNetwork & (~$bitPosition);
            }
        }

        return $cardNetwork;
    }

    public static function getEnabledCardNetworks($networks): array
    {
        $cardNetworks = [];

        foreach (self::$cardNetworkMap as $cardNetwork => $value)
        {
            if (($networks & $value) > 0)
            {
                $cardNetworks[$cardNetwork] = 1;
            }
            else
            {
                $cardNetworks[$cardNetwork] = 0;
            }
        }

        return $cardNetworks;
    }

    public static function getFullName($network)
    {
        if (array_key_exists($network, self::$fullName))
        {
            return self::$fullName[$network];
        }

        return self::$fullName[self::UNKNOWN];
    }

    public static function getFullNames(array $networks): array
    {
        return array_map(
            function($network)
            {
                return self::getFullName($network);
            },
            $networks);
    }

    public static function getCode($fullName)
    {
        if (isset(NetworkName::$codes[$fullName]))
        {
            return NetworkName::$codes[$fullName];
        }

        return Network::UNKNOWN;
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

    public static function getAllNetworkCodes():array
    {
        return self::$networks;
    }

    public static function isDCCSupportedNetwork($networkCode)
    {
        return in_array($networkCode, self::$dccSupportedNetworks, true);
    }

    public static function isAVSSupportedNetwork($networkCode)
    {
        return in_array($networkCode, self::$avsSupportedNetworks, true);
    }
}
