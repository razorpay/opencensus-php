<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\Ecollect;
use ApiResponse;
use Request;

class EcollectController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = new Ecollect\Service;
    }

    public function validateEcollect()
    {
        $input = Request::all();

        $response = $this->service->validate($input);

        return ApiResponse::json($response);
    }

    public function payEcollect()
    {
        $input = Request::all();

        $response = $this->service->pay($input);

        return ApiResponse::json($response);
    }

    public function createCustomerBankAccount()
    {

    }

    public function createStandingBankAccount()
    {

    }
}
