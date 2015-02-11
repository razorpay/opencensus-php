<?php

namespace Models\Payment;

use Models\Settlement;

class Gateway
{
    const HDFC = 'hdfc';
    const ATOM = 'atom';

    public static $channels = array(
        self::HDFC          => Settlement\Channel::KOTAK,
        self::ATOM          => Settlement\Channel::ATOM,
    );

    public static function getChannel($gateway)
    {
        return self::$channels[$gateway];
    }
}
