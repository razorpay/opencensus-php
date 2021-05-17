<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Error\Error;
use RZP\Error\ErrorCode;

class LedgerController extends Controller
{
    public function createAccount()
    {
        $response = $this->app['ledger']->createAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsInBulk()
    {
        $response = $this->app['ledger']->createAccountsInBulk($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function activateAccount()
    {
        $response = $this->app['ledger']->activateAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountDetail()
    {
        $response = $this->app['ledger']->updateAccountDetail($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }
}
