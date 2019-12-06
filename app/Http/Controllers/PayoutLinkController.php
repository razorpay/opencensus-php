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

    public function generateCustomerOtp(string $payoutLinkId)
    {
        $response = $this->service()->generateCustomerOtp($payoutLinkId, $this->input);

        return ApiResponse::json($response);

    }

}
