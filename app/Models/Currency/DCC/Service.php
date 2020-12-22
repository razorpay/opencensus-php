<?php

namespace RZP\Models\Currency\DCC;

use RZP\Models\Base;
use RZP\Models\Currency;

class Service extends Base\Service
{
    const REQUEST_VS_TIME_KEY = 'req_vs_time_';

    const DCC_MARK_UP_PERCENTAGE_KEY = 'dcc_mark_up_percent';

    const HISTORICAL_EXCHANGE_RATE_TTL = 60 * 2; // 2 hours

    const REQUEST_VS_TIME_TTL = 60 * 1; // 1 hours

    const TIME_INTERVAL_MINS = 60;

    const DCC_MARK_UP_PERCENTAGE = 5;

    public function __construct()
    {
        parent::__construct();

        $this->redis = $this->app['cache'];

        $this->core = (new Currency\Core);
    }

    private function updateRates($currency, $time)
    {
        $currency = strtoupper($currency);

        $rates = $this->core->getOrUpdateRates($currency);

        //Capturing markup percentage in redis as this constant can be changed later for A/B Testing.
        $rates[self::DCC_MARK_UP_PERCENTAGE_KEY] = self::DCC_MARK_UP_PERCENTAGE;

        $key = $this->getRedisKey($currency, $time);

        $this->redis->set($key, $rates, self::HISTORICAL_EXCHANGE_RATE_TTL);

        return $rates;
    }

    private function getRates($currency, $time)
    {
        $key = $this->getRedisKey($currency, $time);

        $rates = $this->redis->get($key);

        return $rates;
    }

    private function getOrUpdateRates($currency, $time)
    {
        $rates = $this->getRates($currency, $time);

        if (empty($rates) === true)
        {
            $rates = $this->updateRates($currency, $time);
        }

        return $rates;
    }

    private function getRedisKey($currency, $time)
    {
        $key = 'currency:' . $this->core::EXCHANGE_RATE_KEY . strtoupper($currency) . '_' . $time;

        return $key;
    }

    private function getCurrencyRequestDataRedisKey($currencyRequestId)
    {
        $key = 'currency:' . self::REQUEST_VS_TIME_KEY . $currencyRequestId;

        return $key;
    }

    private function getDCCMarkUpPercentage($rates, $requestedCurrency)
    {
        if ($requestedCurrency === Currency\Currency::INR)
        {
            return 0;
        }

        return isset($rates[self::DCC_MARK_UP_PERCENTAGE_KEY]) === true ?
            $rates[self::DCC_MARK_UP_PERCENTAGE_KEY] : self::DCC_MARK_UP_PERCENTAGE;
    }

    // Round current time to nearest hour.
    private function getCurrentRoundedTime()
    {
        return floor(time() / (self::TIME_INTERVAL_MINS * 60)) * (self::TIME_INTERVAL_MINS * 60);
    }

    public function getConvertedAmount($baseAmount, $rate, $markUpPercent)
    {
        $convertedAmount = $baseAmount * $rate;

        return (int) ceil($convertedAmount + (($markUpPercent * $convertedAmount) / 100));
    }

    public function getCurrencyConversionFee($baseAmount, $rate, $markUpPercent)
    {
        $fee = (($baseAmount * $rate * $markUpPercent) / 100);

        return (int) ceil($fee);
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

        $this->redis->set($this->getCurrencyRequestDataRedisKey($currencyRequestId),
            $roundedTime, self::REQUEST_VS_TIME_TTL);

        $rates = $this->getOrUpdateRates($baseCurrency, $roundedTime);

        $supportedCurrencies = $this->core->getSupportedCurrenciesDetails();

        foreach (array_keys($supportedCurrencies) as $currency)
        {
            if(isset($rates[$currency]) === true)
            {
                $markUpPercent = $this->getDCCMarkUpPercentage($rates, $currency);

                $forexRateConverted =  number_format($rates[$currency], 6, '.', '');

                $supportedCurrencies[$currency]['amount'] = $this->getConvertedAmount($baseAmount, $forexRateConverted, $markUpPercent);
                $supportedCurrencies[$currency]['forex_rate'] = (float) $forexRateConverted;
                $supportedCurrencies[$currency]['fee'] =
                    $this->getCurrencyConversionFee($baseAmount, $forexRateConverted, $markUpPercent);
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
                $forexRate = number_format($rates[$requestedCurrency], 6, '.','');

                $markUpPercent = $this->getDCCMarkUpPercentage($rates, $requestedCurrency);

                $requestedCurrencyData['currency'] = $requestedCurrency;

                $requestedCurrencyData['forex_rate'] = $forexRate;

                $requestedCurrencyData['amount'] = (string)$this->getConvertedAmount($baseAmount,$forexRate, $markUpPercent);

                $requestedCurrencyData['dcc_mark_up_percent'] = $markUpPercent;
            }
        }

        return $requestedCurrencyData;
    }
}
