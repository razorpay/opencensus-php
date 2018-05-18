<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PaymentLinkController extends Controller
{
    public function createPaymentLink()
    {
        $input = Request::all();

        $paymentLink = $this->service()->create($input);

        return ApiResponse::json($paymentLink);
    }

    public function fetchPaymentLinks()
    {
        $input = Request::all();

        $paymentLinks = $this->service()->fetchMultiple($input);

        return ApiResponse::json($paymentLinks);
    }

    public function updatePaymentLink(string $id)
    {
        $input = Request::all();

        $paymentLink = $this->service()->update($id, $input);

        return ApiResponse::json($paymentLink);
    }

    public function fetchPaymentLinkPayments(string $id)
    {
        $input = Request::all();

        $payments = $this->service()->fetchPayments($id, $input);

        return ApiResponse::json($payments);
    }
}
