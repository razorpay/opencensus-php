<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\WebhookV2\Service;

class WebhookV2Controller extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = (new Service)->createForMerchant($input);

        return ApiResponse::json($data);
    }

    public function createForOAuthApp(string $appId)
    {
        $input = Request::all();

        $data = (new Service)->createForOAuthApp($input, $appId);

        return ApiResponse::json($data);
    }

    public function update($id)
    {
        $input = Request::all();

        $data = (new Service)->update($id, $input);

        return ApiResponse::json($data);
    }

    public function get($id)
    {
        $data = (new Service)->get($id);

        return ApiResponse::json($data);
    }

    public function list()
    {
        $data = (new Service)->list($this->input);

        return ApiResponse::json($data);
    }

    public function delete(string $id)
    {
        (new Service)->delete($id);

        return ApiResponse::json([]);
    }

    public function getAnalytics(string $id)
    {
        $res = (new Service)->getAnalytics($id, $this->input);
        return ApiResponse::json($res);
    }

    public function sendEmail(string $emailType)
    {
        $input = Request::all();

        (new Service)->sendEmail($emailType, $input);

        return ApiResponse::json([]);
    }

    /**
     * @see WebhookV2\Service's processWebhookEventsFromCsv method.
     * @return \Illuminate\Http\JsonResponse
     */
    public function processWebhookEventsFromCsv()
    {
        (new Service)->processWebhookEventsFromCsv($this->input);

        return ApiResponse::json([]);
    }

    /**
     * @see WebhookV2\Service's processWebhookEventsByIds method.
     * @return \Illuminate\Http\JsonResponse
     */
    public function processWebhookEventsByIds()
    {
        $data = (new Service)->processWebhookEventsByIds($this->input);

        return ApiResponse::json($data);
    }

    /**
     * @deprecated This should be removed. Subscriptions service, who is only
     * user for this route, should integrate with stork for dispatching webhook
     * events.
     *
     * Dispatches payload to stork.
     *
     * Sample payload-
     * {
     *   "payloads": [
     *     {
     *       "refund": {
     *         "entity": {
     *           "entity": "refund"
     *           "id": "rfnd_DIsa6F6Fikva7D",
     *         }
     *       }
     *     }
     *   ]
     * }
     *
     * @param  string $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function processWebhook(string $event)
    {
        (new Service)->processWebhook($event, $this->input);

        return ApiResponse::json([]);
    }

    /**
     * TODO: Write comment!
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhookEmailStorkRecon()
    {
        $summary = (new Service)->webhookEmailStorkRecon($this->input);

        return ApiResponse::json($summary);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWebhookEvents()
    {
        $response = (new Service)->getWebhookEvents();

        return ApiResponse::json($response);
    }

    public function listWebhookEvents()
    {
        $input = Request::all();

        $data = (new Service)->listWebhookEvents($input);

        return ApiResponse::json($data);
    }
}
