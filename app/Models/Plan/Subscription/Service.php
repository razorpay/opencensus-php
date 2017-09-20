<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use Razorpay\Trace\Logger as Trace;

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
        (new Validator)->validateInputBeforeBuild($input);

        $planId = $input[Entity::PLAN_ID];

        $customer = null;

        if (empty($input[Entity::CUSTOMER_ID]) === false)
        {
            $customerId = $input[Entity::CUSTOMER_ID];
            $customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        $plan = $this->repo->plan->findByPublicIdAndMerchant($planId, $this->merchant);

        $subscription = $this->core->create($input, $plan, $customer);

        return $subscription->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        $subscription = $this->repo
                             ->subscription
                             ->findByPublicIdAndMerchant($id, $this->merchant);

        return $subscription->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $subscriptions = $this->repo
                              ->subscription
                              ->fetch($input, $this->merchant->getId());

        return $subscriptions->toArrayPublic();
    }

    public function createAndChargeInvoices()
    {
        $subscriptionsToCharge = $this->repo->subscription->getSubscriptionsToCharge();

        $invoicesCreated = $failed = 0;
        $failures = [];

        $biller = (new Biller);

        foreach ($subscriptionsToCharge as $subscription)
        {
            try
            {
                $biller->createInvoiceAndCharge($subscription);

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
        $subscriptionsExpired = 0;
        $failures = [];

        foreach ($subscriptionsToExpire as $subscription)
        {
            try
            {
                $this->core->expireSubscription($subscription);

                $subscriptionsExpired++;
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
            'expired'  => $subscriptionsExpired,
            'failed'   => $failed,
            'failures' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTIONS_EXPIRE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function retrySubscriptions()
    {
        $subscriptionsToRetry = $this->repo->subscription->getSubscriptionsToRetry();

        $success = 0;
        $failures = [];

        foreach ($subscriptionsToRetry as $subscription)
        {
            try
            {
                $this->core->retry($subscription);

                $success++;
            }
            catch (\Exception $ex)
            {
                $failures[] = $subscription->getId();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_RETRY_QUEUE_FAILED,
                    ['susbcription_id' => $subscription->getId()]);
            }
        }

        $summary = [
            'total'                 => $subscriptionsToRetry->count(),
            'queued'                => $success,
            'failed'                => count($failures),
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_RETRY_QUEUE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function cancelSubscription(string $subscriptionId, array $input = []): array
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $subscription = $this->core->cancel($subscription, $input);

        return $subscription->toArrayPublic();
    }

    public function chargeTestSubscription(string $subscriptionId, array $input)
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $this->core->testCharge($subscription, $input);

        return $subscription->toArrayPublic();
    }

    public function cancelDueSubscriptions()
    {
        $subscriptionsToCancel = $this->repo->subscription->getSubscriptionsToCancel();

        $success = 0;
        $failures = [];

        foreach ($subscriptionsToCancel as $subscription)
        {
            try
            {
                $this->core->cancelImmediately($subscription);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SUBSCRIPTION_CANCEL_FAILED,
                    [
                        'subscription_id' => $subscription->getId()
                    ]);

                $failures[] = $subscription->getId();
            }
        }

        $summary = [
            'total'                 => $subscriptionsToCancel->count(),
            'queued'                => $success,
            'failed'                => count($failures),
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CANCEL_DUE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function chargeSubscriptionInvoiceManually(string $invoiceId)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($invoiceId, $this->merchant);

        $subscription = $invoice->subscription;

        $this->trace->info(
            TraceCode::SUBSCRIPTION_INVOICE_MANUAL_CHARGE,
            [
                'invoice_id'        => $invoiceId,
                'subscription_id'   => $subscription->getId(),
                'invoice_status'    => $invoice->getStatus(),
                'subscription'      => $subscription->toArray(),
            ]);

        if ($subscription->isInvoiceManualChargeableStatus() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_NOT_IN_ACTIVE_OR_HALTED_STATE,
                'status',
                [
                    'subscription_id'       => $subscription->getId(),
                    'invoice_id'            => $invoice->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);
        }

        if ($invoice->isIssued() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_INVOICE_CANNOT_BE_CHARGED,
                null,
                [
                    'invoice_id'        => $invoiceId,
                    'subscription_id'   => $subscription->getId(),
                    'invoice_status'    => $invoice->getStatus(),
                ]);
        }

        $capture = $this->shouldCaptureInvoice($invoice, $subscription);

        $options = [
            'manual' => true,
            'queue'  => false,
        ];

        if ($capture === true)
        {
            $this->core->retryCapture($subscription, $invoice, $options);
        }
        else
        {
            $this->core->charge($subscription, $invoice, $options);
        }

        // Subscription is charged by passing a payload of reference ids
        // to a helper class (Charge). We use a payload, because for cron
        // charges, we queue the job. We don't for manual though, so
        // reloading at this stage ensures that updated values are returned.
        $this->repo->reload($invoice);

        return $invoice->toArrayPublic();
    }

    /**
     * Whether an invoice should be captured can be determined just by seeing if there
     * are any authorized payments. An authorized payment can only exist if the amount
     * has already been validated, so this can be captured.
     *
     * We can't use subscription errorStatus, as merchant may be manually charging an
     * older invoice, and subscription attributes may since have been updated.
     *
     * @param Invoice\Entity $invoice
     * @param Entity         $subscription
     *
     * @return bool
     * @throws LogicException
     */
    protected function shouldCaptureInvoice(Invoice\Entity $invoice, Entity $subscription)
    {
        $payments = $invoice->payments;

        if ($payments->count() === 0)
        {
            return false;
        }

        $authorizedPayments = $payments->where(Payment\Entity::STATUS, '=', Payment\Status::AUTHORIZED);

        $authorizedPaymentsCount = $authorizedPayments->count();

        if ($authorizedPaymentsCount === 0)
        {
            return false;
        }
        else if ($authorizedPaymentsCount === 1)
        {
            return true;
        }
        else
        {
            throw new LogicException(
                'Invoice cannot have more than one authorized payment.',
                null,
                [
                    'invoice_id'            => $invoice->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'auth_payments_count'   => $authorizedPaymentsCount
                ]);
        }
    }
}
