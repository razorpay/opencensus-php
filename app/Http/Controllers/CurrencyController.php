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
        $data = (new Currency\Core)->getSupportedCurrenciesDetails();

        return ApiResponse::json($data);
    }
}
