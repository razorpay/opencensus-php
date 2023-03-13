<?php


namespace RZP\Http\Controllers;

use Illuminate\Http\Response;
use View;
use Request;
use ApiResponse;
use Redirect;

class TrustedBadgeController extends Controller
{
    public function eligibilityCron()
    {
        $input = Request::all();

        $response = $this->service()->eligibilityCron($input);

        return ApiResponse::json($response);
    }

    public function fetch()
    {
        $response = $this->service()->fetch();

        return ApiResponse::json($response);
    }

    public function updateMerchantStatus()
    {
        $input = Request::all();

        $this->service()->updateMerchantStatus($input);

        return ApiResponse::json([], Response::HTTP_NO_CONTENT);
    }

    public function updateTrustedBadgeStatus()
    {
        $input = Request::all();

        $response = $this->service()->updateTrustedBadgeStatus($input);

        return ApiResponse::json($response);
    }

    public function redirectUrl()
    {
        $input = Request::all();

        $data = $this->service()->redirectUrl($input);

        return Redirect::away($data['url']);
    }

    public function fetchExperimentList()
    {
        $data = $this->service()->fetchExperimentList();

        return ApiResponse::json($data);
    }

    public function putExperimentList()
    {
        $input = Request::all();

        $data = $this->service()->putExperimentList($input);

        return ApiResponse::json($data);
    }
}
