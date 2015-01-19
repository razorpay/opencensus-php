<?php

namespace Models\Settlement;

use Models\Payment;

class Channel
{
    const KOTAK = 'kotak';
    const ATOM = 'atom';

    public static $gateways = array(
        self::KOTAK => array(
            Payment\Gateway::HDFC
        ),

        self::ATOM => array(
            Payment\Gateway::ATOM
        ),
    );

    public static function getChannels()
    {
        return [self::KOTAK, self::ATOM];
    }

    public static function getGateways($channel)
    {
        return self::$gateways[$channel];
    }
}