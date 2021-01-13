<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class FTSController extends Controller
{
    public function updateBulkFtsAttempts()
    {
        $response = $this->app['fts_fund_transfer']->bulkUpdateFtsAttempts($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function getBulkTransferStatus()
    {
        $response = $this->app['fts_fund_transfer']->getBulkTransferStatus($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function checkTransferStatus()
    {
        $response = $this->app['fts_fund_transfer']->checkTransferStatus($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function getRawBankStatus()
    {
        $response = $this->app['fts_fund_transfer']->getRawBankStatus($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createSourceAccount()
    {
        $response = $this->app['fts_create_account']->createAccountMappingForFts($this->input);

        return ApiResponse::json($response);
    }

    public function deleteSourceAccount()
    {
        $response = $this->app['fts_create_account']->deleteSourceAccount($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateSourceAccount()
    {
        $response = $this->app['fts_create_account']->updateSourceAccount($this->input);

        return ApiResponse::json($response);
    }

    public function getBulkStatus()
    {
        $response = $this->app['fts_fund_transfer']->getBulkStatus($this->input);

        return ApiResponse::json($response);
    }

    public function createChannelHealth()
    {
        $response = $this->app['fts_fund_transfer']->createChannelHealth($this->input);

        return ApiResponse::json($response);
    }

    public function deleteChannelHealth()
    {
        $response = $this->app['fts_fund_transfer']->deleteChannelHealth($this->input);

        return ApiResponse::json($response);
    }

    public function triggerTestTransactions()
    {
        $response = $this->app['fts_fund_transfer']->triggerTestTransactions($this->input);

        return ApiResponse::json($response);
    }

    public function getChannelHealthStats()
    {
        $response = $this->app['fts_fund_transfer']->getChannelHealthStats($this->input);

        return ApiResponse::json($response);
    }

    public function createSourceAccountMappings()
    {
        $response = $this->app['fts_fund_transfer']->createSourceAccountMappings($this->input);

        return ApiResponse::json($response);
    }

    public function deleteSourceAccountMappings()
    {
        $response = $this->app['fts_fund_transfer']->deleteSourceAccountMappings($this->input);

        return ApiResponse::json($response);
    }

    public function initiateBulkFtsAttempts()
    {
        $response = $this->app['fts_fund_transfer']->initiateBulkFtsAttempts($this->input);

        return ApiResponse::json($response);
    }

    public function initiateBulkBeneficiary()
    {
        $response = $this->app['fts_create_account']->initiateBulkBeneficiary($this->input);

        return ApiResponse::json($response);
    }

    public function  publishBulkTransfers()
    {
        $response = $this->app['fts_fund_transfer']->publishBulkTransfers($this->input);

        return ApiResponse::json($response);
    }

    public function getPendingFundTransfers()
    {
        $response = $this->app['fts_fund_transfer']->getPendingFundTransfers($this->input);

        return ApiResponse::json($response);
    }

    public function channelNotify()
    {
        $response = $this->app['fts_channel_notification']->channelNotify($this->input);

        return ApiResponse::json($response);
    }

    public function oneOffDbMigrateCron()
    {
        $response = $this->app['fts_create_account']->oneOffDbMigrateCron($this->input);

        return ApiResponse::json($response);
    }

}
