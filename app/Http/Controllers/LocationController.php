<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Location;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Models\Pincode\ZipcodeDirectory\Service;
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
        try
        {
            return (new LocationService($this->app))->getAddressSuggestions($this->app['request']->query->all());
        }
        catch (\Throwable $ex)
        {
            if (($ex instanceof BaseException) === true)
            {
                switch ($ex->getError()->getInternalErrorCode())
                {
                    case ErrorCode::SERVER_ERROR:
                        $data = $ex->getError()->toPublicArray(true);
                        return ApiResponse::json($data, 503);
                }
            }
            throw $ex;
        }
    }

    public function add(): array
    {
        $input = Request::all();

        return  (new Service())->add($input);
    }

    public function remove(): array
    {
        $input = Request::all();

        return  (new Service())->remove($input);
    }
}
