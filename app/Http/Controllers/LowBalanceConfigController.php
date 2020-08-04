<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Http\Controllers\Traits\HasCrudMethods;
use RZP\Models\Merchant\Balance\LowBalanceConfig;

class LowBalanceConfigController extends Controller
{
    use HasCrudMethods;

    protected $service = LowBalanceConfig\Service::class;

    public function disableConfig(string $id)
    {
        $response = $this->service()->disableConfig($id);

        return ApiResponse::json($response);
    }

    public function enableConfig(string $id)
    {
        $response = $this->service()->enableConfig($id);

        return ApiResponse::json($response);
    }
}
