<?php

namespace RZP\Models\Settlement;

use RZP\Models\Payment;

class Channel
{
    const KOTAK = 'kotak';
    const ATOM = 'atom';

    public static $gateways = array(
        self::KOTAK => array(
            Payment\Gateway::AMEX,
            Payment\Gateway::AXIS_GENIUS,
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::BILLDESK,
            Payment\Gateway::HDFC,
            Payment\Gateway::KOTAK,
            Payment\Gateway::MOBIKWIK,
            Payment\Gateway::PAYTM,
            Payment\Gateway::NETBANKING_HDFC,
            Payment\Gateway::WALLET_PAYZAPP,
            Payment\Gateway::WALLET_PAYUMONEY,
            Payment\Gateway::WALLET_OLAMONEY,
            Payment\Gateway::WALLET_FREECHARGE,
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
