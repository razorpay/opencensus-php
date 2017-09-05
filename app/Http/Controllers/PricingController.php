<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class PricingController extends Controller
{
    public function postCreatePricingPlan()
    {
        $input = Request::all();

        $data = $this->service()->createPricingPlan($input);

        return ApiResponse::json($data);
    }

    public function postUploadPricingPlan()
    {
        $input = Request::all();

        $data = $this->service()->uploadPricingPlan($input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = $this->service()->getPricingPlanById($id);

        return ApiResponse::json($data);
    }

    public function getPricingPlans()
    {
        $data = $this->service()->getPricingPlans();

        return ApiResponse::json($data);
    }

    public function getMerchantPricingPlans()
    {
        $data = $this->service()->getMerchantPricingPlans();

        return ApiResponse::json($data);
    }

    public function getGatewayPricingPlans()
    {
        $data = $this->service()->getGatewayPricingPlans();

        return ApiResponse::json($data);
    }

    public function postAddPricingPlanRule($id)
    {
        $input = Request::all();

        $data = $this->service()->addPricingPlanRule($id, $input);

        return ApiResponse::json($data);
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $data = $this->service()->deletePricingPlanRule($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function deletePricingPlanRuleForce($planId, $ruleId)
    {
        $data = $this->service()->deletePricingPlanRuleForce($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function getSupportedNetworks()
    {
        $data = $this->service()->getSupportedNetworks();

        return ApiResponse::json($data);
    }

}
