<?php

namespace RZP\Gateway\Wallet\Payzapp;

class TransactionType
{
    public static $codes = array(
        'SALE'    =>    9003,
        'VOID'    =>    9011,
        'SETTLE'  =>    9021,
        'REFUND'  =>    9030,
    );
}
