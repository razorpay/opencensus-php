<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class ConfigController extends Controller
{
    public function createPaymentConfig()
    {
        $input = Request::all();

        $data = $this->service()->create($input);

        return ApiResponse::json($data);
    }

    public function fetchPaymentConfig(string $type)
    {
        $input = Request::all();

        $configs = $this->service()->fetch($type, $input);

        return ApiResponse::json($configs);
    }

    public function fetchPaymentConfigForCheckout()
    {
        $input = Request::all();

        $data = $this->service()->fetchPaymentConfigForCheckout($input);

        return ApiResponse::json($data);
    }

    public function internalFetchConfigById(string $id)
    {
        $configs = $this->service()->internalFetchById($id);

        return ApiResponse::json($configs);
    }

    public function internalFetchConfigs()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function updatePaymentConfig()
    {
        $input = Request::all();

        $data = $this->service()->update($input);

        return ApiResponse::json($data);
    }


    public function deletePaymentConfig()
    {
        $input = Request::all();

        $data = $this->service()->delete($input);

        return ApiResponse::json($data);
    }

    public function updateLateAuthConfigBulk()
    {
        $input = Request::all();

        $data = $this->service()->updateLateAuthConfigBulk($input);

        return ApiResponse::json($data);
    }

    public function createPaymentConfigBulk()
    {
        $input = Request::all();

        $data = $this->service()->createBulk($input);

        return ApiResponse::json($data);
    }

}
