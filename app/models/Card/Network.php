<?php

namespace Models\Card;

use EE\Error\ErrorCode;
use EE\Exception;

class Network
{
    const MASTERCARD = 'MasterCard';

    const VISA = 'Visa';

    const RUPAY = 'RuPay';

    const MAESTRO = 'Maestro';

    const AMEX = 'American Express';

    const JCB = 'JCB';

    const DINERS_CLUB = 'Diners Club';

    const DISCOVER = 'Discover';

    const UNIDENTIFIED = 'Unidentified';

    public static $networks = array(
        self::MASTERCARD,
        self::VISA,
        self::RUPAY,
        self::MAESTRO,
        self::AMEX,
        self::JCB,
        self::DINERS_CLUB,
        self::DISCOVER);

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
        self::MASTERCARD => '/^5[1-5][0-9]{5,}$/',
        self::VISA => '/^4[0-9]{6,}$/',
        self::AMEX => '/^3[47][0-9]{5,}$/',
        self::JCB => '/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/',
        self::DINERS_CLUB => '/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/',
        self::DISCOVER => '/^6(?:011|5[0-9]{2})[0-9]{3,}$/',
        self::MAESTRO => null,
        self::RUPAY => null);

    public static $unsupportedNetworks = array(
        self::AMEX,
        self::JCB);

    public static function detectNetwork($iin)
    {
        $cardNetwork = null;

        foreach (self::$networkRegexes as $network => $regex)
        {
            if ($regex === null)
            {
                $func = 'is'.studly_case($network);
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
                throw new Exception\CardErrorException(ErrorCode::CARD_ERROR_NOT_SUPPORTED);
            }
        }

        return self::UNIDENTIFIED;
    }

    public static function isMaestro($iin)
    {
        return in_array(substr($iin, 0, 4), self::$maestroFirstFour);
    }

    public static function isRupay($iin)
    {
        // @todo: determine regex for this one.
        return false;
    }

    public static function checkNetworkValidity($network)
    {
        if (in_array($network, self::$networks) === false)
        {
            throw new Exception\LogicException(ErrorCode::LOGICAL_ERROR_UNIDENTIFIED_CARD_NETWORK);
        }
    }
}