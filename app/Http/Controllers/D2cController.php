<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;

class D2cController extends Controller
{
    public function getOrCreate()
    {
        $entity = $this->service(Entity::D2C_BUREAU_DETAIL)->getOrCreate();

        return ApiResponse::json($entity);
    }

    public function patchDetails(string $id)
    {
        $input = Request::all();

        $response = $this->service(Entity::D2C_BUREAU_DETAIL)->updateDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function getReportWithOtp(string $id)
    {
        $input = Request::all();

        $response = $this->service(Entity::D2C_BUREAU_DETAIL)->getReportWithOtp($id, $input);

        return ApiResponse::json($response);
    }

    public function patchReport(string $id)
    {
        $input = Request::all();

        $response = $this->service(Entity::D2C_BUREAU_REPORT)->update($id, $input);

        return ApiResponse::json($response);
    }
}
