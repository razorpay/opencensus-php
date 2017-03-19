<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
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

    public function create(array $input, string $planId)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
        $plan = $this->repo->plan->findByPublicIdAndMerchant($planId, $this->merchant);

        $subscription = $this->core->create($input, $plan, $customer);

        return $subscription->toArrayPublic();
    }

    public function createSubscriptionInvoices()
    {
        $subscriptionsToCharge = $this->repo->subscription->getSubscriptionsToCharge();

        $invoicesCreated = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToCharge as $subscription)
        {
            try
            {
                $this->core->createInvoiceBeforeCharge($subscription);

                $invoicesCreated += 1;
            }
            catch (\Exception $ex)
            {
                $failed += 1;
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
            'total' => $subscriptionsToCharge->count(),
            'invoices_created' => $invoicesCreated,
            'failed' => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CREATE_INVOICE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function chargeSubscriptions()
    {
        $invoicesToCharge = $this->repo->invoice->getSubscriptionInvoicesToCharge();

        $queued = $failed = 0;
        $failures = [];

        foreach ($invoicesToCharge as $invoice)
        {
            try
            {
                $subscription = $invoice->subscription;

                $this->core->charge($subscription, $invoice);
                $queued += 1;
            }
            catch (\Exception $ex)
            {
                $failed += 1;
                $failures[] = $subscription->getId();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_CHARGE_QUEUE_FAILED,
                    [
                        'subscription_id' => $subscription->getId()
                    ]);
            }
        }

        $summary = [
            'total' => $invoicesToCharge->count(),
            'queued' => $queued,
            'failed' => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CHARGE_QUEUE_SUMMARY,
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
                $queued += 1;
            }
            catch (\Exception $ex)
            {
                $failed += 1;
                $failures[] = $subscription->getId();

                $this->trace->traceException($ex);

                $this->trace->error(
                    TraceCode::SUBSCRIPTION_RETRY_QUEUE_FAILED,
                    [
                        'subscription_id' => $subscription->getId(),
                    ]);
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
}
