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

    // -------------------- Plan endpoints start --------------------

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->planService->create($input);

        return ApiResponse::json($plan);
    }

    public function getPlan(string $id)
    {
        $plan = $this->planService->fetch($id);

        return ApiResponse::json($plan);
    }

    public function getPlans()
    {
        $input = Request::all();

        $plans = $this->planService->fetchMultiple($input);

        return ApiResponse::json($plans);
    }

    // -------------------- Plan endpoints end --------------------

    public function postCreateSubscription()
    {
        $input = Request::all();

        $subscription = $this->subscriptionService->create($input);

        return ApiResponse::json($subscription);
    }

    public function getSubscription(string $id)
    {
        $subscription = $this->subscriptionService->fetch($id);

        return ApiResponse::json($subscription);
    }

    public function getSubscriptions()
    {
        $input = Request::input();

        $subscriptions = $this->subscriptionService->fetchMultiple($input);

        return ApiResponse::json($subscriptions);
    }

    public function postCreateAndChargeSubscriptionInvoices()
    {
        $summary = $this->subscriptionService->createAndChargeInvoices();

        return ApiResponse::json($summary);
    }

    public function postRetrySubscriptions()
    {
        $summary = $this->subscriptionService->retrySubscriptions();

        return ApiResponse::json($summary);
    }

    public function postChargeSubscriptionInvoiceManually($invoiceId)
    {
        $subscription = $this->subscriptionService->chargeSubscriptionInvoiceManually($invoiceId);

        return ApiResponse::json($subscription);
    }

    public function postExpireSubscriptions()
    {
        $summary = $this->subscriptionService->expireSubscriptions();

        return ApiResponse::json($summary);
    }
}
