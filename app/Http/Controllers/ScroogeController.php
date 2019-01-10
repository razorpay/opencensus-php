<?php

namespace RZP\Http\Controllers;

use ApiResponse;

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
}
