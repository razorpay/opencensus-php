<?php

namespace RZP\Models\Currency;

use RZP\Constants\Environment;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;

class Core extends Base\Core
{
    protected $exchange;
    protected $redis;

    const EXCHANGE_RATE_KEY = 'exchange_rates_';

    const MCC_REQUEST_EXPIRE = 3600; // 1 hour in seconds

    public function __construct()
    {
        parent::__construct();

        $this->exchange = $this->app['exchange'];

        $this->redis = $this->app['cache'];
    }

    public function updateRates($currency, &$input = null)
    {
        $currency = strtoupper($currency);

        $rates = $this->exchange->latest($currency);

        if (in_array($this->app['env'], [Environment::TESTING, Environment::TESTING_DOCKER], true) === false)
        {
            $reqInput = [
                $currency => $rates,
            ];

            $this->app['pg_router']->updateCurrencyCache($reqInput, false);
        }

        $latKey = $this->getCurrencyRedisKey($currency);
        $cReqIdOld = $this->redis->get($latKey);
        if(empty($cReqIdOld) === false)
        {
            $pref = $this->redis->getPrefix();
            $oldKey = $pref . $this->getCurrencyReqRedisKey($cReqIdOld);
            $this->redis->connection()->command('expire', [$oldKey, self::MCC_REQUEST_EXPIRE]);
        }

        $cReqIdNew = UniqueIdEntity::generateUniqueId();
        $this->redis->forever($this->getCurrencyReqRedisKey($cReqIdNew), $rates);
        $this->redis->forever($latKey, $cReqIdNew);
        if(isset($input))
        {
            $input['mcc_request_id'] = $cReqIdNew;
        }

        $key = $this->getRedisKey($currency);

        $this->redis->forever($key, $rates);

        return $rates;
    }

    public function getRates($currency, &$input = null)
    {
        $key = $this->getRedisKey($currency);

        $rates = $this->redis->get($key);

        if(isset($input)){
            $input['mcc_request_id'] = $this->redis->get($this->getCurrencyRedisKey($currency));
        }

        return $rates;
    }

    public function getRatesById($currency, &$input)
    {
        $key = $this->getCurrencyReqRedisKey($input['mcc_request_id']);
        $rates = $this->redis->get($key);

        if(empty($rates))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_MCC_INVALID_REQUEST_ID, 'mcc_request_id',
                [
                    'mcc_request_id' => $input['mcc_request_id'],
                    'currency'       => $currency,
                ], 'Invalid mcc_request_id');
        }

        return $rates;
    }

    public function getOrUpdateRates($currency, &$input = null)
    {
        $rates = $this->getRates($currency, $input);

        if (empty($rates) === true)
        {
            $rates = $this->updateRates($currency, $input);
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

    // In case of non domestic payment in reference to merchant, here we calculate value of payment amount in merchant's currency
    // Since base amount is used in the settlement process, hence need to calculate the payment amount in merchant currency itself
    public function getBaseAmount($amount, $currency, $merchantCurrency = "INR", &$input = null)
    {
        if ($currency === $merchantCurrency)
        {
            return $amount;
        }

        if(isset($input) && isset($input['mcc_request_id']))
        {
            $rates = $this->getRatesById($currency, $input);
        }
        else
        {
            $rates = $this->getOrUpdateRates($currency, $input);
        }

        $denominationFactorMerchantCurrency = Currency::DENOMINATION_FACTOR[$merchantCurrency];

        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$currency];

        $denominationFactor = $denominationFactorMerchantCurrency / $denominationFactorInputCurr;

        $baseAmount = $amount * $rates[$merchantCurrency] * $denominationFactor;

        $baseAmount = (int) ceil($baseAmount);

        $input['mcc_applied'] = true;
        $input['mcc_forex_rate'] = $rates[Currency::INR];

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

    protected function getCurrencyRedisKey($currency)
    {
        $key = 'currency_latest_key:' . strtoupper($currency);
        return $key;
    }

    protected function getCurrencyReqRedisKey($reqId)
    {
        $key = 'currency_req:' . $reqId;
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

    public function reverseMccConversionIfApplicable($payment)
    {
        list($rate, $denominationFactor) = $this->getMccReverseRateAndDenominationFactor($payment);
        if(empty($rate))
        {
            return;
        }
    }

    public function reverseMccConversionOnFeeIfApplicable($input, &$fee, &$tax)
    {
        list($rate, $denominationFactor) = $this->getMccReverseRateAndDenominationFactor($input);
        if(empty($rate))
        {
            return;
        }

        $fee = (int) ceil(($fee / $rate) * $denominationFactor);
        $tax = (int) ceil(($tax / $rate) * $denominationFactor);
    }

    protected function getMccReverseRateAndDenominationFactor($input)
    {
        if($input['currency'] === Currency::INR || !isset($input['mcc_request_id']))
        {
            return;
        }

        $rates = $this->getRatesById($input['currency'], $input);
        if(empty($rates))
        {
            return;
        }

        $denominationFactorINR = Currency::DENOMINATION_FACTOR[Currency::INR];
        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$input['currency']];

        $denominationFactor = $denominationFactorInputCurr/$denominationFactorINR;

        return [$rates[Currency::INR], $denominationFactor];
    }

    protected function getMccRedisKey($paymentId, $currency)
    {
        $key = 'currency:mcc_reverse_' . self::EXCHANGE_RATE_KEY . $paymentId . '_' . strtoupper($currency);
        return $key;
    }
}
