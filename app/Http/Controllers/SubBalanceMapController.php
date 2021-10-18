<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

class SubBalanceMapController extends Controller
{
    public function createSubBalance()
    {
        $input = Request::all();

        $response = $this->service()->createSubBalanceAndMap($input);

        return ApiResponse::json($response);
    }
}
