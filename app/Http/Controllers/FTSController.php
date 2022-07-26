<?php

namespace RZP\Http\Controllers;

use Request;
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


    public function createSourceAccountCopy()
    {
        $response = $this->app['fts_fund_transfer']->createSourceAccountCopy($this->input);

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

    public function getNewChannelHealthStats()
    {
        $response = $this->app['fts_fund_transfer']->getNewChannelHealthStats($this->input);

        return ApiResponse::json($response);
    }

    public function getTriggerHealthStatus()
    {
        $response = $this->app['fts_fund_transfer']->getTriggerStatus($this->input);

        return ApiResponse::json($response);
    }

    public function createSourceAccountMappings()
    {
        $response = $this->app['fts_fund_transfer']->createSourceAccountMappings($this->input);

        return ApiResponse::json($response);
    }

    public function createDirectAccountRoutingRules()
    {
        $response = $this->app['fts_fund_transfer']->createDirectAccountRoutingRules($this->input);

        return ApiResponse::json($response);
    }

    public function deleteDirectAccountRoutingRules()
    {
        $response = $this->app['fts_fund_transfer']->deleteDirectAccountRoutingRules($this->input);

        return ApiResponse::json($response);
    }

    public function createPreferredRoutingWeights()
    {
        $response = $this->app['fts_fund_transfer']->createPreferredRoutingWeights($this->input);

        return ApiResponse::json($response);
    }

    public function createAccountTypeMappings()
    {
        $response = $this->app['fts_fund_transfer']->createAccountTypeMappings($this->input);

        return ApiResponse::json($response);
    }

    public function deleteSourceAccountMappings()
    {
        $response = $this->app['fts_fund_transfer']->deleteSourceAccountMappings($this->input);

        return ApiResponse::json($response);
    }

    public function deletePreferredRoutingWeights()
    {
        $response = $this->app['fts_fund_transfer']->deletePreferredRoutingWeights($this->input);

        return ApiResponse::json($response);
    }

    public function deleteAccountTypeMappings()
    {
        $response = $this->app['fts_fund_transfer']->deleteAccountTypeMappings($this->input);

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
        $this->input = Request::all();

        $response = $this->app['fts_channel_notification']->channelNotify($this->input);

        return ApiResponse::json($response);
    }

    public function oneOffDbMigrateCron()
    {
        $response = $this->app['fts_create_account']->oneOffDbMigrateCron($this->input);


        return ApiResponse::json($response);
    }

    public function lowBalanceAlert()
    {
        $response = $this->app['fts_fund_transfer']->sendAlertIfLowBalance($this->input);

        return ApiResponse::json($response);
    }

    public function fetchAccountBalance()
    {
        $response = $this->app['fts_fund_transfer']->fetchAccountBalance($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function failQueuedTransfer()
    {
        $response = $this->app['fts_fund_transfer']->failQueuedTransfer($this->input);

        return ApiResponse::json($response);
    }

    public function failQueuedTransferBulk()
    {
        $response = $this->app['fts_fund_transfer']->failQueuedTransferBulk($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function createSchedule()
    {
        $response = $this->app['fts_fund_transfer']->createSchedule($this->input);

        return ApiResponse::json($response);
    }

    public function deleteSchedule()
    {
        $response = $this->app['fts_fund_transfer']->deleteSchedule($this->input);

        return ApiResponse::json($response);
    }

    public function updateSchedule()
    {
        $response = $this->app['fts_fund_transfer']->updateSchedule($this->input);

        return ApiResponse::json($response);
    }

    public function manualOverride()
    {
        $response = $this->app['fts_fund_transfer']->manualOverride($this->input);

        return ApiResponse::json($response);
    }

    public function createMerchantConfigurations()
    {
        $response = $this->app['fts_fund_transfer']->createMerchantConfigurations($this->input);

        return ApiResponse::json($response);
    }

    public function deleteMerchantConfigurations()
    {
        $response = $this->app['fts_fund_transfer']->deleteMerchantConfigurations($this->input);

        return ApiResponse::json($response);
    }

    public function patchMerchantConfigurations()
    {
        $response = $this->app['fts_fund_transfer']->patchMerchantConfigurations($this->input);

        return ApiResponse::json($response);
    }

    public function failFastStatusManualUpdate()
    {
        $response = $this->app['fts_fund_transfer']->failFastStatusManualUpdate($this->input);

        return ApiResponse::json($response);
    }

    public function forceRetryFTSTransfer()
    {
        $response = $this->app['fts_fund_transfer']->forceRetryFTSTransfer($this->input);

        return ApiResponse::json($response);
    }

    public function patchKeyValuePair()
    {
        $response = $this->app['fts_fund_transfer']->patchKeyValuePair($this->input);

        return ApiResponse::json($response);
    }

    public function postKeyValuePair()
    {
        $response = $this->app['fts_fund_transfer']->postKeyValuePair($this->input);

        return ApiResponse::json($response);
    }
}
