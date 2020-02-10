<?php

namespace RZP\Models\Currency;

use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    protected $exchange;
    protected $redis;

    const EXCHANGE_RATE_KEY = 'exchange_rates_';

    const REQUEST_VS_TIME_KEY = 'req_vs_time_';

    const REQUEST_VS_TIME_TTL = 60 * 60 * 2; // 2 hours

    const HISTORICAL_EXCHANGE_RATE_TTL = 60 * 60 * 3; // 3 hours

    const TIME_INTERVAL_MINS = 60;

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['cache'];
    }

    public function updateRates($currency, $time=null)
    {
        $currency = strtoupper($currency);

        $rates = $this->exchange->latest($currency);

        $key = $this->getRedisKey($currency, $time);

        if ($time === null)
        {
            $this->redis->forever($key, $rates);
        }
        else {
            $this->redis->set($key, $rates, self::HISTORICAL_EXCHANGE_RATE_TTL);
        }

        return $rates;
    }

    public function getRates($currency, $time=null)
    {
        $key = $this->getRedisKey($currency, $time);

        $rates = $this->redis->get($key);

        return $rates;
    }

    public function getOrUpdateRates($currency, $time=null)
    {
        $rates = $this->getRates($currency, $time);

        if (empty($rates) === true)
        {
            $rates = $this->updateRates($currency, $time);
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

    protected function getRedisKey($currency, $time=null)
    {
        $key = 'currency:' . self::EXCHANGE_RATE_KEY . strtoupper($currency);

        if ($time !== null)
        {
            $key = $key . '_' . $time;
        }

        return $key;
    }

    protected function getCurrencyRequestDataRedisKey($currencyRequestId)
    {
        $key = 'currency:' . self::REQUEST_VS_TIME_KEY . $currencyRequestId;

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

    public function getCurrentRoundedTime()
    {
        return floor(time() / (self::TIME_INTERVAL_MINS * 60)) * (self::TIME_INTERVAL_MINS * 60);
    }

    /*
     * - Capture current time, round it off to nearest interval
     * - Store currencyRequestId and round off time in redis
     * - Get or Update rates for the round off time
     * - convert currency to all supported currencies
     */
    public function getConvertedCurrencies($baseCurrency, $baseAmount, $currencyRequestId)
    {
        $roundedTime = $this->getCurrentRoundedTime();

        $this->redis->set($this->getCurrencyRequestDataRedisKey($currencyRequestId), $roundedTime, self::REQUEST_VS_TIME_TTL);

        $rates = $this->getOrUpdateRates($baseCurrency, $roundedTime);

        $supportedCurrencies = $this->getSupportedCurrenciesDetails();

        foreach (array_keys($supportedCurrencies) as $currency)
        {
            if(isset($rates[$currency]))
            {
                $convertedAmount = $baseAmount * $rates[$currency];

                $supportedCurrencies[$currency]['amount'] = (int) ceil($convertedAmount);
            }
            else {
                unset($supportedCurrencies[$currency]);
            }
        }

        return $supportedCurrencies;
    }
}
