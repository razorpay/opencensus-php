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
            'card'          => 'Card',
            'netbanking'    => 'Net Banking',
            'wallet'        => 'Wallet'
        ];

        return $methodFormat[$method];
    }
}
