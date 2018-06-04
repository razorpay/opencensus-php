<?php

namespace RZP\Http\Controllers;

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

    public function expireLinks()
    {
        $summary = $this->service()->expireLinks();

        return ApiResponse::json($summary);
    }
}
