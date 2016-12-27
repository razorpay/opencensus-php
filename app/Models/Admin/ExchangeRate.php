<?php

namespace RZP\Models\Admin;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment\Currency;

class ExchangeRate extends Base\Core
{
    protected $exchange;

    const EXCHANGE_RATE_KEY = 'exchange_rates_';

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['cache'];
    }

    public function updateRates($currency)
    {
        $rates = $this->exchange->latest($currency);

        $key = self::EXCHANGE_RATE_KEY . $currency;

        $this->redis->forever($key, $rates);

        return ['success' => true];
    }

    public function getBaseAmount($amount, $currency)
    {
        $rates = $this->getRates($currency);

        $denominationFactorINR = Currency::DENOMINATION_FACTOR[Currency::INR];

        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$currency];

        $denominationFactor = $denominationFactorINR / $denominationFactorInputCurr;

        $baseAmount = $amount * $rates[Currency::INR] * $denominationFactor;

        $baseAmount = (int) ceil($baseAmount);

        return $baseAmount;
    }

    protected function getRates($currency)
    {
        $key = self::EXCHANGE_RATE_KEY . $currency;

        $rates = $this->redis->get($key);

        return $rates;
    }
}
