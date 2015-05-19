<?php

namespace Models\Settlement;

use Models\Payment;

class Channel
{
    const KOTAK = 'kotak';
    const ATOM = 'atom';

    public static $gateways = array(
        self::KOTAK => array(
            Payment\Gateway::HDFC,
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::AXIS_GENIUS,
            Payment\Gateway::KOTAK,
            Payment\Gateway::PAYTM,
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