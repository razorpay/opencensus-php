<?php

namespace RZP\Models\Currency;

use RZP\Constants\Environment;
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

        if (in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER], true) === false)
        {
            $input = [
                $currency => $rates,
            ];

            $this->app['pg_router']->updateCurrencyCache($input, false);
        }

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

    // `conversionRate` is the factor used to convert an amount in `fromCurrency`
    // to the equivalent amount in `toCurrency`, i.e. we can multiply the amount
    // in `fromCurrency` to this `conversionRate` to get the equivalent amount in
    // `toCurrency`. Example:
    //
    // say 1 USD = 70 INR and 1 SGD = 50 INR, then
    // conversionRate(USD, SGD) = 70/50 (and INR is irrelevant to the example)
    //
    // this means 20 USD is equivalent to 20*(70/50) SGD
    //
    public function getConversionRate($fromCurrency, $toCurrency)
    {
        $fromRates = $this->getOrUpdateRates($fromCurrency);

        $denominationFactorToCurrency = Currency::DENOMINATION_FACTOR[$toCurrency];

        $denominationFactorFromCurrency = Currency::DENOMINATION_FACTOR[$fromCurrency];

        $denominationFactor = $denominationFactorToCurrency / $denominationFactorFromCurrency;

        $conversionRate = $fromRates[$toCurrency] * $denominationFactor;

        return $conversionRate;
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

    public function convertAmount($amount, $fromCurrency, $toCurrency)
    {
        $fromRates = $this->getOrUpdateRates($fromCurrency);

        $denominationFactorToCurrency = Currency::DENOMINATION_FACTOR[$toCurrency];

        $denominationFactorFromCurrency = Currency::DENOMINATION_FACTOR[$fromCurrency];

        $denominationFactor = $denominationFactorToCurrency / $denominationFactorFromCurrency;

        $finalAmount = (int) ceil($amount * $fromRates[$toCurrency] * $denominationFactor);

        return $finalAmount;
    }

    protected function getRedisKey($currency)
    {
        $key = 'currency:' . self::EXCHANGE_RATE_KEY . strtoupper($currency);

        return $key;
    }

    /**
     * Function to get all rzp supported_currency, min supported
     * amount, code, symbol and exponent
     * @return array|null
     */
    public function getSupportedCurrenciesDetails()
    {
        $details = Currency::getDetails();

        return $details;
    }
}
