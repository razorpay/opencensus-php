<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class PincodeSearchController extends Controller
{
    public function get(int $id)
    {
        $data = $this->app['pincodesearcher.client']->fetchCityAndStateFromPincode($id);

        return ApiResponse::json($data);
    }
}
