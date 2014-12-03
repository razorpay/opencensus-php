<?php

namespace Models\Card;

use EE\Error\ErrorCode;
use EE\Exception;

class Network
{
    const MC    = 'MC';
    const VISA  = 'VISA';
    const DICL  = 'DICL';
    const RUPAY = 'RUPAY';
    const AMEX  = 'AMEX';
    const JCB   = 'JCB';
    const MAES  = 'MAES';
    const DISC  = 'DISC';

    // Unidentified
    const OTHER = 'OTHER';
    const UNKNOWN = 'UNKNOWN';

    protected $fullName = array(
        self::MC      => 'MasterCard',
        self::VISA    => 'Visa',
        self::RUPAY   => 'RuPay',
        self::MAES    => 'Maestro',
        self::AMEX    => 'American Express',
        self::JCB     => 'JCB',
        self::DICL    => 'Diners Club',
        self::DISC    => 'Discover',
        self::OTHER   => 'Other',
        self::UNKNOWN => 'Unknown');

   public static $networks = array(
        self::MC,
        self::VISA,
        self::RUPAY,
        self::MAES,
        self::AMEX,
        self::JCB,
        self::DICL,
        self::DISC);

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
        self::MAES  => null,
        self::RUPAY => null);

    public static $unsupportedNetworks = array(
        self::AMEX,
        self::JCB,
        self::DISC);

    public static function detectNetwork($iin)
    {
        $cardNetwork = null;

        foreach (self::$networkRegexes as $network => $regex)
        {
            if ($regex === null)
            {
                $func = 'is'.$network;
                if (self::{$func}($iin) === true)
                {
                    $cardNetwork = $network;
                    break;
                }
            }
            else
            {
                $ret =  preg_match($regex, $iin);

                if ($ret === 1)
                {
                    $cardNetwork = $network;
                    break;
                }
            }
        }

        if ($cardNetwork !== null)
        {
            if (in_array($cardNetwork, self::$unsupportedNetworks))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                    'number');
            }
        }

        return self::OTHER;
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
        if (in_array($network, self::$networks) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid card network given');
        }
    }
}