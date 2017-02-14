<?php

namespace RZP\Models\Settlement;

use RZP\Models\Payment;

class Channel
{
    const KOTAK     = 'kotak';
    const ATOM      = 'atom';

    public static $gateways = [
        self::KOTAK => [
            Payment\Gateway::AMEX,
            Payment\Gateway::AXIS_GENIUS,
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::BILLDESK,
            Payment\Gateway::HDFC,
            Payment\Gateway::MOBIKWIK,
            Payment\Gateway::PAYTM,
            Payment\Gateway::NETBANKING_HDFC,
            Payment\Gateway::WALLET_PAYZAPP,
            Payment\Gateway::WALLET_PAYUMONEY,
            Payment\Gateway::WALLET_OLAMONEY,
            Payment\Gateway::WALLET_FREECHARGE,
            Payment\Gateway::WALLET_JIOMONEY,
            Payment\Gateway::WALLET_OPENWALLET,
            Payment\Gateway::MARKETPLACE,
        ],
        self::ATOM => [
            Payment\Gateway::ATOM
        ],
    ];

    public static function getChannels()
    {
        return [self::KOTAK, self::ATOM];
    }

    public static function getGateways($channel)
    {
        return self::$gateways[$channel];
    }
}
