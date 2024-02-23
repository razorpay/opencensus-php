<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Upi\Turbo\Service;

class UpiTurboController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = new Service();
    }

    public function fetchErrorMappings()
    {
        $errorMappings = $this->service->fetchErrorMappings();

        return ApiResponse::json($errorMappings);
    }

    public function setErrorMappingsAdmin()
    {
        $errorMappings = $this->service->setErrorMappingsAdmin();

        return ApiResponse::json($errorMappings);
    }

    public function recordCustomerConsent()
    {
        $input = Request::all();

        $response = $this->service->recordCustomerConsent($input);

        return ApiResponse::json($response, 201);
    }
}
