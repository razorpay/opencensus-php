<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class WebhookController extends Controller
{
    public function processWebhook(String $event)
    {
        $input = Request::all();

        $this->service()->processWebhook($event, $input);

        return ApiResponse::json(["status" => "success"]);
    }
}