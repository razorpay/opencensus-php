<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Plan;
use Request;

class PlanController extends Controller
{
    protected $planService;
    protected $subscriptionService;

    public function __construct()
    {
        parent::__construct();

        $this->planService = new Plan\Service;
        $this->subscriptionService = new Plan\Subscription\Service;
    }

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->planService->create($input);

        return ApiResponse::json($plan);
    }

    public function postCreateSubscription($planId)
    {
        $input = Request::all();

        $subscription = $this->subscriptionService->create($input, $planId);

        return ApiResponse::json($subscription);
    }

    public function postChargeSubscriptions()
    {
        $summary = $this->subscriptionService->chargeSubscriptions();

        return ApiResponse::json($summary);
    }
}