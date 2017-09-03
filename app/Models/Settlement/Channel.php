<?php

namespace RZP\Models\Settlement;

use RZP\Exception;
use RZP\Models\Payment;

class Channel
{
    const KOTAK     = 'kotak';
    const ATOM      = 'atom';
    const ICICI     = 'icici';
    const AXIS      = 'axis';

    public static $gateways = [
        self::KOTAK => [
            Payment\Gateway::AMEX,
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
        ],
        self::ATOM => [
            Payment\Gateway::ATOM
        ],
        self::ICICI => [
            Payment\Gateway::FIRST_DATA,
            Payment\Gateway::NETBANKING_ICICI,
            Payment\Gateway::UPI_ICICI,
        ],
        self::AXIS => [
            Payment\Gateway::AXIS_MIGS,
            Payment\Gateway::AXIS_GENIUS,
        ],
    ];

    public static function getChannels()
    {
        return [self::KOTAK];
    }

    public static function getGateways($channel)
    {
        return self::$gateways[$channel];
    }

    public static function getChannelFromGateway(string $gateway): string
    {
        foreach (self::$gateways as $channel => $gatewayList)
        {
            if (in_array($gateway, $gatewayList, true) === true)
            {
                return $channel;
            }
        }

        throw new Exception\LogicException('Channel not found for gateway ' . $gateway);
    }
}
