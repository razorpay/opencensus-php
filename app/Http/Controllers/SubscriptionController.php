<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Constants\Entity;
use Request;

class SubscriptionController extends Controller
{
    // -------------------- Plan endpoints start --------------------

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->service(Entity::PLAN)->create($input);

        return ApiResponse::json($plan);
    }

    public function getPlan(string $id)
    {
        $plan = $this->service(Entity::PLAN)->fetch($id);

        return ApiResponse::json($plan);
    }

    public function getPlans()
    {
        $input = Request::all();

        $plans = $this->service(Entity::PLAN)->fetchMultiple($input);

        return ApiResponse::json($plans);
    }

    // -------------------- Plan endpoints end --------------------

    // -------------------- Addon endpoints start ---------------------

    public function postAddon($subscriptionId)
    {
        $input = Request::all();

        $addon = $this->service(Entity::ADDON)->create($subscriptionId, $input);

        return ApiResponse::json($addon);
    }

    public function getAddon($id)
    {
        $addon = $this->service(Entity::ADDON)->fetch($id);

        return ApiResponse::json($addon);
    }

    public function getAddons($subscriptionId)
    {
        $input = Request::all();

        $addon = $this->service(Entity::ADDON)->fetchMultiple($input);

        return ApiResponse::json($addon);
    }

    public function fetchDueAddons($subscriptionId)
    {
        $addons = $this->service(Entity::ADDON)->fetchDueAddons($subscriptionId);

        return ApiResponse::json($addons);
    }

    public function deleteAddon($id)
    {
        $addon = $this->service(Entity::ADDON)->delete($id);

        return ApiResponse::json($addon);
    }

    // -------------------- Addon endpoints start ---------------------

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
        $subscription = $this->service()->chargeSubscriptionInvoiceManually($invoiceId);

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
