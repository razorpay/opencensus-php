<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Http\Controllers\Traits\HasCrudMethods;

class PaymentLinkController extends Controller
{
    use HasCrudMethods;

    public function sendNotification(string $id)
    {
        $this->service()->sendNotification($id, $this->input);

        return ApiResponse::json([]);
    }

    public function expirePaymentLinks()
    {
        $summary = $this->service()->expirePaymentLinks();

        return ApiResponse::json($summary);
    }

    public function deactivate(string $id)
    {
        $response = $this->service()->deactivate($id);

        return ApiResponse::json($response);
    }
}
