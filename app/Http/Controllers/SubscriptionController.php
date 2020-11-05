<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;

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

    public function postChargeSubscriptionInvoiceManuallyOld($invoiceId)
    {
        $invoice = $this->service()->chargeSubscriptionInvoiceManuallyOld($invoiceId);

        return ApiResponse::json($invoice);
    }

    public function postChargeSubscriptionInvoiceManually($subscriptionId, $invoiceId)
    {
        $invoice = $this->service()->chargeSubscriptionInvoiceManually($subscriptionId, $invoiceId);

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
        $input = Request::all();

        $subscription = $this->service()->cancelSubscription($subscriptionId, $input);

        return ApiResponse::json($subscription);
    }

    public function getSubscriptionView(string $subscriptionId)
    {
        $error = Request::get('error');

        $data = [];

        if (empty($error) === false)
        {
            $data['error'] = $error;

            $view = 'public.error';
        }
        else
        {
            try
            {
                $data = $this->service()->getSubscriptionViewData($subscriptionId);

                $view = 'subscription.index';
            }
            catch (BaseException $e)
            {
                $data = $e->getError()->toPublicArray();

                $view = 'public.error';
            }
        }

        return View::make($view)
                   ->with('data', $data);
    }

    public function postCancelDueSubscriptions()
    {
        $summary = $this->service()->cancelDueSubscriptions();

        return ApiResponse::json($summary);
    }

    public function postUpdateData(string $subscriptionId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postPaymentProcess(string $subscriptionId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postSubscriptionChargeCycle(string $subscriptionId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postSubscriptionSkipCycle(string $subscriptionId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getOverview()
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postCreateItem()
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getItem(string $itemId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getItems()
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postUpdateItem(string $itemId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function postDeleteItem(string $itemId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function updateSubscription(string $id)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getFetchScheduledChanges(string $id)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getCancelScheduledChanges(string $id)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }

    public function getRevenueBySource(string $sourceId)
    {
        // We are proxying this API request to subserv
        // This should not come here at all, added this method to make sure
        // that api does not break on not finding this method
        throw new LogicException('The request should not have reached here');
    }
}
