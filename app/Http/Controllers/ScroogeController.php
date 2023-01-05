<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Error\Error;
use RZP\Error\ErrorCode;

class ScroogeController extends Controller
{
    public function get($id)
    {
        $response = $this->app['scrooge']->getRefund($id);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function listReports()
    {
        $response = $this->app['scrooge']->getReports($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function bulkStatusUpdate()
    {
        $response = $this->app['scrooge']->bulkUpdateRefundStatus($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function reverseFailedRefunds()
    {
        $response = $this->app['scrooge']->reverseFailedRefunds($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function bulkReference1Update()
    {
        $response = $this->app['scrooge']->bulkUpdateRefundReference1($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function enqueue()
    {
        $response = $this->app['scrooge']->enqueueRefunds($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function statusUpdate(string $id)
    {
        $response = $this->app['scrooge']->updateRefundStatus($id, $this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function listRefunds()
    {
        $response = $this->app['scrooge']->getRefunds($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function downloadRefunds()
    {
        $response = $this->app['scrooge']->downloadRefunds($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function downloadGatewayRefundsFile()
    {
        $response = $this->app['scrooge']->downloadGatewayRefundsFile($this->input);

        if ($response['code'] === 400)
        {
            $publicErrorMessage = json_decode(json_encode($response['body']), true)['public_error']['message']
                ?? 'service request failed';

            $error = new Error(ErrorCode::BAD_REQUEST_SCROOGE_DASHBOARD_ERROR, $publicErrorMessage);

            return ApiResponse::generateErrorResponse($error);
        }

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function downloadGatewayReportsFile()
    {
        $response = $this->app['scrooge']->downloadGatewayReportsFile($this->input);

        if ($response['code'] !== 201)
        {
            $publicErrorMessage = json_decode(json_encode($response['body']), true)['public_error']['message']
                ?? 'service request failed';

            $error = new Error(ErrorCode::BAD_REQUEST_SCROOGE_DASHBOARD_ERROR, $publicErrorMessage);

            return ApiResponse::generateErrorResponse($error);
        }

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function dashboardInit()
    {
        $response = $this->app['scrooge']->dashboardInit($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function setInstantRefundsMode()
    {
        $response = $this->app['scrooge']->setInstantRefundsMode($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function setInstantRefundsModeForMerchant(string $mid)
    {
        $response = $this->app['scrooge']->setInstantRefundsMode($this->input, $mid);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function expireInstantRefundsModeConfig(string $id)
    {
        $response = $this->app['scrooge']->expireInstantRefundsModeConfig($id, $this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function expireInstantRefundsModeConfigForMerchant(string $mid, string $id)
    {
        $response = $this->app['scrooge']->expireInstantRefundsModeConfig($id, $this->input, $mid);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function refreshFtaModes()
    {
        $response = $this->app['scrooge']->refreshFtaModes($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchInstantRefundsModeConfigs()
    {
        $response = $this->app['scrooge']->fetchInstantRefundsModeConfigs($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function fetchInstantRefundsModeConfigsForMerchant(string $mid)
    {
        $response = $this->app['scrooge']->fetchInstantRefundsModeConfigs($this->input, $mid);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function retryRefundsWithVerify()
    {
        $response = $this->app['scrooge']->retryRefundsWithVerify($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function retryRefundsWithoutVerify()
    {
        $response = $this->app['scrooge']->retryRefundsWithoutVerify($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function retryRefundsWithAppend()
    {
        $response = $this->app['scrooge']->retryRefundsWithAppend($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function retryRefundsViaSourceFundTransfers()
    {
        $response = $this->app['scrooge']->retryRefundsViaSourceFundTransfers($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function retryRefundsViaCustomFundTransfers()
    {
        $response = $this->app['scrooge']->retryRefundsViaCustomFundTransfers($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }

    public function updateRefund($id)
    {
        $response = $this->app['scrooge']->updateRefund($id, $this->input);

        return ApiResponse::json($response);
    }

    public function refundsVerifyBulk() {
        $response = $this->app['scrooge']->verifyRefunds($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }
}
