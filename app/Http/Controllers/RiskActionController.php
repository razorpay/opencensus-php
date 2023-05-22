<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\RiskWorkflowAction\Constants;
use RZP\Models\RiskWorkflowAction\Service;
use RZP\Trace\TraceCode;

class RiskActionController extends Controller
{
    public function getRiskAttributes()
    {
        $data = (new Service())->getRiskAttributes();

        return ApiResponse::json($data);
    }

    public function createAndExecuteRiskAction()
    {
        $input = Request::all();

        $response = (new Service())->createAndExecuteRiskAction($input);

        return ApiResponse::json($response);
    }

    public function createRiskAction()
    {
        $input = Request::all();

        $response = (new Service())->createRiskWorkflowAction($input);

        return ApiResponse::json($response);
    }

    public function createRiskActionRas()
    {
        $input = Request::all();

        $response = (new Service())->createRiskWorkflowActionRas($input);

        return ApiResponse::json($response);
    }

    public function createRiskActionInternal()
    {
        $input = Request::all();

        if (isset($input[Constants::RISK_ATTRIBUTES]) && isset($input[Constants::RISK_ATTRIBUTES][Constants::RISK_SOURCE])
        && $input[Constants::RISK_ATTRIBUTES][Constants::RISK_SOURCE] === Constants::RISK_SOURCE_MERCHANT_RISK_ALERTS)
        {
            $response = (new Service())->createRiskWorkflowActionRas($input);
        }
        else
        {
            $response = (new Service())->createRiskWorkflowActionInternal($input);
        }
        return ApiResponse::json($response);
    }
}
