<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Payout\Configurations;

class PayoutsConfigurationsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createDirectAccountPayoutModeConfig()
    {
        $input = Request::all();

        $response = (new Configurations\DirectAccounts\PayoutModeConfig\Service())->createPayoutModeConfig($input);

        return ApiResponse::json($response);
    }

    public function editDirectAccountPayoutModeConfig()
    {
        $input = Request::all();

        $response = (new Configurations\DirectAccounts\PayoutModeConfig\Service())->editPayoutModeConfig($input);

        return ApiResponse::json($response);
    }

    public function fetchDirectAccountPayoutModeConfig()
    {
        $input = Request::all();

        $response = (new Configurations\DirectAccounts\PayoutModeConfig\Service())->fetchPayoutModeConfig($input);

        return ApiResponse::json($response);
    }
}
