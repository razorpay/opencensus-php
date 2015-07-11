<?php

namespace Models\Card;

use EE\Error\ErrorCode;
use EE\Exception;

class Network
{
    const AMEX  = 'AMEX';
    const DICL  = 'DICL';
    const DISC  = 'DISC';
    const JCB   = 'JCB';
    const MAES  = 'MAES';
    const MC    = 'MC';
    const RUPAY = 'RUPAY';
    const VISA  = 'VISA';
    const UNP   = 'UNP';

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

   public static $networks = array(
        self::AMEX,
        self::DICL,
        self::DISC,
        self::JCB,
        self::MC,
        self::VISA,
        self::UNP);

    public static $maestroFirstFour = array(
        '5018',
        '5020',
        '5038',
        '5612',
        '5893',
        '6304',
        '6759',
        '6761',
        '6762',
        '6763',
        '0604',
        '6390');

    public static $networkRegexes = array(
        self::MC    => '/^5[1-5][0-9]{5,}$/',
        self::VISA  => '/^4[0-9]{6,}$/',
        self::AMEX  => '/^3[47][0-9]{5,}$/',
        self::JCB   => '/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/',
        self::DICL  => '/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/',
        self::DISC  => '/^6(?:011|5[0-9]{2})[0-9]{3,}$/',
        self::UNP   => '/^62[0-9]{14,}$/',
        self::MAES  => '/^(500|50[1-8]|50[2-9]|5[6-9]|6010|601[2-9]|60[2-5]|6060|62(1|7|9)|67([0-5]|7)|676([0-6]|[8-9])|679)[0-9]{8,15}$/',
        self::RUPAY => '/^(508[5-9][0-9][0-9]|60698[5-9]|60699[0-9]|60738[4-9]|60739[0-9]|607[0-8][0-9][0-9]|6079[0-7][0-9]|60798[0-4]|608[0-4][0-9][0-9]|608500|6521[5-9][0-9]|652[2-9][0-9][0-9]|6530[0-9][0-9]|6531[0-4][0-9]|6070(66|90|32|74|94|27|93|02|76)|6071(26|05|65)|607243)[0-9]{10,13}$/'
    );

    public static $unsupportedNetworks = array(
        self::AMEX,
        self::DICL,
        self::DISC,
        self::JCB,
        self::MAES,
        self::RUPAY,
        self::UNP,
    );

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

    public static function isMAES($iin)
    {
        return in_array(substr($iin, 0, 4), self::$maestroFirstFour);
    }

    public static function isRUPAY($iin)
    {
        //
        // @todo: determine regex for this one.
        //
        // Looking at lots of images of Rupay card suggests that it
        // may start with 607*
        //
        return false;
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
        return ((in_array($network, self::$networks)) or
                (in_array($network, array_values(self::$fullName))));
    }

    public static function isValidNetworkName($network)
    {
        return (array_search($network, self::$fullName) !== false);
    }

    public static function isUnsupportedNetwork($network)
    {
        return (in_array($network, self::$unsupportedNetworks));
    }

    public static function getFullName($network)
    {
        return self::$fullName[$network];
    }

    public static function getCode($fullName)
    {
        $codes = array_flip(self::$fullName);

        return $codes[$fullName];
    }
}