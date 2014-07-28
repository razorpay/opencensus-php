<?php

use Http\ApiResponse;
use Models\Pricing;

class PricingController extends BaseController
{
    public function postCreatePricingPlan()
    {
        $input = Input::all();

        $data = (new Pricing\Service)->createPricingPlan($input);

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

    public function postAddPricingPlanRule($id)
    {
        $input = Input::all();

        $data = (new Pricing\Service)->addPricingPlanRule($id, $input);

        return ApiResponse::json($data);
    }

    public function postReplacePricingPlanRule($id)
    {
        ;
    }

    public function deletePricingPlanRule($id)
    {
        ;
    }

    public function deletePricingPlan($id)
    {
        ;
    }
}
