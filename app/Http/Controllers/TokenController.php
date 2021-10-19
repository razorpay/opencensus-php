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
        $input = Request::all();

        $data = $this->service()->fetchNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetchCryptoGram()
    {
        $input = Request::all();

        $data = $this->service()->fetchCryptoGram($input);

        return ApiResponse::json($data);
    }

    public function delete()
    {
        $input = Request::all();

        $data = $this->service()->deleteNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function pauseNotSupportedCardTokens()
    {
        $input = Request::all();

        $data = $this->service()->pauseNotSupportedCardTokens($input);

        return ApiResponse::json($data);
    }
}
