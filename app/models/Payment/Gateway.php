<?php

namespace Models\Payment;

use EE\Exception;
use Models\Settlement;

class Gateway
{
    const HDFC      = 'hdfc';
    const ATOM      = 'atom';
    const AXIS      = 'axis';
    const GENIUS    = 'genius';

    public static $channels = array(
        self::HDFC          => Settlement\Channel::KOTAK,
        self::ATOM          => Settlement\Channel::ATOM,
        self::AXIS          => Settlement\Channel::KOTAK,
        self::GENIUS        => Settlement\Channel::KOTAK,
    );

    public static function getChannel($gateway)
    {
        return self::$channels[$gateway];
    }

    public static function isValidGateway($gateway)
    {
        return (defined(__CLASS__.'::'.strtoupper($gateway)));
    }

    public static function validateGateway($gateway)
    {
        if (self::isValidGateway($gateway) === false)
        {
            throw new Exception\LogicException(
                'Unknown gateway. Terminal Id: ' . $terminal->getId() .
                ' Gateway: ' . $terminal->getGateway());
        }
    }
}
