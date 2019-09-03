<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;

class BankingAccountStatementController extends Controller
{
    public function fetchStatementForAccount()
    {
        $input = Request::all();

        $response = $this->service()->fetchStatementForAccount($input);

        return ApiResponse::json($response);
    }

    /***
     * Expected Input:
     * 'format' : pdf/xlsx/csv
     * 'send_email'  : true/false
     * 'account_number' : '<account_number>',
     * 'channel'        : '<channel>',
     * from_date        : '2019-01-01'
     * to_date          : '2019-04-01'
     * @return mixed
     */
    public function generate()
    {
        $input = Request::all();

        $response = $this->service()->generateAccountStatement($input);

        return $response;

        return ApiResponse::json($response);
    }
}
