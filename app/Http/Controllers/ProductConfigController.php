<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Models\Merchant\Product;

class ProductConfigController extends Controller
{
    protected $service = Product\Service::class;

    public function fetchConfigForMerchant(string $merchantId, string $merchantProductConfigId)
    {
        return $this->service()->getConfig($merchantId, $merchantProductConfigId);
    }

    public function updateConfigForMerchant(string $merchantId, string $merchantProductConfigId)
    {
        $input = Request::all();

        return $this->service()->updateConfig($merchantId, $merchantProductConfigId, $input);
    }

    public function createConfigForMerchant(string $merchantId)
    {
        $input = Request::all();

        return $this->service()->createConfig($merchantId, $input);
    }
}
