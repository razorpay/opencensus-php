<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class FeeRecoveryController extends Controller
{
    public function createRecoveryPayout()
    {
        $input = Request::all();

        $response = $this->service()->createRecoveryPayout($input);

        return ApiResponse::json($response);
    }

    public function processRecoveryPayout()
    {
        $input = Request::all();

        $response = $this->service()->recoveryPayoutCron($input);

        return ApiResponse::json($response);
    }

    public function postManualRecovery()
    {
        $input = Request::all();

        $response = $this->service()->createManualRecovery($input);

        return ApiResponse::json($response);
    }

    public function createRecoveryRetryPayoutManually()
    {
        $input = Request::all();

        $response = $this->service()->createRecoveryRetryPayoutManually($input);

        return ApiResponse::json($response);
    }

    public function processFeeRecoveryBalanceCron()
    {
        $response = $this->service()->processFeeRecoveryBalanceCron();

        return ApiResponse::json($response);
    }

    public function updateFeeRecoveryScheduleAdmin()
    {
        $input = Request::all();

        $response = $this->service()->updateFeeRecoveryScheduleAdmin($input);

        return ApiResponse::json($response);
    }

    public function calculateFeeRecoveryAmountAdmin()
    {
        $input = Request::all();

        $response = $this->service()->calculateFeeRecoveryAmountAdmin($input);

        return ApiResponse::json($response);
    }

    public function createRecoveryPayoutJobAdmin()
    {
        $input = Request::all();

        $response = $this->service()->createRecoveryPayoutJobAdmin($input);

        return ApiResponse::json($response);
    }
}
