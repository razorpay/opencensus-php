<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant\WebhookV2\Service;

class OnboardingWebhookController extends Controller
{
    public function create(string $accountId)
    {
        $input = Request::all();

        $response = (new Service)->createOnboardingWk($input, $accountId);

        return ApiResponse::json($response);
    }

    public function fetch(string $accountId, string $webhookId)
    {
        $response = (new Service)->fetchOnboardingWk($webhookId, $accountId);

        return ApiResponse::json($response);
    }

    public function fetchAll(string $accountId)
    {
        $input = Request::all();

        $response = (new Service)->listOnboardingWk($input, $accountId);

        return ApiResponse::json($response);
    }

    public function update(string $accountId, string $webhookId)
    {
        $input = Request::all();

        $response = (new Service)->updateOnboardingWk($webhookId, $input, $accountId);

        return ApiResponse::json($response);
    }

    public function delete(string $accountId, string $webhookId)
    {
        (new Service)->deleteOnboardingWk($webhookId, $accountId);

        return ApiResponse::json([]);
    }
}
