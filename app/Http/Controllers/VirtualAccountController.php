<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;
use ApiResponse;
use Request;

class VirtualAccountController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = \RZP\Models\VirtualAccount\Service::class;
    }

    public function createVirtualAccount()
    {
        $input = Request::all();

        $response = $this->service()->createVirtualAccount($input);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccount($id)
    {
        $response = $this->service()->closeVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccount($id)
    {
        $response = $this->service()->getVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccounts()
    {
        $input = Request::all();

        $response = $this->service()->getVirtualAccounts($input);

        return ApiResponse::json($response);
    }
}
