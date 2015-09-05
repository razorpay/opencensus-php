<?php

namespace Models\Payment;

use EE\Exception;
use Models\Card\Network;
use Models\Settlement;

class Gateway
{
    const ATOM              = 'atom';
    const AXIS_GENIUS       = 'axis_genius';
    const AXIS_MIGS         = 'axis_migs';
    const BILLDESK          = 'billdesk';
    const HDFC              = 'hdfc';
    const KOTAK             = 'kotak';
    const MOBIKWIK          = 'mobikwik';
    const PAYTM             = 'paytm';
    const NETBANKING_HDFC   = 'netbanking_hdfc';
    const SHARP             = 'sharp';

    public static $channels = array(
        self::ATOM              => Settlement\Channel::ATOM,
        self::AXIS_GENIUS       => Settlement\Channel::KOTAK,
        self::AXIS_MIGS         => Settlement\Channel::KOTAK,
        self::BILLDESK          => Settlement\Channel::KOTAK,
        self::HDFC              => Settlement\Channel::KOTAK,
        self::KOTAK             => Settlement\Channel::KOTAK,
        self::MOBIKWIK          => Settlement\Channel::KOTAK,
        self::PAYTM             => Settlement\Channel::KOTAK,
        self::NETBANKING_HDFC   => Settlement\Channel::KOTAK,
        self::SHARP             => Settlement\Channel::KOTAK,
    );

    public static $methodMap = array(
        Method::CARD => array(
            self::HDFC,
            self::ATOM,
            self::AXIS_MIGS,
            self::AXIS_GENIUS,
            self::KOTAK,
            self::PAYTM,
        ),

        Method::NETBANKING => array(
            self::PAYTM,
            self::BILLDESK,
            self::NETBANKING_HDFC,
        ),

        Method::WALLET => array(
            self::MOBIKWIK,
            self::PAYTM,
        ),
    );

    public static $cardNetworkMap = array(
        self::HDFC => array(
            Network::MC,
            Network::VISA,
            Network::MAES),
        self::AXIS_MIGS => array(
            Network::MC,
            Network::VISA),
        self::AXIS_GENIUS => array(
            Network::MC,
            Network::VISA),
        self::ATOM => array(
            Network::MC,
            Network::VISA,
            Network::MAES),
        self::KOTAK => array(
            Network::RUPAY),
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

    public static function isMethodSupported($method, $gateway)
    {
        return (in_array($gateway, self::$methodMap[$method]));
    }

    public static function isCardNetworkSupported($network, $gateway)
    {
        return ((array_key_exists($gateway, self::$cardNetworkMap)) and
                (in_array($network, self::$cardNetworkMap[$gateway])));
    }
}
