<?php

namespace RZP\Models\Payment;

class Method
{
    const CARD          = 'card';
    const NETBANKING    = 'netbanking';
    const WALLET        = 'wallet';
    const EMI           = 'emi';

    protected static $methods = array(
        self::CARD       => 'Card',
        self::NETBANKING => 'Net Banking',
        self::WALLET     => 'Wallet',
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

    public static function validateMethod($method)
    {
        if (defined(__CLASS__.'::'.strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Payment method: ' . $method);
        }
    }
}
