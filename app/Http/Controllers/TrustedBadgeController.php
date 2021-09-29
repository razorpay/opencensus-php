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
        $response = $this->service()->eligibilityCron();

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

    public function blacklistMerchants()
    {
        $input = Request::all();

        $response = $this->service()->blacklistMerchants($input);

        return ApiResponse::json($response);
    }

    public function redirectUrl()
    {
        $input = Request::all();

        $data = $this->service()->redirectUrl($input);

        return Redirect::away($data['url']);
    }
}
