<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PincodeSearchController extends Controller
{
    public function get($id)
    {
        $url = Request::path();

        $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode($id, strpos($url, '1cc') !== false);

        return ApiResponse::json($data);
    }
}
