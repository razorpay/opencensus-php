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

    const DCC_MARK_UP_PERCENTAGE_KEY = 'dcc_mark_up_percent';

    const REQUEST_VS_TIME_TTL = 60 * 60 * 2; // 2 hours

    const HISTORICAL_EXCHANGE_RATE_TTL = 60 * 60 * 3; // 3 hours

    const TIME_INTERVAL_MINS = 60;

    const DCC_MARK_UP_PERCENTAGE = 5;

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['cache'];
    }

    public function updateRates($currency, $time = null)
    {
        $currency = strtoupper($currency);

        $rates = $this->exchange->latest($currency);

        //Capturing markup percentage in redis as this constant can be changed later for A/B Testing.
        $rates[self::DCC_MARK_UP_PERCENTAGE_KEY] = self::DCC_MARK_UP_PERCENTAGE;

        $key = $this->getRedisKey($currency, $time);

        if ($time === null)
        {
            $this->redis->forever($key, $rates);
        }
        else
        {
            $this->redis->set($key, $rates, self::HISTORICAL_EXCHANGE_RATE_TTL);
        }

        return $rates;
    }

    public function getRates($currency, $time = null)
    {
        $key = $this->getRedisKey($currency, $time);

        $rates = $this->redis->get($key);

        return $rates;
    }

    public function getOrUpdateRates($currency, $time = null)
    {
        $rates = $this->getRates($currency, $time);

        if (empty($rates) === true)
        {
            $rates = $this->updateRates($currency, $time);
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

    protected function getRedisKey($currency, $time = null)
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

    protected function getDCCMarkUpPercentage($rates)
    {
        return isset($rates[self::DCC_MARK_UP_PERCENTAGE_KEY]) === true ? $rates[self::DCC_MARK_UP_PERCENTAGE_KEY] : self::DCC_MARK_UP_PERCENTAGE;
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

    public function getConvertedAmount($baseAmount, $rate, $markUpPercent)
    {
        $convertedAmount = $baseAmount * $rate;

        return (int) ceil($convertedAmount + (($markUpPercent * $convertedAmount) / 100));
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

        $markUpPercent = $this->getDCCMarkUpPercentage($rates);

        $supportedCurrencies = $this->getSupportedCurrenciesDetails();

        foreach (array_keys($supportedCurrencies) as $currency)
        {
            if(isset($rates[$currency]) === true)
            {
                $supportedCurrencies[$currency]['amount'] = $this->getConvertedAmount($baseAmount, $rates[$currency], $markUpPercent);
            }
            else
            {
                unset($supportedCurrencies[$currency]);
            }
        }

        return $supportedCurrencies;
    }

    public function getRequestedCurrencyDetails($baseCurrency, $baseAmount, $requestedCurrency, $currencyRequestId)
    {
        $requestedCurrencyData = [];

        $ratesTimestamp = $this->redis->get($this->getCurrencyRequestDataRedisKey($currencyRequestId));

        if (empty($ratesTimestamp) === false)
        {
            $rates = $this->getRates($baseCurrency, $ratesTimestamp);

            if((empty($rates) === false) and (isset($rates[$requestedCurrency]) === true))
            {
                $markUpPercent = $this->getDCCMarkUpPercentage($rates);

                $requestedCurrencyData['currency'] = $requestedCurrency;

                $requestedCurrencyData['forex_rate'] = (string)$rates[$requestedCurrency];

                $requestedCurrencyData['amount'] = (string)$this->getConvertedAmount($baseAmount,$rates[$requestedCurrency], $markUpPercent);

                $requestedCurrencyData['dcc_mark_up_percent'] = $markUpPercent;
            }
        }

        return $requestedCurrencyData;
    }
}
