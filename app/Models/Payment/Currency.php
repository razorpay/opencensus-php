<?php

namespace RZP\Models\Payment;

class Currency
{
    const INR = 'INR';
    const USD = 'USD';

    const SUPPORTED_CURRENCIES = [
        self::INR,
        self::USD,
    ];
}

