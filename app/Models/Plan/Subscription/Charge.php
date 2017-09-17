<?php

namespace RZP\Models\Plan\Subscription;

use App;

use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Schedule\Task;

class Charge extends Base\Core
{
    protected $app;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var Payment\Processor\Processor
     */
    protected $processor;

    protected $mutex;

    /**
     * Maximum authorization attempts allowed for subscription charge.
     *
     * TODO: Make this merchant configurable.
     */
    const MAX_AUTH_ATTEMPTS = 4;

    const MUTEX_LOCK_TIMEOUT = 120;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * This is called via the queue to initiate the actual
     * charge process.
     *
     * @param $data
     *
     * @return bool
     * @throws LogicException
     */
    public function fireCharge(array $data)
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_CHARGE_QUEUE_PAYLOAD_RECEIVED,
            $data);

        //
        // This is required so that the mode and the db connection are set.
        // Since this is via queue, this will not set on its own.
        //
        $this->app['basicauth']->checkAndSetKeyId($data['key_id']);

        $subscription = $this->repo->subscription->findOrFail($data['subscription_id']);

        $invoice = $this->repo->invoice->findOrFail($data['invoice_id']);

        //
        // We should not go through the failure flow if the request was
        // done manually from the dashboard or something.
        // The failure flow should be done only if the system retries are
        // going on. Otherwise, it'll create an inconsistency around
        // error_codes, auth_attempts, etc.., since anyone can retry any number
        // of times manually from multiple places.
        //
        $manual = $data['manual'];

        list($valid, $traceCode) = $subscription->getValidator()->validateSubscriptionChargeable($invoice, $manual);

        if ($valid === false)
        {
            $this->trace->critical(
                $traceCode,
                [
                    'invoice_id'            => $invoice->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);

            return false;
        }

        if ($manual === false)
        {
            //
            // This needs to be incremented every time we attempt to authorize a payment.
            // Using this attribute, we would decide whether to retry or not.
            //
            $subscription->incrementAuthAttempts();
        }

        $payment = null;
        $exception = false;

        try
        {
            $payment = $this->authorizePayment($subscription, $data['recurring_payload']);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $exception = true;
        }

        //
        // If it's already captured, `handleCaptureSuccess` would have been
        // called in the auto capture flow itself.
        // Hence, we don't have to handle for captured successfully flow, here.
        //
        // Also, the order of the conditions matter! Think!
        //
        if (($exception === true) or
            ($payment->isCaptured() === false))
        {
            if ($manual === false)
            {
                $captureFailure = true;

                if ($exception === true)
                {
                    $captureFailure = false;
                }

                $this->handleAuthorizationOrCaptureFailure($subscription, $invoice, $payment, $captureFailure);
            }

            return false;
        }

        return true;
    }

    /**
     * @param Entity         $subscription
     * @param Payment\Entity $capturedPayment
     * @param Invoice\Entity $invoice
     */
    public function handleCaptureSuccess(
        Entity $subscription,
        Payment\Entity $capturedPayment,
        Invoice\Entity $invoice)
    {
        $task = $subscription->task;

        //
        // Cannot move this to a variable because the instance values change later.
        //
        $this->trace->info(
            TraceCode::SUBSCRIPTION_BEFORE_CAPTURE_UPDATE,
            [
                'subscription_details' => $subscription->toArray(),
                'invoice_details'      => $invoice->toArray(),
                'task_details'         => $task->toArray(),
            ]);

        $oldStatus = $subscription->getStatus();

        //
        // If the subscription is in a terminal state,
        // we can NOT change the status!
        //
        if ($subscription->isTerminalStatus() === false)
        {
            //
            // Not sending webhook here because the transaction might fail later
            // in the flow. Will be sending it after the transaction is committed.
            //
            $subscription->setStatus(Status::ACTIVE);
        }

        $this->trace->info(
            TraceCode::SUBSCRIPTION_STATUS_ACTIVE,
            [
                'old_status'        => $oldStatus,
                'new_status'        => $subscription->getStatus(),
                'subscription_id'   => $subscription->getId(),
                'payment_id'        => $capturedPayment->getId(),
            ]);

        //
        // We should update next_run_at only if the latest invoice is
        // being charged, except in the case of halted.
        //
        // Irrespective of the subscription status, we should never update
        // the next_run_at or ended_at if it's not the latest invoice that
        // just got charged successfully.
        //
        // In case of halted, everything is an old invoice only. If a halted
        // subscription is being charged, it just means that it's a manual charge
        // attempt of an old invoice. If it's an old invoice, next_run_at
        // shouldn't be changed. Old invoice is independent of the current
        // cycle going on.
        // The one difference here is, though we don't update any cycles, we always
        // mark the subscription as activated. Since we now have a good card.
        // In halted, there's no need to update next_run_at, as the regular
        // charge cron has already updated it.
        //
        // In case the subscription is in pending state, irrespective of whether
        // a manual charge is being made or automated charge is being made,
        // we will always update the next_run_at and also set it as completed
        // if this was the last charge of the subscription.
        //
        // Active and authenticated are pretty self-explanatory, in the sense
        // that it's a normal charge. The first charge of the cycle succeeded
        // and now we need to update next_run_at and mark completed as required.
        //
        // If the subscription is in completed, cancelled or expired state, it means
        // that this is a manual charge attempt of an old invoice. Since it's a terminal
        // state, the invoice must be old. If it's an old invoice, we should not update
        // anything at all.
        //
        if ((($oldStatus === Status::AUTHENTICATED) or
             ($oldStatus === Status::ACTIVE) or
             ($oldStatus === Status::PENDING)) and
            ($subscription->isLatestInvoiceForSubscription($invoice) === true))
        {
            $task->updateForSubscription($this->mode);

            //
            // Even though we are updating it in the invoice now,
            // we will be keeping the current billing cycle period
            // in subscriptions also.
            //
            $this->setEndedAtIfApplicable($subscription);
        }

        $subscription->resetErrorFields();

        $subscription->incrementPaidCount();

        //
        // Only when a subscription moves from authenticated to active, we should
        // set `activated_at`. Subscription can move to active through many ways.
        //
        if ($oldStatus === Status::AUTHENTICATED)
        {
            $subscription->setActivatedAt($capturedPayment->getCaptureTimestamp());
        }

        $this->saveAndFireWebhooksOnCaptureSuccess($subscription, $task, $invoice, $capturedPayment, $oldStatus);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_AFTER_CAPTURE_UPDATE,
            [
                'subscription_details' => $subscription->toArray(),
                'invoice_details' => $invoice->toArray(),
                'task_details' => $task->toArray()
            ]);

        //
        // This must be sent after saving the invoice and subscription
        // to ensure that we don't send an email when we were not able
        // to charge the subscription.
        //
        // $this->sendInvoiceEmail($invoice);
    }

    /**
     * @param Entity              $subscription
     * @param Invoice\Entity      $invoice
     * @param Payment\Entity|null $payment
     * @param bool                $captureFailure
     *
     * @throws LogicException
     */
    public function handleAuthorizationOrCaptureFailure(
        Entity $subscription,
        Invoice\Entity $invoice,
        Payment\Entity $payment = null,
        bool $captureFailure = false)
    {
        $traceCode = TraceCode::SUBSCRIPTION_PAYMENT_AUTHORIZE_FAILED;
        $errorStatus = Status::AUTH_FAILURE;

        if ($captureFailure === true)
        {
            $traceCode = TraceCode::SUBSCRIPTION_PAYMENT_CAPTURE_FAILED;
            $errorStatus = Status::CAPTURE_FAILURE;
        }

        $this->trace->critical(
            $traceCode,
            ['subscription_id'   => $subscription->getId()]);

        $subscription->setErrorStatus($errorStatus);

        $authAttempts = $subscription->getAuthAttempts();

        $task = $subscription->task;

        if ($authAttempts < self::MAX_AUTH_ATTEMPTS)
        {
            // Charge has failed an acceptable number of times
            $subscription->setStatus(Status::PENDING);

            // Update task by a day
            $task->updateForSubscription($this->mode, true);

            // As long as retries are going on, we don't
            // mark the subscription as completed.
        }
        else if ($authAttempts === self::MAX_AUTH_ATTEMPTS)
        {
            //
            // TODO: Make this merchant configurable. It can either
            // go into halted or cancelled state.
            //
            $subscription->setStatus(Status::HALTED);
            $invoice->setSubscriptionStatus(Invoice\Status::HALTED);

            //
            // Update task by a full plan period
            //
            $task->updateForSubscription($this->mode);

            $this->setEndedAtIfApplicable($subscription);
        }
        else
        {
            throw new LogicException(
                'Should not have reached here. Auth Attempts cannot be greater than ' . self::MAX_AUTH_ATTEMPTS,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'auth_attempts'     => $authAttempts,
                ]);
        }

        $this->saveAndFireWebhooksOnFailure($subscription, $invoice, $payment);
    }

    /**
     * Count the number of invoices generated for the subscription that were part of
     * the plan (so exclude upfront amounts with future start_at). When this count
     * is equal to the total count for the subscription, it is complete.
     * We also mark charge_at to null at this stage.
     *
     * @param Entity $subscription
     *
     * @return bool Returns true if marked as completed. Else, false.
     */
    public function setEndedAtIfApplicable(Entity $subscription)
    {
        $planChargeInvoiceCount = $subscription->getPlanChargeInvoicesCount();

        //
        // Ideally, planChargeInvoiceCount would never be greater than the
        // subscription's total_count. `>` is simply there.
        //
        if ($planChargeInvoiceCount >= $subscription->getTotalCount())
        {
            //
            // If the last charge of the subscription is on 20th August,
            // ideally, the end date would be 20th August only.. but,
            // the subscription would go on until 20th October.
            // Not sure whether to set the ended_at as 20th August
            // or 20th October. For now, setting it as 20th August.
            // Current period in subscriptions and invoices
            // would be 20th August to 20th October.
            //
            $subscription->setEndedAt($subscription->getCurrentStart());

            $subscription->setStatus(Status::COMPLETED);

            return true;
        }

        return false;
    }

    protected function saveAndFireWebhooksOnFailure(
        Entity $subscription,
        Invoice\Entity $invoice,
        Payment\Entity $payment = null)
    {
        $task = $subscription->task;

        $updatedStatus = $subscription->getStatus();

        $core = new Core;

        //
        // If the latest status of the subscription is halted, just save and fire the webhook.
        // If the latest status of the subscription is completed, it means that it was moved
        // from halted to completed; since the only time this function would be called is when
        // a charge has failed. If a charge fails, it can either move from active to pending
        // or pending to pending or pending to halted. If it moves to pending, we would not
        // mark it as completed. Only if it moves to halted, we would mark the subscription as
        // completed as required.
        //

        if ($updatedStatus === Status::PENDING)
        {
            $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

            $core->fireWebhookForStatusUpdate($subscription, Status::PENDING, $payment);
        }
        else if ($updatedStatus === Status::HALTED)
        {
            $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

            $core->fireWebhookForStatusUpdate($subscription, Status::HALTED, $payment);
        }
        else if ($updatedStatus === Status::COMPLETED)
        {
            $currentEndedAt = $subscription->getEndedAt();

            $subscription->setStatus(Status::HALTED);
            $subscription->setEndedAt(null);

            $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

            $core->fireWebhookForStatusUpdate($subscription, Status::HALTED, $payment);

            $subscription->setStatus(Status::COMPLETED);
            $subscription->setEndedAt($currentEndedAt);

            $this->repo->saveOrFail($subscription);

            $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED, $payment);
        }
        else
        {
            // TODO: Throw an exception. At this stage, the subscription status
            // should always be either pending, halted or completed only.
        }
    }

    protected function saveAndFireWebhooksOnCaptureSuccess(
        Entity $subscription,
        Task\Entity $task,
        Invoice\Entity $invoice,
        Payment\Entity $capturedPayment,
        string $oldStatus)
    {
        $updatedStatus = $subscription->getStatus();

        $core = new Core;

        //
        // Different cases:
        //  - completed -> completed [fire charge webhook]
        //  - authenticated -> active -> completed [fire charge, active and completed webhooks]
        //  - active -> active -> completed [fire charge and completed webhooks]
        //  - pending -> active -> completed [fire charge, active and completed webhooks]
        //
        //  - active -> active [fire charge webhook]
        //  - pending -> active [fire charge and active webhooks]
        //  - halted -> active [fire charge and active webhooks]
        //  - authenticated -> active [fire charge and active webhooks]
        //

        switch ($updatedStatus)
        {
            case Status::COMPLETED:
                //
                // For a terminal state, we don't update the status.
                // If the subscription is moving from completed -> completed, we don't have to fire the
                // webhook, since the merchant already knows that the subscription is in completed state.
                //
                if ($oldStatus === Status::COMPLETED)
                {
                    $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                    $core->eventSubscriptionCharged($subscription, $capturedPayment);
                }
                //
                // Only from either authenticated, active, pending, the subscription can move to completed.
                // From halted, it can never move to completed.
                // But, in all of these cases, the subscription would first move to active and THEN to completed.
                // Hence, in these cases we have to fire both active and completed webhooks.
                // But, in case of active -> active -> completed, we don't have to fire the active webhook
                // and we can directly fire the completed webhook.
                //
                else if (in_array($oldStatus, [Status::AUTHENTICATED, Status::ACTIVE, Status::PENDING], true) === true)
                {
                    $currentEndedAt = $subscription->getEndedAt();

                    $subscription->setStatus(Status::ACTIVE);
                    $subscription->setEndedAt(null);

                    $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                    $core->eventSubscriptionCharged($subscription, $capturedPayment);

                    if ($oldStatus !== Status::ACTIVE)
                    {
                        $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE, $capturedPayment);
                    }

                    $subscription->setStatus(Status::COMPLETED);
                    $subscription->setEndedAt($currentEndedAt);

                    $this->repo->saveOrFail($subscription);

                    $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED);
                }

                break;
            //
            case Status::ACTIVE:
                //
                // If the latest status is active, we will definitely not be required to
                // fire completed webhook. We only need to fire the active webhook.
                //

                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->eventSubscriptionCharged($subscription, $capturedPayment);

                //
                // If we are moving subscription from active to active, we don't have
                // to fire the active webhook, since the merchant already knows
                // that this is in active state.
                //
                if ($oldStatus !== Status::ACTIVE)
                {
                    $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE, $capturedPayment);
                }

                break;
            default:
                // TODO: Throw an exception
        }
    }

    protected function saveSubscriptionAndInvoiceAndTask(
        Entity $subscription,
        Task\Entity $task,
        Invoice\Entity $invoice)
    {
        $this->repo->transaction(
            function() use ($subscription, $task, $invoice)
            {
                $this->repo->saveOrFail($task);
                $this->repo->saveOrFail($invoice);
                $this->repo->saveOrFail($subscription);
            });
    }

    protected function authorizePayment(Entity $subscription, array $recurringPayload)
    {
        $processor = new Payment\Processor\Processor($subscription->merchant);

        $recurringPayment = $processor->process($recurringPayload);

        $authorizedPayment = $this->repo->payment->findByPublicId($recurringPayment['razorpay_payment_id']);

        return $authorizedPayment;
    }

    // TODO: Implement Slack Logging! Pliss
}
