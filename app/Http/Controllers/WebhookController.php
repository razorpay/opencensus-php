<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class WebhookController extends Controller
{
    /**
     * Dispatches payload(i.e. input) for given event onto merchant's webhook
     * if it exists and is active.
     *
     * Sample payload-
     * {
     *   "payloads": [
     *     {
     *       "refund": {
     *         "entity": {
     *           "amount": 20000,
     *           "notes": [],
     *           "payment_id": "pay_DIryUfJprrOTZv",
     *           "acquirer_data": {
     *             "rrn": null
     *           },
     *           "created_at": 1568638864,
     *           "currency": "INR",
     *           "receipt": null,
     *           "id": "rfnd_DIsa6F6Fikva7D",
     *           "entity": "refund"
     *         }
     *       }
     *     }
     *   ]
     * }
     *
     * @param  string $event
     * @return mixed
     */
    public function processWebhook(string $event)
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

    /**
     * @see Webhook\Service::webhookStorkRecon()
     * @return mixed
     */
    public function webhookStorkRecon()
    {
        $data = $this->service()->webhookStorkRecon($this->input);

        return ApiResponse::json($data);
    }

    /**
     *
     * @return mixed
     */
    public function webhookStorkCreateBankingBulk()
    {
        $data = $this->service()->webhookStorkCreateBankingBulk($this->input);

        return ApiResponse::json($data);
    }

    /**
     * @see Webhook\Service::webhookDeactivate()
     *
     * @param string $id
     * @return mixed
     */
    public function webhookDeactivate(string $id)
    {
        $input = Request::all();

        $this->service()->webhookDeactivate($id, $input);

        return ApiResponse::json([]);
    }

    /**
     * @return mixed
     */
    public function webhookEmailStorkRecon()
    {
        $input = Request::all();

        $summary = $this->service()->webhookEmailStorkRecon($input);

        return ApiResponse::json($summary);
    }
}
