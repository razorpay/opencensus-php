<?php

namespace RZP\Models\Base\Traits;

use RZP\Models\Currency\Currency;

trait CustomReplacesAttributes
{
    public function replaceMinAmount($message, $attribute, $rule, $parameters)
    {
        $currencyKey = $parameters[0] ?? 'currency';

        $currency = array_get($this->getData(), $currencyKey, Currency::INR);

        $minAmount = Currency::getMinAmount($currency);

        return str_replace([':min_amount', ':currency'], [amount_format_IN($minAmount),$currency], $message);
    }
}

