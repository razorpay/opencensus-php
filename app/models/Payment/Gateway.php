<?php

namespace Models\Payment;

use EE\Exception;
use Models\Settlement;

class Gateway
{
    const HDFC              = 'hdfc';
    const ATOM              = 'atom';
    const AXIS_MIGS         = 'axis_migs';
    const AXIS_GENIUS       = 'axis_genius';
    const KOTAK             = 'kotak';
    const PAYTM             = 'paytm';
    const NETBANKING_HDFC   = 'netbanking_hdfc';

    public static $channels = array(
        self::ATOM              => Settlement\Channel::ATOM,
        self::HDFC              => Settlement\Channel::KOTAK,
        self::AXIS_MIGS         => Settlement\Channel::KOTAK,
        self::AXIS_GENIUS       => Settlement\Channel::KOTAK,
        self::KOTAK             => Settlement\Channel::KOTAK,
        self::PAYTM             => Settlement\Channel::KOTAK,
        self::NETBANKING_HDFC   => Settlement\Channel::KOTAK,
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
                'Unknown gateway. Gateway: ' . $gateway);
        }
    }
}
