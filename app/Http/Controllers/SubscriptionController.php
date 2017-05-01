<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Plan;
use Request;

class SubscriptionController extends Controller
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

    public function postCreateAndChargeSubscriptionInvoices()
    {
        $summary = $this->subscriptionService->createAndChargeInvoices();

        return ApiResponse::json($summary);
    }

    public function postRetryAuthSubscriptions()
    {
        $summary = $this->subscriptionService->retryAuthSubscription();

        return ApiResponse::json($summary);
    }

    public function postChargeSubscriptionInvoiceManually($subId, $invId)
    {
        $subscription = $this->subscriptionService->chargeSubscriptionInvoiceManually($subId, $invId);

        return ApiResponse::json($subscription);
    }

    public function postExpireSubscriptions()
    {
        $summary = $this->subscriptionService->expireSubscriptions();

        return ApiResponse::json($summary);
    }
}
