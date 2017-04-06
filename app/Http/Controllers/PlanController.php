<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Plan;
use Request;

class PlanController extends Controller
{
    protected $planService;
    protected $subscriptionService;

    public function __construct(
        Plan\Service $planService,
        Plan\Subscription\Service $subscriptionService)
    {
        parent::__construct();

        $this->planService = $planService;
        $this->subscriptionService = $subscriptionService;
    }

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->planService->create($input);

        return ApiResponse::json($plan);
    }

    public function postCreateSubscription(string $planId)
    {
        $input = Request::all();

        $subscription = $this->subscriptionService->create($input, $planId);

        return ApiResponse::json($subscription);
    }

    public function postCreateSubscriptionInvoices()
    {
        $summary = $this->subscriptionService->createSubscriptionInvoices();

        return ApiResponse::json($summary);
    }

    public function postChargeSubscriptions()
    {
        $summary = $this->subscriptionService->chargeSubscriptions();

        return ApiResponse::json($summary);
    }

    public function postRetryAuthSubscriptions()
    {
        $summary = $this->subscriptionService->retryAuthSubscription();

        return ApiResponse::json($summary);
    }

    public function postExpireSubscriptions()
    {
        $summary = $this->subscriptionService->expireSubscriptions();

        return ApiResponse::json($summary);
    }
}
