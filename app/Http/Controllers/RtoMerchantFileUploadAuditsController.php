<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class RtoMerchantFileUploadAuditsController extends Controller
{

    protected function createFileUploadAudit()
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $response = $this->app['rto_file_upload_audit_service']->create($input, $merchantId);

        $apiResponse = ApiResponse::json($response);

        return $apiResponse;
    }

    protected function listFileUploadAudits()
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $response = $this->app['rto_file_upload_audit_service']->list($input, $merchantId);

        $apiResponse = ApiResponse::json($response);

        return $apiResponse;
    }
}
