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

    public function getCurrencyRates($currency)
    {
        $data = (new Currency\Core)->getRates($currency);

        return ApiResponse::json($data);
    }
}
