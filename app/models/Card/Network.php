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

    protected static $fullName = array(
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
        self::MAES,
        self::MC,
        self::RUPAY,
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
        self::MAES  => null,
        self::RUPAY => null);

    public static $unsupportedNetworks = array(
        self::AMEX,
        self::JCB,
        self::DISC,
        self::DICL,
        self::UNP);

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
        return (in_array($network, self::$networks));
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