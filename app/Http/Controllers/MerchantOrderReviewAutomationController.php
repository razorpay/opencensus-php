<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class MerchantOrderReviewAutomationController extends Controller
{
    protected function get()
    {
        $merchantId = $this->ba->getMerchant()->getId();

        $response = $this->app['rto_prediction_provider_service']->getMerchantOrderReviewAutomationRuleConfigs($merchantId);

        return ApiResponse::json($response);
    }

    protected function upsert()
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $userEmail = $this->ba->getUser()->getEmail();

        $input['merchant_id'] = $merchantId;

        $input['created_by'] = $userEmail;

        $response = $this->app['rto_prediction_provider_service']->upsertMerchantOrderReviewAutomationRuleConfigs($input);

        return ApiResponse::json($response);
    }
}
