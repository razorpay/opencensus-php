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

    public function create()
    {
        $input = Request::all();

        $response = $this->service()->create($input);

        return ApiResponse::json($response);
    }

    public function edit(string $id)
    {
        $input = Request::all();

        $response = $this->service()->edit($id, $input);

        return ApiResponse::json($response);
    }

    public function delete(string $id)
    {
        $response = $this->service()->delete($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccount(string $id)
    {
        $response = $this->service()->getSingle($id);

        return ApiResponse::json($response);
    }

    public function getVirtualAccounts()
    {
        $input = Request::all();

        $response = $this->service()->getMultiple($input);

        return ApiResponse::json($response);
    }
}
