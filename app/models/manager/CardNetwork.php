<?php

namespace Models\Manager;

class CardNetwork
{
    const MASTERCARD = 'mastercard';

    const VISA = 'visa';

    const RUPAY = 'rupay';

    const MAESTRO = 'maestro';

    const AMEX = 'amex';

    const JCB = 'jcb';

    const DINERS_CLUB = 'diners club';

    const DISCOVER = 'discover';

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

    public static $networks = array(
        self::MASTERCARD => '/^5[1-5][0-9]{5,}$/',
        self::VISA => '/^4[0-9]{6,}$/',
        self::AMEX => '/^3[47][0-9]{5,}$/',
        self::JCB => '/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/',
        self::DINERS_CLUB => '/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/',
        self::DISCOVER => '/^6(?:011|5[0-9]{2})[0-9]{3,}$/',
        self::MAESTRO => null,
        self::RUPAY => null);

    public static function detectNetwork($number)
    {
        foreach (self::$networks as $network => $regex)
        {
            if ($regex === null)
            {
                $func = 'is'.studly_case($network);
                if (self::{$func}($number) === true)
                {
                    return $network;
                }
            }
            else
            {
                $ret =  preg_match($regex, $number);

                if ($ret === 1)
                    return $network;
            }
        }



    }

    public static function isMaestro($number)
    {
        return in_array(substr($number, 0, 4), self::$maestroFirstFour);
    }

    public static function isRupay($number)
    {
        // @todo: determine regex for this one.
        return false;
    }
}