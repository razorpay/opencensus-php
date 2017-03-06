<?php

namespace RZP\Models\Currency;

use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    protected $exchange;
    protected $redis;

    const EXCHANGE_RATE_KEY = 'exchange_rates_';

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['cache'];
    }

    public function updateRates($currency)
    {
        $currency = strtoupper($currency);

        $rates = $this->exchange->latest($currency);

        $key = $this->getRedisKey($currency);

        $this->redis->forever($key, $rates);

        return $rates;
    }

    public function getRates($currency)
    {
        $key = $this->getRedisKey($currency);

        $rates = $this->redis->get($key);

        return $rates;
    }

    public function getOrUpdateRates($currency)
    {
        $rates = $this->getRates($currency);

        if (empty($rates) === true)
        {
            $rates = $this->updateRates($currency);
        }

        return $rates;
    }

    public function getBaseAmount($amount, $currency)
    {
        if ($currency === Currency::INR)
        {
            return $amount;
        }

        $rates = $this->getOrUpdateRates($currency);

        $denominationFactorINR = Currency::DENOMINATION_FACTOR[Currency::INR];

        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$currency];

        $denominationFactor = $denominationFactorINR / $denominationFactorInputCurr;

        $baseAmount = $amount * $rates[Currency::INR] * $denominationFactor;

        $baseAmount = (int) ceil($baseAmount);

        return $baseAmount;
    }

    protected function getRedisKey($currency)
    {
        $key = self::EXCHANGE_RATE_KEY . strtoupper($currency);

        return $key;
    }
}
