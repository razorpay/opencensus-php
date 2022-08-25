<?php

namespace RZP\Models\Payment\Gateway;

use RZP\Models\Currency\Currency;

class Constants
{
    const PAYPAL_DISABLED_CURRENCIES = [Currency::INR];

    const PAYPAL_SUPPORTED_CURRENCIES = [
        Currency::AUD,
        Currency::CAD,
        Currency::CNY,
        Currency::CZK,
        Currency::DKK,
        Currency::EUR,
        Currency::HKD,
        Currency::HUF,
        Currency::ILS,
        Currency::MYR,
        Currency::MXN,
        Currency::PHP,
        Currency::NZD,
        Currency::NOK,
        Currency::GBP,
        Currency::RUB,
        Currency::SGD,
        Currency::SEK,
        Currency::CHF,
        Currency::THB,
        Currency::USD
    ];        
}
