<?php

namespace Models\Payment\Processor;

class Wallet
{
    const PAYTM     = 'paytm';
    const PAYZAPP   = 'payzapp';
    const MOBIKWIK  = 'mobikwik';
    const PAYUMONEY = 'payumoney';

    public static function exists($wallet)
    {
        return defined(get_class().'::'.$wallet);
    }
}
