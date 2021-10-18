<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\RiskWorkflowAction\Service;

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
}
