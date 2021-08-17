<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;

class MerchantRiskNotesController extends Controller
{
    public function getAll(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_RISK_NOTE)->getAll($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function create(string $merchantId)
    {
        $input = Request::all();

        $response = $this->service(Entity::MERCHANT_RISK_NOTE)->create($merchantId, $input);

        return ApiResponse::json($response);
    }

    public function delete(string $merchantId, string $id)
    {
        $response = $this->service(Entity::MERCHANT_RISK_NOTE)->delete($merchantId, $id);

        return ApiResponse::json($response);
    }
}
