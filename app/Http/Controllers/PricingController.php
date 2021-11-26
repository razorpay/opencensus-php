<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Pricing\Entity;
use RZP\Models\Pricing\Type;
use RZP\Trace\Tracer;

class PricingController extends Controller
{
    public function postCreatePlan()
    {
        $input = Request::all();

        $data = $this->service()->createPlan($input);

        return ApiResponse::json($data);
    }

    public function postCreateBuyPlan()
    {
        $input = Request::all();

        $data = $this->service()->createPlan($input, Type::BUY_PRICING);

        return ApiResponse::json($data);
    }

    public function postCalculateBuyPricingCost()
    {
        return Tracer::inSpan(['name' => 'buy_pricing.process_terminals_cost'], function()
        {
            $input = Request::all();

            $data = $this->service()->processBuyPricingCostCalculation($input);

            return ApiResponse::json($data);
        });
    }

    public function getPlan($id)
    {
        $data = $this->service()->getPlanById($id);

        return ApiResponse::json($data);
    }

    public function getBuyPricingPlan($id)
    {
        $data = $this->service()->getBuyPricingPlanById($id);

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

        $data = $this->service()->getPricingPlansSummary($input);

        return ApiResponse::json($data);
    }

    public function getTerminalBuyPricingPlans()
    {
        $input = Request::all();

        $data = $this->service()->getBuyPricingPlansSummary($input);

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

    public function postAddBuyPlanGroupedRule($id)
    {
        $input = Request::all();

        $data = $this->service()->addPlanRule($id, $input, null, true);

        return ApiResponse::json($data);
    }

    public function postAddBulkPlanRules()
    {
        $input = Request::all();

        $data = $this->service()->postAddBulkPricingRules($input);

        return ApiResponse::json($data);
    }

    public function postAddBulkBuyPricingRules()
    {
        $input = Request::all();

        $data = $this->service()->postAddBulkBuyPricingRules($input);

        return ApiResponse::json($data);
    }

    public function updatePlanRule($planId, $ruleId)
    {
        $input = Request::all();

        $data = $this->service()->updatePlanRule($planId, $ruleId, $input);

        return ApiResponse::json($data);
    }

    public function updateBuyPricingPlanRule($planId, $ruleId)
    {
        $input = Request::all();

        $data = $this->service()->updatePlanRule($planId, $ruleId, $input, true);

        return ApiResponse::json($data);
    }

    public function deletePlanRuleForce($planId, $ruleId)
    {
        $data = $this->service()->deletePlanRuleForce($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function deleteBuyPlanGroupedRuleForce($planId, $ruleId)
    {
        $data = $this->service()->deleteBuyPlanGroupedRuleForce($planId, $ruleId);

        return ApiResponse::json($data);
    }

    public function getSupportedNetworks()
    {
        $data = $this->service()->getSupportedNetworks();

        return ApiResponse::json($data);
    }

}
