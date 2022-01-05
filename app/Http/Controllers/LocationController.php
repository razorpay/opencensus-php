<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Location;
use RZP\Services\LocationService;

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

    public function getStatesByCountry(string $countryCode)
    {
        return (new LocationService($this->app))->getStatesByCountry($countryCode);
    }

    public function getAddressSuggestions()
    {
        $uri = \Request::fullUrl();
        $query = explode( "?", $uri, 2)[1];
        return (new LocationService($this->app))->getAddressSuggestions($query);
    }
}
