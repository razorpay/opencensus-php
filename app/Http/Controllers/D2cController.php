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

    public function fetchD2cCSVReport()
    {
        $response = $this->service(Entity::D2C_BUREAU_REPORT)->getCsvReport();

        return ApiResponse::json($response);
    }

    public function patchDetails(string $id)
    {
        $input = Request::all();

        $response = $this->service(Entity::D2C_BUREAU_DETAIL)->updateDetails($id, $input);

        return ApiResponse::json($response);
    }

    public function fetchReport(string $id)
    {
        $response = $this->service(Entity::D2C_BUREAU_DETAIL)->fetchReport($id);

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

    public function getReportDownloadUrl(string $id)
    {
        $response = $this->service(Entity::D2C_BUREAU_REPORT)->getDownloadUrl($id);

        return ApiResponse::json($response);
    }
}
