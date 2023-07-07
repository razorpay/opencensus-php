<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\MerchantRiskAlert\Constants as MerchantRiskAlertConstants;

class MerchantRiskAlertController extends Controller
{
    public function createFOHWorkflow()
    {
        $input = Request::all();

        $response = $this->service()->createFOHWorkflow($input);

        return ApiResponse::json($response);
    }

    public function createRule()
    {
        $input = Request::all();

        $response = $this->service()->createRule($input);

        return ApiResponse::json($response);
    }

    public function updateRule($ruleId)
    {
        $input = Request::all();

        $response = $this->service()->updateRule($ruleId, $input);

        return ApiResponse::json($response);
    }

    public function deleteRule($ruleId)
    {
        $response = $this->service()->deleteRule([MerchantRiskAlertConstants::RAS_RULES_ID => $ruleId]);

        return ApiResponse::json($response);
    }

    public function getMerchantDetails(string $mid)
    {
        $response = $this->service()->getMerchantDetails($mid);

        return ApiResponse::json($response);
    }

    // NOTE: This is not mapped to an external route
    // Meant foh execution (specifically for auto foh)
    public function executeFOHWorkflow()
    {
        $input = Request::all();

        $response = $this->service()->executeFOHWorkflow($input);

        return ApiResponse::json($response);
    }

    public function getMerchantDisputeDetails(string $mid)
    {
        $input = Request::all();

        $response = $this->service()->getMerchantDisputeDetails($mid, $input);

        return ApiResponse::json($response);
    }

    public function identifyBlacklistCountryAlerts()
    {
        $input = Request::all();

        $response = $this->service()->identifyBlacklistCountryAlerts($input);

        return ApiResponse::json($response);
    }

    public function postTriggerNeedsClarification(string $workflowActionId)
    {
        $input = Request::all();

        $response = $this->service()->triggerNeedsClarification($workflowActionId, $input);

        return ApiResponse::json($response);
    }

    public function setMerchantDedupeKey(string $mid)
    {
        $response = $this->service()->setMerchantDedupeKey($mid);

        return ApiResponse::json($response);
    }

    public function fetchMappings()
    {
        $response = $this->service()->fetchMappings();

        return ApiResponse::json($response);
    }
}
