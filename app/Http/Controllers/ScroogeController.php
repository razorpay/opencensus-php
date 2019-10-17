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

    public function setRefundDark(string $id, string $action)
    {
        $response = $this->app['scrooge']->setRefundDark($id, $action, $this->input);

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

    public function deleteInstantRefundsMode()
    {
        $response = $this->app['scrooge']->deleteInstantRefundsMode($this->input);

        return ApiResponse::json($response['body'], $response['code']);
    }
}
