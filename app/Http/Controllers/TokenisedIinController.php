<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Card\TokenisedIIN\Entity;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class TokenisedIinController extends Controller
{
    public function createIin()
    {
        $input = Request::all();

        $data = $this->service()->createIin($input);

        return ApiResponse::json($data);
    }

    public function updateIin($iin)
    {
        $input = Request::all();

        $data = $this->service()->updateIin($iin, $input);

        return ApiResponse::json($data);
    }

    public function fetchIin($iin)
    {
        $input = Request::all();

        $response = $this->service()->fetchIin($iin);

        return ApiResponse::json($response);
    }

    public function fetchbyTokenIin($iin)
    {
        $input = Request::all();

        $data = $this->service()->fetchbyTokenIin($iin);

        return ApiResponse::json($data);
    }

    public function addIinBulk()
    {
        $input = Request::all();

        $data = $this->service()->addIinBulk($input);

        return ApiResponse::json($data);
    }

}
