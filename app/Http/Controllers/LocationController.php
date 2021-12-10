<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Location;

class LocationController extends Controller
{
    protected $service = Location\Service::class;

    public function getCountryDetails()
    {
        $data = $this->service()->getCountryDetails();

        return ApiResponse::json($data);
    }

    public function getstateDetailsFromCountryCode(string $id)
    {
        $data = $this->service()->getstateDetailsFromCountryCode($id);

        if (empty($data) === true) 
        {
            return ApiResponse::json(['Status' => 'Country code sent is invalid'], 400);
        }

        return ApiResponse::json($data);
    }
}
