<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Plan;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input) : array
    {
        if (empty($input[Entity::CUSTOMER_ID]) === true)
        {
            throw new BadRequestValidationFailureException(
                'customer_id should be sent in the request to create a subscription.',
                'customer_id');
        }

        if (empty($input[Entity::PLAN_ID]) === true)
        {
            throw new BadRequestValidationFailureException(
                'plan_id should be sent in the request to create a subscription.',
                'plan_id');
        }

        $customerId = $input[Entity::CUSTOMER_ID];
        $planId = $input[Entity::PLAN_ID];

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
        $plan = $this->repo->plan->findByPublicIdAndMerchant($planId, $this->merchant);

        $subscription = $this->core->create($input, $plan, $customer);

        return $subscription->toArrayPublic();
    }

    public function createAndChargeInvoices()
    {
        $subscriptionsToCharge = $this->repo->subscription->getSubscriptionsToCharge();

        $invoicesCreated = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToCharge as $subscription)
        {
            try
            {
                (new Billing)->createInvoiceAndCharge($subscription);
                $this->core->createInvoiceAndCharge($subscription);

                $invoicesCreated++;
            }
            catch (\Exception $ex)
            {
                $failed++;
                $failures[] = $subscription->getId();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_CREATE_INVOICE_FAILED,
                    [
                        'subscription_id' => $subscription->getId()
                    ]);
            }
        }

        $summary = [
            'total'             => $subscriptionsToCharge->count(),
            'invoices_created'  => $invoicesCreated,
            'failed'            => $failed,
            'failures'          => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CREATE_INVOICE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function expireSubscriptions()
    {
        $subscriptionsToExpire = $this->repo->subscription->getSubscriptionsToExpire();

        $failed = 0;
        $failures = [];

        foreach ($subscriptionsToExpire as $subscription)
        {
            try
            {
                $this->core->expireSubscription($subscription);
            }
            catch (\Exception $ex)
            {
                $failed++;
                $failures[] = $subscription->getId();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_EXPIRE_FAILED,
                    [
                        'subscription_id' => $subscription->getId()
                    ]);
            }
        }

        $summary = [
            'total'    => $subscriptionsToExpire->count(),
            'failed'   => $failed,
            'failures' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTIONS_EXPIRE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function retryAuthSubscription()
    {
        $subscriptionsToRetry = $this->repo->subscription->getSubscriptionsToRetry();

        $queued = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToRetry as $subscription)
        {
            try
            {
                $this->core->retry($subscription);
                $queued++;
            }
            catch (\Exception $ex)
            {
                $failed++;
                $failures[] = $subscription->getId();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_RETRY_QUEUE_FAILED,
                    ['susbcription_id' => $subscription->getId()]);
            }
        }

        $summary = [
            'total' => $subscriptionsToRetry->count(),
            'queued' => $queued,
            'failed' => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_RETRY_QUEUE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function chargeSubscriptionInvoiceManually(string $subscriptionId, string $invoiceId)
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $invoice = $this->repo->invoice->findByPublicIdAndSubscription($invoiceId, $subscription);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_INVOICE_MANUAL_CHARGE,
            [
                'invoice_id' => $invoiceId,
                'subscription_id' => $subscriptionId,
                'invoice_status' => $invoice->getStatus(),
                'subscription' => $subscription->toArray(),
            ]);

        if ($invoice->isIssued() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_INVOICE_NOT_IN_ISSUED,
                null,
                [
                    'invoice_id' => $invoiceId,
                    'subscription_id' => $subscriptionId,
                    'invoice_status' => $invoice->getStatus(),
                ]);
        }

        (new Core)->charge($subscription, $invoice, true);
    }
}
