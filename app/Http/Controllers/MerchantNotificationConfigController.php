<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Http\Controllers\Traits\HasCrudMethods;
use RZP\Models\Merchant\MerchantNotificationConfig;

class MerchantNotificationConfigController extends Controller
{
    use HasCrudMethods;

    public function getAsAdmin(string $merchantId, string $id)
    {
        $entity = $this->service()->fetchAsAdmin($id, $merchantId);

        return ApiResponse::json($entity);
    }

    public function listAsAdmin(string $merchantId)
    {
        $input = Request::all();

        $entities = $this->service()->fetchMultipleAsAdmin($input, $merchantId);

        return ApiResponse::json($entities);
    }

    public function createAsAdmin(string $merchantId)
    {
        $input = Request::all();

        $entity = $this->service()->createAsAdmin($input, $merchantId);

        return ApiResponse::json($entity);
    }

    public function updateAsAdmin(string $merchantId, string $id)
    {
        $input = Request::all();

        $entity = $this->service()->updateAsAdmin($id, $merchantId, $input);

        return ApiResponse::json($entity);
    }

    public function deleteAsAdmin(string $merchantId, string $id)
    {
        $response = $this->service()->deleteAsAdmin($id, $merchantId);

        return ApiResponse::json($response);
    }

    public function disableConfig(string $id)
    {
        $response = $this->service()->disableConfig($id);

        return ApiResponse::json($response);
    }

    public function disableConfigAsAdmin(string $merchantId, string $id)
    {
        $response = $this->service()->disableConfigAsAdmin($id, $merchantId);

        return ApiResponse::json($response);
    }

    public function enableConfig(string $id)
    {
        $response = $this->service()->enableConfig($id);

        return ApiResponse::json($response);
    }

    public function enableConfigAsAdmin(string $merchantId, string $id)
    {
        $response = $this->service()->enableConfigAsAdmin($id, $merchantId);

        return ApiResponse::json($response);
    }
}
