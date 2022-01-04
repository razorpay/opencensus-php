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

    public function uploadAddressesToKafka()
    {
        $response = $this->service()->uploadAddressesToKafka();
        return ApiResponse::json([$response]);
    }

    public function getFailedAddressFile($batch_id)
    {
        $response = $this->service()->getFailedAddressFile($batch_id);
        return ApiResponse::json($response);
    }

}
