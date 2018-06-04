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
        $input = Request::all();

        $summary = $this->service()->sendNotification($id, $input);

        return ApiResponse::json($summary);
    }
}
