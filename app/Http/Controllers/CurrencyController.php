<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Currency;

class CurrencyController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->currency = new Currency\Core;
    }

    public function postCurrencyRates($currency)
    {
        $data = $this->currency->updateRates($currency);

        return ApiResponse::json($data);
    }

    public function getCurrencyRates($currency)
    {
        $data = $this->currency->getRates($currency);

        return ApiResponse::json($data);
    }
}
