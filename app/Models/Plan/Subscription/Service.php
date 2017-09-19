<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Plan;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice;
use RZP\Models\Payment;
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

        $success = $failed = 0;
        $failures = [];

        foreach ($subscriptionsToRetry as $subscription)
        {
            $errorStatus = $subscription->getErrorStatus();

            if ($errorStatus === null)
            {
                throw new LogicException(
                    'Only subscriptions with an error status should be retried!',
                    null,
                    [
                        'subscription_id' => $subscription->getId(),
                    ]);
            }

            try
            {
                $this->core->retry($subscription, $errorStatus);

                $success++;
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
            'total'                 => $subscriptionsToRetry->count(),
            'queued'                => $success,
            'failed'                => $failed,
            'failure_subscriptions' => $failures,
        ];

        $this->trace->info(
            TraceCode::SUBSCRIPTION_RETRY_QUEUE_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $subscription = $this->core->cancel($subscription);

        return $subscription->toArrayPublic();
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

        if (in_array($subscription->getStatus(), Status::$invoiceManualChargeableStatuses, true) === false)
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

        if (($invoice->isIssued() === false) or
            ($invoice->getSubscriptionStatus() !== Invoice\Status::HALTED))
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

        if ($capture === true)
        {
            return $this->core->retryCapture($subscription, $invoice, true);
        }
        else
        {
            return $this->core->charge($subscription, $invoice, true);
        }
    }

    public function getSubscriptionViewData(string $subscriptionId): array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if (($routeName === 'subscription_view_test') or
            ($routeName === 'subscription_view_test_post'))
        {
            $mode = Constants\Mode::TEST;
        }
        else
        {
            $mode = Constants\Mode::LIVE;
        }

        \Database\DefaultConnection::set($mode);

        $this->app['basicauth']->setMode($mode);

        $subscription = $this->repo->subscription->findByPublicId($subscriptionId);

        $subscription->getValidator()->validateSubscriptionViewable();

        return (new ViewDataSerializer($subscription))->get();
    }

    /**
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
