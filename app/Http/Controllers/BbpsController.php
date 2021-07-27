<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Trace\TraceCode;

class BbpsController extends Controller
{
    protected $bbpsService;

    public function __construct()
    {
        parent::__construct();

        $this->bbpsService = $this->app['bbpsService'];
    }

    public function showBbpsDashboard()
    {
        $response = $this->bbpsService->getIframeForDashboard($this->ba->getMerchant(), $this->ba->getMode());

        $this->trace->info(TraceCode::BBPS_SERVICE_RESPONSE,
            [
                'response' => $response
            ]);

        return ApiResponse::json($response);
    }
}
