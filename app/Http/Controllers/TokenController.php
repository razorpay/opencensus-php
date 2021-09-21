<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class TokenController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = $this->service()->createNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetch()
    {
        return ApiResponse::json(['action' => 'fetch']);
    }

    public function fetchCryptoGram()
    {
        return ApiResponse::json(['action' => 'fetchCryptoGram']);
    }

    public function delete()
    {
        return ApiResponse::json(['action' => 'delete']);
    }
}
