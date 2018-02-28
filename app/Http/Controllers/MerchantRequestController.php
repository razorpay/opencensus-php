<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Constants\Entity as E;

class MerchantRequestController extends Controller
{
    public function getAll()
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getAll($this->input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->get($id);

        return ApiResponse::json($data);
    }

    public function getForFeatureTypeAndName(string $type, string $featureName)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getForFeatureTypeAndName($type, $featureName);

        return ApiResponse::json($data);
    }

    public function getMerchantRequestStatusLog(string $id)
    {
        $data = $this->service(E::MERCHANT_REQUEST)->getMerchantRequestStatusLog($id, $this->input);

        return ApiResponse::json($data);
    }

    public function create()
    {
        $response = $this->service(E::MERCHANT_REQUEST)->create($this->input);

        return ApiResponse::json($response);
    }

    public function update(string $id)
    {
        $response = $this->service(E::MERCHANT_REQUEST)->update($id, $this->input);

        return ApiResponse::json($response);
    }
}
