<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Currency;

class CurrencyController extends Controller
{
    public function postCurrencyRates($currency)
    {
        $this->currency = new Currency\Core;

        $data = $this->currency->updateRates($currency);

        return ApiResponse::json($data);
    }

    public function getCurrencyRates($currency)
    {
        $this->currency = new Currency\Core;

        $data = $this->currency->getRates($currency);

        return ApiResponse::json($data);
    }
}
