<?php

namespace Models\Payment;

class Method
{
    const CARD          = 'card';
    const NETBANKING    = 'netbanking';
    const WALLET        = 'wallet';
    const EMI           = 'emi';

    public static function formatted($method)
    {
        $methodFormat = [
            self::CARD          => 'Card',
            self::NETBANKING    => 'Net Banking',
            self::WALLET        => 'Wallet',
            self::EMI           => 'EMI'
        ];

        return $methodFormat[$method];
    }
}
