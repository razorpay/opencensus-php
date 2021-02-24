<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use RZP\Http\RequestHeader;
use RZP\Services\Razorflow;

class RazorflowController extends Controller
{
    public function postSlashCommand(string $customEndpoint = null)
    {
        $inputHeaders = [
            Razorflow::SLACK_REQUEST_TIMESTAMP => $this->app['request']->header(RequestHeader::X_SLACK_REQUEST_TIMESTAMP),
            Razorflow::SLACK_SIGNATURE         => $this->app['request']->header(RequestHeader::X_SLACK_SIGNATURE),
        ];

        $response = $this->app['razorflow']->postSlashCommand($this->input, $inputHeaders, $customEndpoint);

        return ApiResponse::json($response[Razorflow::RESPONSE_BODY], $response[Razorflow::RESPONSE_CODE]);
    }
}
