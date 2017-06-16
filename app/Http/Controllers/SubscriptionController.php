<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Plan;
use Request;

class SubscriptionController extends Controller
{
    // -------------------- Plan endpoints start --------------------

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->service('plan')->create($input);

        return ApiResponse::json($plan);
    }

    public function getPlan(string $id)
    {
        $plan = $this->service('plan')->fetch($id);

        return ApiResponse::json($plan);
    }

    public function getPlans()
    {
        $input = Request::all();

        $plans = $this->service('plan')->fetchMultiple($input);

        return ApiResponse::json($plans);
    }

    // -------------------- Plan endpoints end --------------------

    public function postCreateSubscription()
    {
        $input = Request::all();

        $subscription = $this->service('subscription')->create($input);

        return ApiResponse::json($subscription);
    }

    public function getSubscription(string $id)
    {
        $subscription = $this->service('subscription')->fetch($id);

        return ApiResponse::json($subscription);
    }

    public function getSubscriptions()
    {
        $input = Request::input();

        $subscriptions = $this->service('subscription')->fetchMultiple($input);

        return ApiResponse::json($subscriptions);
    }

    public function postCreateAndChargeSubscriptionInvoices()
    {
        $summary = $this->service('subscription')->createAndChargeInvoices();

        return ApiResponse::json($summary);
    }

    public function postRetrySubscriptions()
    {
        $summary = $this->service('subscription')->retrySubscriptions();

        return ApiResponse::json($summary);
    }

    public function postChargeSubscriptionInvoiceManually($invoiceId)
    {
        $subscription = $this->service('subscription')->chargeSubscriptionInvoiceManually($invoiceId);

        return ApiResponse::json($subscription);
    }

    public function postExpireSubscriptions()
    {
        $summary = $this->service('subscription')->expireSubscriptions();

        return ApiResponse::json($summary);
    }
}
