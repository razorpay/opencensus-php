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

    public function deactivateAccount()
    {
        $response = $this->app['ledger']->deactivateAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function archiveAccount()
    {
        $response = $this->app['ledger']->archiveAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccount()
    {
        $response = $this->app['ledger']->updateAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountDetail()
    {
        $response = $this->app['ledger']->updateAccountDetail($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function requestGovernor()
    {
        $response = $this->app['ledger']->requestGovernor($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetch()
    {
        $response = $this->app['ledger']->fetch($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchMultiple()
    {
        $response = $this->app['ledger']->fetchMultiple($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchFilter()
    {
        $response = $this->app['ledger']->fetchFilter($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAdminFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchAdminFormFieldOptions($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }
}
