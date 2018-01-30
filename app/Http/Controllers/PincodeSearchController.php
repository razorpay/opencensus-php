<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class PincodeSearchController extends Controller
{
    public function get(int $id)
    {
        $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode($id);

        return ApiResponse::json($data);
    }
}
