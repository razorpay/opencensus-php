<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\Entity as E;

class SubscriptionController extends Controller
{
    // -------------------- Plan endpoints start --------------------

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->service(E::PLAN)->create($input);

        return ApiResponse::json($plan);
    }

    public function getPlan(string $id)
    {
        $plan = $this->service(E::PLAN)->fetch($id);

        return ApiResponse::json($plan);
    }

    public function getPlans()
    {
        $input = Request::all();

        $plans = $this->service(E::PLAN)->fetchMultiple($input);

        return ApiResponse::json($plans);
    }

    // -------------------- Plan endpoints end --------------------

    public function postCreateSubscription()
    {
        $input = Request::all();

        $subscription = $this->service()->create($input);

        return ApiResponse::json($subscription);
    }

    public function getSubscription(string $id)
    {
        $subscription = $this->service()->fetch($id);

        return ApiResponse::json($subscription);
    }

    public function getSubscriptions()
    {
        $input = Request::input();

        $subscriptions = $this->service()->fetchMultiple($input);

        return ApiResponse::json($subscriptions);
    }

    public function postCreateAndChargeSubscriptionInvoices()
    {
        $summary = $this->service()->createAndChargeInvoices();

        return ApiResponse::json($summary);
    }

    public function postRetrySubscriptions()
    {
        $summary = $this->service()->retrySubscriptions();

        return ApiResponse::json($summary);
    }

    public function postChargeSubscriptionInvoiceManually($invoiceId)
    {
        $invoice = $this->service()->chargeSubscriptionInvoiceManually($invoiceId);

        return ApiResponse::json($invoice);
    }

    public function postTestChargeSubscription($subscriptionId)
    {
        $input = Request::input();

        $subscription = $this->service()->chargeTestSubscription($subscriptionId, $input);

        return ApiResponse::json($subscription);
    }

    public function postExpireSubscriptions()
    {
        $summary = $this->service()->expireSubscriptions();

        return ApiResponse::json($summary);
    }

    public function postCancelSubscription(string $subscriptionId)
    {
        $subscription = $this->service()->cancelSubscription($subscriptionId);

        return ApiResponse::json($subscription);
    }
}
