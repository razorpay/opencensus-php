<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Currency;

class CurrencyController extends Controller
{
    public function postCurrencyRates($currency)
    {
        $data = (new Currency\Core)->updateRates($currency);

        return ApiResponse::json($data);
    }

    public function postCurrencyRatesMultiple()
    {
        $data = [];

        $currencies = Currency\Currency::SUPPORTED_CURRENCIES;

        foreach ($currencies as $currency)
        {
            $rates = (new Currency\Core)->updateRates($currency);

            $data[$currency] = $rates[Currency\Currency::INR];
        }

        return ApiResponse::json($data);
    }

    public function getCurrencyRates($currency)
    {
        $data = (new Currency\Core)->getRates($currency);

        return ApiResponse::json($data);
    }

    /**
     * Function to return all rzp supported_currency and their min_amount,
     * code, symbol and exponent
     *
     * @return mixed
     */
    public function getAllCurrency()
    {
        /*
         * Added isThreeDecimalCurrencySupported to true, to return three decimal currencies
         * as well in response of supported currency in "currency_fetch_all_proxy" endpoint.
         * We have a feature flag 'SHAADI_COM_NEW_CURRENCY' restrictions for merchants to return data in DCC flow in @getSupportedCurrenciesDetails function.
         * This is required as we will be supporting n exponent currencies at frontend in merchant dashboard.
         * Note: this flag will be removed once we remove the feature flag dependency.
         */

        $isThreeDecimalCurrencySupported = true;
        $data = (new Currency\Core)->getSupportedCurrenciesDetails($isThreeDecimalCurrencySupported);

        return ApiResponse::json($data);
    }
}
