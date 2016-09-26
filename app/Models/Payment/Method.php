<?php

namespace RZP\Models\Payment;

use RZP\Exception;

class Method
{
    const CARD          = 'card';
    const NETBANKING    = 'netbanking';
    const WALLET        = 'wallet';
    const EMI           = 'emi';
    const UPI           = 'upi';

    protected static $methods = array(
        self::CARD       => 'Card',
        self::NETBANKING => 'Net Banking',
        self::WALLET     => 'Wallet',
        self::UPI        => 'UPI',
        self::EMI        => 'EMI',
    );

    public static function formatted($method)
    {
        return self::$methods[$method];
    }

    public static function getAllPaymentMethods()
    {
        return array_keys(self::$methods);
    }

    public static function isValid($method)
    {
        return defined(__CLASS__ . '::' . strtoupper($method));
    }

    public static function validateMethod($method)
    {
        if (self::isValid($method) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payment method: ' . $method);
        }
    }
}
