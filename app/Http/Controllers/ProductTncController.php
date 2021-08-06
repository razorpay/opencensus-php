<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\Product\TncMap\Acceptance;

class ProductTncController extends Controller
{
    protected $service = Acceptance\Service::class;

    public function fetchTncForMerchantProduct(string $merchantId)
    {
        return $this->service()->fetchTnc($merchantId);
    }

    public function acceptTncForMerchantProduct(string $merchantId)
    {
        $input = Request::all();

        return $this->service()->acceptTnc($merchantId, $input);
    }
}
