<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Pricing;

class PricingController extends Controller
{
    public function postCreatePricingPlan()
    {
        $input = Request::all();

        $data = (new Pricing\Service)->createPricingPlan($input);

        return ApiResponse::json($data);
    }

    public function postUploadPricingPlan()
    {
        $input = Request::all();

        $data = (new Pricing\Service)->uploadPricingPlan($input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = (new Pricing\Service)->getPricingPlanById($id);

        return ApiResponse::json($data);
    }

    public function getPricingPlans()
    {
        $data = (new Pricing\Service)->getPricingPlans();

        return ApiResponse::json($data);
    }

    public function getMerchantPricingPlans()
    {
        $data = (new Pricing\Service)->getMerchantPricingPlans();

        return ApiResponse::json($data);
    }

    public function getGatewayPricingPlans()
    {
        $data = (new Pricing\Service)->getGatewayPricingPlans();

        return ApiResponse::json($data);
    }

    public function postAddPricingPlanRule($id)
    {
        $input = Request::all();

        $data = (new Pricing\Service)->addPricingPlanRule($id, $input);

        return ApiResponse::json($data);
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $data = (new Pricing\Service)->deletePricingPlanRule($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function deletePricingPlanRuleForce($planId, $ruleId)
    {
        $data = (new Pricing\Service)->deletePricingPlanRuleForce($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function getSupportedNetworks()
    {
        $data = (new Pricing\Service)->getSupportedNetworks();

        return ApiResponse::json($data);
    }

}
