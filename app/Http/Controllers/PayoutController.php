<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PayoutController extends Controller
{
    public function postContactPayout()
    {
        $input = Request::all();

        $data = $this->service()->contactPayout($input);

        return ApiResponse::json($data);
    }

    /**
     * Logged in business banking user creates payout with otp.
     * @return \Illuminate\Http\Response
     */
    public function postCustomerPayoutWithOtp()
    {
        $response = $this->service()->customerPayoutWithOtp($this->input);

        return ApiResponse::json($response);
    }

    public function postMerchantPayoutOnDemand()
    {
        $input = Request::all();

        $data = $this->service()->merchantPayoutOnDemand($input);

        return ApiResponse::json($data);
    }

    public function postInternalMerchantPayout()
    {
        $input = Request::all();

        $data = $this->service()->internalMerchantPayout($input);

        return ApiResponse::json($data);
    }

    public function getPayout(string $id)
    {
        $input = Request::all();

        $data = $this->service()->fetch($id, $input);

        return ApiResponse::json($data);
    }

    public function getPayouts()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postPayoutRetry()
    {
        $input = Request::all();

        $data = $this->service()->processFailedPayouts($input);

        return ApiResponse::json($data);
    }
}
