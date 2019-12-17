<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class PayoutLinkController extends Controller
{
    use Traits\HasCrudMethods;

    public function update(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function delete(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->generateAndSendCustomerOtp($payoutLinkId, $this->input);

        return ApiResponse::json($response);

    }

    public function verifyCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->verifyCustomerOtp($payoutLinkId, $this->input);

        return ApiResponse::json($response);
    }
}
