<?php

namespace RZP\Models\Payment;

class Method
{
    const CARD          = 'card';
    const NETBANKING    = 'netbanking';
    const WALLET        = 'wallet';
    const EMI           = 'emi';
    const UPI           = 'upi';

    public static function formatted($method)
    {
        $methodFormat = [
            self::CARD          => 'Card',
            self::NETBANKING    => 'Net Banking',
            self::WALLET        => 'Wallet',
            self::EMI           => 'EMI',
            self::UPI           => 'UPI',
        ];

        return $methodFormat[$method];
    }

    public static function getAllPaymentMethods()
    {
        return array(
            self::CARD,
            self::NETBANKING,
            self::WALLET,
            self::EMI,
            self::UPI
        );
    }

}
