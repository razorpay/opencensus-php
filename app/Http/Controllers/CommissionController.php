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

    public function fetchAnalytics()
    {
        $input = Request::all();

        $response = $this->service()->fetchAnalytics($input);

        return ApiResponse::json($response);
    }
}
