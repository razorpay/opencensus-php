<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\FundLoadingDowntime\Service;

class FundLoadingDowntimeController extends Controller
{
    public function __construct()
    {
        $this->service = new Service();
    }

    public function createFundLoadingDowntime()
    {
        $input = Request::all();

        $data = $this->service()->createFundLoadingDowntime($input);

        return ApiResponse::json($data);
    }

    public function updateFundLoadingDowntime($id)
    {
        $input = Request::all();

        $data  = $this->service()->updateFundLoadingDowntime($id, $input);

        return ApiResponse::json($data);
    }

    public function listFundLoadingDowntimes()
    {
        $input = Request::all();

        $data  = $this->service()->listFundLoadingDowntimes($input);

        return ApiResponse::json($data);
    }

    public function listActiveFundLoadingDowntimes()
    {
        $input = Request::all();

        $data = $this->service()->listActiveFundLoadingDowntimes($input);

        return ApiResponse::json($data);
    }

    public function fetchFundLoadingDowntime($id)
    {
        $data = $this->service()->fetchFundLoadingDowntime($id);

        return ApiResponse::json($data);
    }

    public function deleteFundLoadingDowntime($id)
    {
        $data = $this->service()->deleteFundLoadingDowntime($id);

        return ApiResponse::json($data);
    }

    public function notificationFlow($flowType)
    {
        $input = Request::all();

        $response = $this->service()->notificationFlow($flowType, $input);

        return ApiResponse::json($response);
    }
}
