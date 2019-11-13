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
}