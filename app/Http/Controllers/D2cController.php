<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;

class D2cController extends Controller
{
    public function getOrCreate()
    {
        $input = Request::all();

        $entity = $this->service(Entity::D2C_BUREAU_DETAIL)->getOrCreate($input);

        return ApiResponse::json($entity);
    }

    public function createReportForLos()
    {
        $input = Request::all();

        $entity = $this->service(Entity::D2C_BUREAU_DETAIL)->createReportForLos($input);

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
        $response = $this->service(Entity::D2C_BUREAU_REPORT)->fetchReport($id);

        return ApiResponse::json($response);
    }

    public function fetchReportForLos()
    {
        $input = Request::all();

        $response = $this->service(Entity::D2C_BUREAU_REPORT)->fetchReportForLos($input);

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

    public function deleteReport(string $id)
    {
        $response = $this->service(Entity::D2C_BUREAU_REPORT)->deleteReport($id);

        return ApiResponse::json($response);
    }
}
