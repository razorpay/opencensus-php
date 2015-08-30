<?php

namespace Models\Payment;

class Method
{
    const CARD          = 'card';
    const NETBANKING    = 'netbanking';
    const WALLET        = 'wallet';

    public static function formatted($method)
    {
        $methodFormat = [
            self::CARD          => 'Card',
            self::NETBANKING    => 'Net Banking',
            self::WALLET        => 'Wallet'
        ];

        return $methodFormat[$method];
    }
}
