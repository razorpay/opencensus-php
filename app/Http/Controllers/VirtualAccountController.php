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

        $this->virtualAccountService = new VirtualAccount\Service;
    }

    public function createVirtualAccount()
    {
        $input = Request::all();

        $response = $this->virtualAccountService->createVirtualAccount($input);

        return ApiResponse::json($response);
    }

    public function closeVirtualAccount($id)
    {
        $response = $this->virtualAccountService->closeVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccount($id)
    {
        $response = $this->virtualAccountService->getVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccounts()
    {
        $input = Request::all();

        $response = $this->virtualAccountService->getVirtualAccounts($input);

        return ApiResponse::json($response);
    }
}
