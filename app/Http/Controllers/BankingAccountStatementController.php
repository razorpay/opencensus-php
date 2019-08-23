<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use View;

class BankingAccountStatementController extends Controller
{
    public function fetchStatementForAccount()
    {
        $input = Request::all();

        $response = $this->service()->fetchStatementForAccount($input);

        return ApiResponse::json($response);
    }

    public function pdf()
    {
        // Expected account_number, format, channel
        $input = Request::all();
        $response = $this->service()->generateAccountStatement($input);
        return $response;
        return ApiResponse::json($response);

    }
}
