<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class RawAddressController extends Controller
{
    use Traits\HasCrudMethods;

    public function postCreateBatch()
    {
        $input = Request::all();

        $response = $this->service()->createBatch($input);

        return ApiResponse::json($response);
    }

    public function createAddressBulk()
    {

        $input = Request::all();

        $this->service()->createAddressBulk($input);

        return ApiResponse::json([]);
    }
}
