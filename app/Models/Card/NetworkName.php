<?php

namespace RZP\Models\Card;

use RZP\Exception;

class NetworkName
{
    const AMEX    = 'American Express';
    const DICL    = 'Diners Club';
    const DISC    = 'Discover';
    const JCB     = 'JCB';
    const MAES    = 'Maestro';
    const MC      = 'MasterCard';
    const RUPAY   = 'RuPay';
    const UNKNOWN = 'Unknown';
    const VISA    = 'Visa';
    const UNP     = 'Union Pay';
    const BAJAJ   = 'Bajaj Finserv';


    // in case of any changes in gateway config, please contact smart routing team
    // changes done here won't be reflected in routing
    public static $codes = array(
        self::AMEX    => Network::AMEX,
        self::DICL    => Network::DICL,
        self::DISC    => Network::DISC,
        self::JCB     => Network::JCB,
        self::MAES    => Network::MAES,
        self::MC      => Network::MC,
        self::RUPAY   => Network::RUPAY,
        self::UNKNOWN => Network::UNKNOWN,
        self::VISA    => Network::VISA,
        self::UNP     => Network::UNP,
        self::BAJAJ   => Network::BAJAJ,
    );

    public static function isValidNetworkFullName($name)
    {
        return isset(self::$codes[$name]);
    }
}
