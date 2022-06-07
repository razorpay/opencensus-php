<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class RolesController extends Controller
{
    public function listRolesForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->listRolesForMerchant($input);

        return ApiResponse::json($response);
    }
}
