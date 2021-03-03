<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RiskController extends Controller
{
    public function get(string $id)
    {
        $entity = $this->service()->fetch($id);

        return ApiResponse::json($entity);
    }

    public function list()
    {
        $input = Request::all();

        $entities = $this->service()->fetchMultiple($input);

        return ApiResponse::json($entities);
    }

    public function getEntityDetails(string $id)
    {
        $details = $this->service()->getGrievanceEntityDetails($id);

        return ApiResponse::json($details);
    }

    public function postCustomerGrievance()
    {
        $input = Request::all();

        $response = $this->service()->postCustomerGrievance($input);

        return ApiResponse::json($response);
    }

    public function allowCors()
    {
        return ApiResponse::json([]);
    }
}
