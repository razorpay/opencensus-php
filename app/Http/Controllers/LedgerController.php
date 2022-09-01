<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Error\Error;
use RZP\Error\ErrorCode;

class LedgerController extends Controller
{
    /**
     * HTTP request headers
     *
     * @var array
     */
    protected $headers;

    public function __construct()
    {
        parent::__construct();

        $this->headers = Request::header();
    }

    public function createAccount()
    {
        $response = $this->app['ledger']->createAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsOnEvent()
    {
        $response = $this->app['ledger']->createAccountsOnEvent($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createAccountsInBulk()
    {
        $response = $this->app['ledger']->createAccountsInBulk($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function activateAccount()
    {
        $response = $this->app['ledger']->activateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deactivateAccount()
    {
        $response = $this->app['ledger']->deactivateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function archiveAccount()
    {
        $response = $this->app['ledger']->archiveAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccount()
    {
        $response = $this->app['ledger']->updateAccount($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountByEntitiesAndMerchantID()
    {
        $response = $this->app['ledger']->updateAccountByEntitiesAndMerchantID($this->input, $this->headers, true);
        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateAccountDetail()
    {
        $response = $this->app['ledger']->updateAccountDetail($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournal()
    {
        $response = $this->app['ledger']->createJournal($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchByTransactor()
    {
        $response = $this->app['ledger']->fetchByTransactor($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createLedgerConfig()
    {
        $response = $this->app['ledger']->createLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function UpdateLedgerConfig()
    {
        $response = $this->app['ledger']->updateLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function DeleteLedgerConfig()
    {
        $response = $this->app['ledger']->deleteLedgerConfig($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function requestGovernor()
    {
        $response = $this->app['ledger']->requestGovernor($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetch()
    {
        $response = $this->app['ledger']->fetch($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchMultiple()
    {
        $response = $this->app['ledger']->fetchMultiple($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchFilter()
    {
        $response = $this->app['ledger']->fetchFilter($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchAccountFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchJournalFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchJournalFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function replayJournalRejectedEvents()
    {
        $response = $this->app['ledger']->replayJournalRejectedEvents($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchLedgerConfigFormFieldOptions()
    {
        $response = $this->app['ledger']->fetchLedgerConfigFormFieldOptions($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchAccountTypes()
    {
        $response = $this->app['ledger']->fetchAccountTypes($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function deleteMerchants()
    {
        $response = $this->app['ledger']->deleteMerchants($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createJournalCron()
    {
        $response = $this->app['ledger']->createJournalCron($this->input, $this->headers, true);

        return ApiResponse::json($response['body'], $response['code']);
    }

}
