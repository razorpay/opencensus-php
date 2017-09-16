<?php

namespace RZP\Models\Plan\Subscription;

use App;

use RZP\Base\RepositoryManager;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

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
        $captureFailure = true;

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
        if ((($payment->isCaptured() === false) or
             ($exception === true)) and
            ($manual === false))
        {
            if ($exception === false)
            {
                $captureFailure = true;
            }

            $this->handleAuthorizationOrCaptureFailure($subscription, $invoice, $payment, $captureFailure);

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

        $this->trace->info(
            TraceCode::SUBSCRIPTION_STATUS_ACTIVE,
            [
                'old_status'        => $subscription->getStatus(),
                'new_status'        => Status::ACTIVE,
                'subscription_id'   => $subscription->getId(),
                'payment_id'        => $capturedPayment->getId(),
            ]);

        $oldStatus = $subscription->getStatus();

        //
        // Charge_At is to be updated only after charge of current invoices, and not after
        // manual charge of an older invoice. Also, for halted subscriptions, reaching here
        // means an older invoice is being manually charged. There again, no need to update
        // charge_at, as the regular charge cron has already updated it.
        //
        // Subscription Status | Which invoice | Should Charge_at be updated?
        // ----------------------------------------------------------------------
        //        Active       |   Latest      |         Yes
        //        Active       |   Older       |         No
        //        Pending      |   Latest      |         Yes
        //        Pending      |   Older       |         No
        //        Halted       |   Older       |         No
        //
        //
        if (($subscription->isLatestInvoiceForSubscription($invoice) === true) and
            (($subscription->getStatus() === Status::ACTIVE) or
             ($subscription->getStatus() === Status::PENDING)))
        {
            $task->updateForSubscription($this->mode);

            //
            // Even though we are updating it in the invoice now,
            // we will be keeping the current billing cycle period
            // in subscriptions also.
            //
            $this->setEndedAtIfApplicable($subscription);
        }

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

        $subscription->resetErrorFields();

        $subscription->incrementPaidCount();

        if ($oldStatus === Status::AUTHENTICATED)
        {
            $subscription->setActivatedAt($capturedPayment->getCaptureTimestamp());
        }

        $this->repo->transaction(
            function() use ($task, $invoice, $subscription)
            {
                $this->repo->saveOrFail($task);
                $this->repo->saveOrFail($invoice);
                $this->repo->saveOrFail($subscription);
            });

        $this->trace->info(
            TraceCode::SUBSCRIPTION_AFTER_CAPTURE_UPDATE,
            [
                'subscription_details' => $subscription->toArray(),
                'invoice_details' => $invoice->toArray(),
                'task_details' => $task->toArray()
            ]);

        $this->fireWebhooksOnCaptureSuccess($subscription, $capturedPayment, $oldStatus);

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

            // TODO: Handle completed
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
            $this->updateForSubscription($subscription);

            //
            // TODO: Should we be resetting auth_attempts here? We don't actually use
            // it anywhere, but we do need to decide what we want the merchant to see.
            //

            // TODO: Handle completed
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

        $this->repo->transaction(function() use ($invoice, $subscription)
        {
            $this->repo->saveOrFail($invoice);
            $this->repo->saveOrFail($subscription);
            $this->repo->saveOrFail($subscription->task);
        });

        (new Core)->fireWebhookForStatusUpdate($subscription, $subscription->getStatus(), $payment);
    }

    /**
     * Count the number of invoices generated for the subscription that were part of
     * the plan (so exclude upfront amounts with future start_at). When this count
     * is equal to the total count for the subscription, it is complete.
     * We also mark charge_at to null at this stage.
     *
     * @param Entity $subscription
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
        }
    }

    protected function fireWebhooksOnCaptureSuccess(
        Entity $subscription,
        Payment\Entity $capturedPayment,
        string $oldStatus)
    {
        $core = new Core;

        //
        // In case the old status was a terminal status
        // (completed, expired, cancelled), we wouldn't
        // have marked the subscription as active.
        // Hence, we shouldn't fire any webhook.
        //
        if (($oldStatus !== Status::ACTIVE) and
            ($subscription->isActive() === true))
        {
            $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE, $capturedPayment);
        }

        $core->eventSubscriptionCharged($subscription, $capturedPayment);

        // TODO: It will not fire activated webhook because we would have marked it
        // as active from pending and then to completed. So, subscription status at
        // this point would be completed.

        //
        // If the merchant manually charges a completed subscription, the subscription status
        // would still be completed. but, in this case we should not fire the webhook.
        // We would have already fired the completed webhook once before.
        //
        // We should fire this webhook only if it was not marked as completed before.
        //
        if (($oldStatus !== Status::COMPLETED) and
            ($subscription->isCompleted() === true))
        {
            $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED);
        }
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
