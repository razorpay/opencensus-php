<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CommissionController extends Controller
{
    public function list()
    {
        $input = Request::all();

        $data  = $this->service()->list($input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $entity = $this->service()->fetch($id);

        return ApiResponse::json($entity);
    }

    public function capture(string $id)
    {
        $entity = $this->service()->capture($id);

        return ApiResponse::json($entity);
    }

    public function captureByPartner(string $partnerId)
    {
        $count = $this->service()->captureByPartner($partnerId);

        $response = ['count' => $count];

        return ApiResponse::json($response);
    }

    public function clearOnHoldForPartner(string $partnerId)
    {
        $input = Request::all();

        $data = $this->service()->clearOnHoldForPartner($partnerId, $input);

        return ApiResponse::json($data);
    }

    public function fetchAnalytics()
    {
        $input = Request::all();

        $response = $this->service()->fetchAnalytics($input);

        return ApiResponse::json($response);
    }

    public function fetchAggregateCommissionDetails(string $partnerId)
    {
        $input = Request::all();

        $data = $this->service()->fetchAggregateCommissionDetails($partnerId, $input);

        return ApiResponse::json($data);
    }
}
