<?php

use Http\ApiResponse;
use Models\Merchant;

class PricingController extends BaseController
{
    public function postCreatePricingPlan()
    {
        $input = Input::all();

        $data = (new Pricing\Service)->newPricingPlan($input);

        return ApiResponse::json($data);
    }

    public function getPricingPlan($id)
    {
        $data = (new Pricing\Service)->getPricingPlan($id);

        return ApiResponse::json($data);
    }

    public function getPricingPlans()
    {
        $data = (new Pricing\Service)->getPricingPlans($id);

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
