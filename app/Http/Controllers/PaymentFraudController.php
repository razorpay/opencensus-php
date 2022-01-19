<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;

class PaymentFraudController extends Controller
{
    public function getFraudAttributes()
    {
        $input = Request::all();

        $response = $this->service(E::PAYMENT_FRAUD)->getFraudAttributes($input);

        return ApiResponse::json($response);
    }

    public function savePaymentFraud()
    {
        $input = Request::all();

        $response = $this->service(E::PAYMENT_FRAUD)->savePaymentFraud($input);

        return ApiResponse::json($response);
    }
}
