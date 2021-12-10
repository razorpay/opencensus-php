<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PincodeSearchController extends Controller
{
    public function get($id)
    {
        $url = Request::path();

        $useStateName = $useGstCodes = strpos($url, '1cc') !== false;

        $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode(
            $id,
            $useStateName,
            $useGstCodes
          );

        return ApiResponse::json($data);
    }

    public function getByCountry($country, $pincode)
    {
        $data = $this->app['pincodesearch']->fetchCityAndStateFromPincode($pincode, false, false, $country);
        return ApiResponse::json($data);
    }
}
