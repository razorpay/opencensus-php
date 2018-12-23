<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PayoutController extends Controller
{
    public function postFundAccountPayout()
    {
        $input = Request::all();

        $data = $this->service()->fundAccountPayout($input);

        return ApiResponse::json($data);
    }

    /**
     * Logged in business banking user creates payout with OTP (proxy auth)
     *
     * @return \Illuminate\Http\Response
     */
    public function postFundAccountPayoutWithOtp()
    {
        $response = $this->service()->fundAccountPayoutWithOtp($this->input);

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

    public function getPurposes()
    {
        $data = $this->service()->getPurposes();

        return ApiResponse::json($data);
    }

    public function postPurpose()
    {
        $input = Request::all();

        $data = $this->service()->postPurpose($input);

        return ApiResponse::json($data);
    }
}
