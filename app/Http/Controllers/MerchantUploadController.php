<?php


namespace RZP\Http\Controllers;


use Request;
use ApiResponse;

class MerchantUploadController extends Controller
{
    public function uploadMerchant()
    {
        $input = Request::all();

        $response = $this->service()->uploadMerchant($input);

        return ApiResponse::json($response);
    }
}
