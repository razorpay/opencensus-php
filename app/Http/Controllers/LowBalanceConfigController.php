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

    public function alert()
    {
        $response = $this->service()->alert();

        return ApiResponse::json($response);
    }

    public function adminCreate()
    {
        $input = Request::all();

        $entity = $this->service()->adminCreate($input);

        return ApiResponse::json($entity);
    }

    public function adminUpdate(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->adminUpdate($id, $input);

        return ApiResponse::json($entity);
    }

    public function adminEnableConfig(string $id)
    {
        $entity = $this->service()->adminEnableConfig($id);

        return ApiResponse::json($entity);
    }

    public function adminDisableConfig(string $id)
    {
        $entity = $this->service()->adminDisableConfig($id);

        return ApiResponse::json($entity);
    }

    public function adminDelete(string $id)
    {
        $entity = $this->service()->adminDelete($id);

        return ApiResponse::json($entity);
    }

    public function adminList(string $mid)
    {
        $input = Request::all();

        $entity = $this->service()->adminFetchMultiple($input, $mid);

        return ApiResponse::json($entity);
    }
}
