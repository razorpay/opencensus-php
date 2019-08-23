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

        return ApiResponse::json([]);
    }

    /**
     * @see Webhook\Service::webhookStorkMigrate()
     *
     * @return mixed
     */
    public function webhookStorkMigrate()
    {
        $data = $this->service()->webhookStorkMigrate($this->input);

        return ApiResponse::json($data);
    }
}
