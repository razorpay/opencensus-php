<?php


namespace RZP\Http\Controllers;

use ApiResponse;

class CityController extends Controller
{
    public function getCities()
    {
        $citiesFile = resource_path('json/cities.json');

        $citiesJson = json_decode(file_get_contents($citiesFile));

        return ApiResponse::json($citiesJson);;
    }
}
