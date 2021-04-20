<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PricingController extends Controller
{
    public function postCreatePlan()
    {
        $input = Request::all();

        $data = $this->service()->createPlan($input);

        return ApiResponse::json($data);
    }

    public function getPlan($id)
    {
        $data = $this->service()->getPlanById($id);

        return ApiResponse::json($data);
    }

    public function getPlans()
    {
        $input = Request::all();

        $data = $this->service()->getPlans($input);

        return ApiResponse::json($data);
    }

    public function getMerchantPricingPlans()
    {
        $input = Request::all();

        $data = $this->service()->getMerchantPricingPlans($input);

        return ApiResponse::json($data);
    }

    public function getGatewayPricingPlans()
    {
        $data = $this->service()->getGatewayPricingPlans();

        return ApiResponse::json($data);
    }

    public function postAddPlanRule($id)
    {
        $input = Request::all();

        $data = $this->service()->addPlanRule($id, $input);

        return ApiResponse::json($data);
    }

    public function postAddBulkPlanRules()
    {
        $input = Request::all();

        $data = $this->service()->postAddBulkPricingRules($input);

        return ApiResponse::json($data);
    }

    public function updatePlanRule($planId, $ruleId)
    {
        $input = Request::all();

        $data = $this->service()->updatePlanRule($planId, $ruleId, $input);

        return ApiResponse::json($data);
    }

    public function deletePlanRuleForce($planId, $ruleId)
    {
        $data = $this->service()->deletePlanRuleForce($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function getSupportedNetworks()
    {
        $data = $this->service()->getSupportedNetworks();

        return ApiResponse::json($data);
    }

}
