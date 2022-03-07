<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Error\Error;
use RZP\Error\ErrorCode;

class LedgerController extends Controller
{
    public function createAccount()
    {
        $response = $this->app['ledger']->createAccount($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsOnEvent()
    {
        $response = $this->app['ledger']->createAccountsOnEvent($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsInBulk()
    {
        $response = $this->app['ledger']->createAccountsInBulk($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function activateAccount()
    {
        $response = $this->app['ledger']->activateAccount($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deactivateAccount()
    {
        $response = $this->app['ledger']->deactivateAccount($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function archiveAccount()
    {
        $response = $this->app['ledger']->archiveAccount($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccount()
    {
        $response = $this->app['ledger']->updateAccount($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountDetail()
    {
        $response = $this->app['ledger']->updateAccountDetail($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournal()
    {
        $response = $this->app['ledger']->createJournal($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchByTransactor()
    {
        $response = $this->app['ledger']->fetchByTransactor($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createLedgerConfig()
    {
        $response = $this->app['ledger']->createLedgerConfig($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function UpdateLedgerConfig()
    {
        $response = $this->app['ledger']->updateLedgerConfig($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function DeleteLedgerConfig()
    {
        $response = $this->app['ledger']->deleteLedgerConfig($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function requestGovernor()
    {
        $response = $this->app['ledger']->requestGovernor($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetch()
    {
        $response = $this->app['ledger']->fetch($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchMultiple()
    {
        $response = $this->app['ledger']->fetchMultiple($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchFilter()
    {
        $response = $this->app['ledger']->fetchFilter($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchAccountFormFieldOptions($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchJournalFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchJournalFormFieldOptions($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchLedgerConfigFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchLedgerConfigFormFieldOptions($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountTypes()
    {
        $response = $this->app['ledger']->fetchAccountTypes($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchFundAccountTypes()
    {
        $response = $this->app['ledger']->fetchFundAccountTypes($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deleteMerchants()
    {
        $response = $this->app['ledger']->deleteMerchants($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournalCron()
    {
        $response = $this->app['ledger']->createJournalCron($this->input, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

}
