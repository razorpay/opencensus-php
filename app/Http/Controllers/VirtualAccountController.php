<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;

class VirtualAccountController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = VirtualAccount\Service::class;
    }

    public function createVirtualAccount()
    {
        $input = Request::all();

        $response = $this->service()->createVirtualAccount($input);

        return ApiResponse::json($response);
    }

    public function editVirtualAccount(string $id)
    {
        $input = Request::all();

        $response = $this->service()->editVirtualAccount($id, $input);

        return ApiResponse::json($response);
    }

    public function deleteVirtualAccount(string $id)
    {
        $response = $this->service()->deleteVirtualAccount($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccount(string $id)
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
