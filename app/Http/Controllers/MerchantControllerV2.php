<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class MerchantControllerV2 extends Controller
{
    protected $service = Merchant\Service::class;

    public function getAccountBalances()
    {
        $input = Request::all();

        $data = $this->service()->fetchAccountBalancesV2($input);

        return ApiResponse::json($data);
    }
}
