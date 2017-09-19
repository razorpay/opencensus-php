<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;

class SubscriptionController extends Controller
{
    // -------------------- Plan endpoints start --------------------

    public function postCreatePlan()
    {
        $input = Request::all();

        $plan = $this->service(E::PLAN)->create($input);

        return ApiResponse::json($plan);
    }

    public function getPlan(string $planId)
    {
        $plan = $this->service(E::PLAN)->fetch($planId);

        return ApiResponse::json($plan);
    }

    public function getPlans()
    {
        $input = Request::all();

        $plans = $this->service(E::PLAN)->fetchMultiple($input);

        return ApiResponse::json($plans);
    }

    // -------------------- Plan endpoints end ----------------------

    // -------------------- Addon endpoints start -------------------

    public function postAddonForSubscription(string $subscriptionId)
    {
        $input = Request::all();

        $addon = $this->service(E::ADDON)->create($input, $subscriptionId);

        return ApiResponse::json($addon);
    }

    public function getAddon(string $addonId)
    {
        $addon = $this->service(E::ADDON)->fetch($addonId);

        return ApiResponse::json($addon);
    }

    public function getAddons()
    {
        $input = Request::all();

        $addon = $this->service(E::ADDON)->fetchMultiple($input);

        return ApiResponse::json($addon);
    }

    public function getDueAddonsForSubscription(string $subscriptionId)
    {
        $addons = $this->service(E::ADDON)->fetchDueAddonsForSubscription($subscriptionId);

        return ApiResponse::json($addons);
    }

    public function deleteAddon($addonId)
    {
        $addon = $this->service(E::ADDON)->delete($addonId);

        return ApiResponse::json($addon);
    }

    // -------------------- Addon endpoints end -------------------

    public function postCreateSubscription()
    {
        $input = Request::all();

        $subscription = $this->service()->create($input);

        return ApiResponse::json($subscription);
    }

    public function getSubscription(string $subscriptionId)
    {
        $subscription = $this->service()->fetch($subscriptionId);

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

    public function getSubscriptionView(string $subscriptionId)
    {
        $error = Request::get('error');

        try
        {
            $data = $this->service()->getSubscriptionViewData($subscriptionId);
        }
        catch (BaseException $e)
        {
            $data = $e->getError()->toPublicArray();
        }

        if (empty($error) === false)
        {
            $data['error'] = $error;
        }

        $view = 'subscription.index';

        //
        // This route gets called as part of callback_url during payment
        // creation when pop-up doesn't work. We send the request parameters
        // to blade and there JS code handles invoice.callback_url.
        //

        $data['request_params'] = Request::all();

        return View::make($view)
            ->with('data', $data);
    }
}
