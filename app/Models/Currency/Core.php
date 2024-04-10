<?php

namespace RZP\Models\Currency;

use Monolog\Logger;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Models\Admin\Org;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\Dcs\Configurations\Constants as DcsConfigConst;
use RZP\Trace\TraceCode;


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

    public function updateRatesFromRearch($currency, &$exchangeRates)
    {
        $currency = strtoupper($currency);

        $rates = $exchangeRates;

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
        if ($currency === $merchantCurrency) {
            return $amount;
        }

        if (isset($input) && isset($input['is_lrs_merchant'])) {
            return $this->getLrsRates($amount, $input);
        } else if (isset($input) && isset($input['mcc_request_id'])) {
            $rates = $this->getRatesById($currency, $input);
        } else {
            $rates = $this->getOrUpdateRates($currency, $input);
        }

        $denominationFactorMerchantCurrency = Currency::DENOMINATION_FACTOR[$merchantCurrency];

        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$currency];

        $denominationFactor = $denominationFactorMerchantCurrency / $denominationFactorInputCurr;

        $baseAmount = $amount * $rates[$merchantCurrency] * $denominationFactor;

        $baseAmount = (int)ceil($baseAmount);

        $input['mcc_applied'] = true;
        $input['mcc_forex_rate'] = $rates[Currency::INR];

        return $baseAmount;
    }

    public function getLrsRates($amount, &$input)
    {
        $param = [
            'order_id' => $input['order_id'],
        ];
        $lrs_quote = $this->getLrsQuote($param);
        if (!empty($lrs_quote) && isset($lrs_quote['data'])) {
            $input['lrs_forex_rate'] = $lrs_quote['data']['exchange_rate'];
            if ($input['is_lrs_convert_amount'] === true)
            {
                return (int)$lrs_quote['data']['converted_amount'];
            }
            return (int)ceil($amount * $lrs_quote['data']['exchange_rate']);
        }
        return 0;
    }

    public function getLrsQuote($param)
    {
        return  $this->app['payments-cross-border']->getLRSQuote($param);
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
     * Function to get all rzp supported_currency
     * minus disabled card currencies if any
     * @return array|null
     */
    public function getSupportedCurrencies($orgId = Org\Constants::RZP)
    {
        try {
            $mode = $this->mode ?? Mode::LIVE;
            $dcsConfigService = app('dcs_config_service');
            $disabledCardCurrencies = $dcsConfigService->fetchConfiguration(DcsConfigConst::DisabledCardCurrencies,
                $orgId, [DcsConfigConst::DisabledCardCurrencies], $mode);

            $disabledCurrencies = [];
            foreach ($disabledCardCurrencies[DcsConfigConst::DisabledCardCurrencies] as $currency) {
                $disabledCurrencies[] = $currency;
            }
            $supportedCurrencies = array_diff($this->getAllCurrencies(), $disabledCurrencies);
        } catch (\Exception $e) {
            // trace the error and let supported currencies as default array
            $this->trace->traceException($e, Logger::ERROR, TraceCode::GET_DCS_DISABLED_CARD_CURRENCIES_ERROR);
            $supportedCurrencies = $this->getAllCurrencies();
        }
        return array_values($supportedCurrencies);
    }
    protected function getAllCurrencies () {
        return  [
            Currency::AED,
            Currency::ALL,
            Currency::AMD,
            Currency::ARS,
            Currency::AUD,
            Currency::AWG,
            Currency::BBD,
            Currency::BDT,
            Currency::BHD,
            Currency::BIF,
            Currency::BMD,
            Currency::BND,
            Currency::BOB,
            Currency::BSD,
            Currency::BWP,
            Currency::BZD,
            Currency::CAD,
            Currency::CHF,
            Currency::CNY,
            Currency::COP,
            Currency::CRC,
            Currency::CUP,
            Currency::CZK,
            Currency::DJF,
            Currency::DKK,
            Currency::DOP,
            Currency::DZD,
            Currency::EGP,
            Currency::ETB,
            Currency::EUR,
            Currency::FJD,
            Currency::GBP,
            Currency::GHS,
            Currency::GIP,
            Currency::GMD,
            Currency::GNF,
            Currency::GTQ,
            Currency::GYD,
            Currency::HKD,
            Currency::HNL,
            Currency::HRK,
            Currency::HTG,
            Currency::HUF,
            Currency::IDR,
            Currency::ILS,
            Currency::INR,
            Currency::JMD,
            Currency::JPY,
            Currency::KES,
            Currency::KGS,
            Currency::KHR,
            Currency::KMF,
            Currency::KRW,
            Currency::KWD,
            Currency::KYD,
            Currency::KZT,
            Currency::LAK,
            Currency::LKR,
            Currency::LRD,
            Currency::LSL,
            Currency::MAD,
            Currency::MDL,
            Currency::MKD,
            Currency::MMK,
            Currency::MNT,
            Currency::MOP,
            Currency::MUR,
            Currency::MVR,
            Currency::MWK,
            Currency::MXN,
            Currency::MYR,
            Currency::NAD,
            Currency::NGN,
            Currency::NIO,
            Currency::NOK,
            Currency::NPR,
            Currency::NZD,
            Currency::OMR,
            Currency::PEN,
            Currency::PGK,
            Currency::PHP,
            Currency::PKR,
            Currency::PYG,
            Currency::QAR,
            Currency::RUB,
            Currency::RWF,
            Currency::SAR,
            Currency::SCR,
            Currency::SEK,
            Currency::SGD,
            Currency::SLL,
            Currency::SOS,
            Currency::SSP,
            Currency::SVC,
            Currency::SZL,
            Currency::THB,
            Currency::TRY,
            Currency::TTD,
            Currency::TZS,
            Currency::UGX,
            Currency::USD,
            Currency::UYU,
            Currency::UZS,
            Currency::VUV,
            Currency::XAF,
            Currency::XOF,
            Currency::XPF,
            Currency::YER,
            Currency::ZAR,
        ];
    }
    /**
     * Function to get all rzp supported_currency, min supported
     * amount, code, symbol and exponent
     * @return array|null
     */
    public function getSupportedCurrenciesDetails($isZeroExponentCurrencySupported=false)
    {
        $details = Currency::getDetails();

        if(!$isZeroExponentCurrencySupported)
        {
            foreach (Currency::ZERO_DECIMAL_CURRENCIES as $currency)
            {
                unset($details[$currency]);
            }
        }

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

    public function reverseLRSEducationFee(&$input, &$fee, &$tax)
    {
        $param = [
            'order_id' => $input['order_id'],
        ];
        $lrs_quote = $this->getLrsQuote($param);
        if (empty($lrs_quote))
        {
            return 0;
        }
        $rate = $lrs_quote['data']['exchange_rate'];
        $denominationFactorInr = Currency::DENOMINATION_FACTOR[Currency::INR];
        $denominationFactorInputCurr = Currency::DENOMINATION_FACTOR[$input['currency']];
        $denominationFactor = $denominationFactorInputCurr/$denominationFactorInr;
        $fee = (int) ceil(($fee / $rate) * $denominationFactor);
        $tax = (int) ceil(($tax / $rate) * $denominationFactor);

        // this is required to show the correct amount on checkout screen
        $input['lrs_inr_fee'] = (int)ceil($fee * $lrs_quote['data']['exchange_rate']);
        $input['lrs_inr_tax'] = (int)ceil($tax * $lrs_quote['data']['exchange_rate']);
        $input['lrs_inr_amount'] = (int)$lrs_quote['data']['converted_amount'];
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
