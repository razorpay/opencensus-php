<?php

namespace RZP\Models\Plan\Subscription;

use App;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Models\Schedule;
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

                // Payment will be null here in case there was some exception thrown.
                $this->handleAuthorizationOrCaptureFailure($subscription, $invoice, $payment, $captureFailure);
            }

            return false;
        }

        return true;
    }

    public function handleCaptureSuccess(
        Entity $subscription,
        Payment\Entity $capturedPayment,
        Invoice\Entity $invoice,
        $newSubscriptionAuthTxnCharge = false)
    {
        $task = $subscription->task;

        //
        // All this natak is being done just so that we can keep
        // schedule anchor update and other entities update inside
        // a transaction and also ensure that the webhooks being fired
        // are not inside a transaction.
        //
        if ($newSubscriptionAuthTxnCharge === true)
        {
            $schedule = $subscription->schedule;

            //
            // If this is auth txn charge, it means that start_at was null. This,
            // in turn, means that some fields were not filled when the subscription
            // was created. We fill those fields here.
            //
            $this->updateSubscriptionDetails($subscription, $capturedPayment, $invoice, $schedule);

            // TODO: This needs to go inside a transaction in `saveSubscriptionAndInvoiceAndTask`.
            // For some reason, it doesn't get updated there correctly. Figure it out and fix.
            $this->repo->saveOrFail($schedule);
        }

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

        if ($subscription->shouldUpdateWithInvoiceCharge($invoice) === true)
        {
            $this->handleCaptureSuccessAndUpdateSubscription($subscription, $capturedPayment, $invoice, $task);
        }
        else
        {
            $this->handleCaptureSuccessWithoutUpdatingSubscription($subscription, $capturedPayment, $invoice, $task);
        }

        $this->trace->info(
            TraceCode::SUBSCRIPTION_AFTER_CAPTURE_UPDATE,
            [
                'subscription_details' => $subscription->toArray(),
                'invoice_details' => $invoice->toArray(),
                'task_details' => $task->toArray()
            ]);
    }

    protected function handleCaptureSuccessAndUpdateSubscription(
        Entity $subscription,
        Payment\Entity $capturedPayment,
        Invoice\Entity $invoice,
        Task\Entity $task)
    {
        $oldStatus = $subscription->getStatus();

        //
        // If the capture is being done for latest invoice,
        // The only statuses that it can be in are:
        //  - authenticated
        //  - active
        //  - pending
        //

        if (in_array($oldStatus, Status::$latestInvoiceStatuses, true) === false)
        {
            throw new LogicException(
                'Should have never reached here. Should have been caught much before in the flow',
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_INVOICE_BAD_STATUS,
                [
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                    'captured_payment_id'   => $capturedPayment->getId(),
                    'invoice_id'            => $invoice->getId(),
                    'method'                => $capturedPayment->getMethod()
                ]);
        }

        $subscription->setStatus(Status::ACTIVE);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_STATUS_ACTIVE,
            [
                'old_status'        => $oldStatus,
                'new_status'        => $subscription->getStatus(),
                'subscription_id'   => $subscription->getId(),
                'payment_id'        => $capturedPayment->getId(),
            ]);

        $task->updateForSubscription($subscription, $this->mode);

        //
        // Even though we are updating it in the invoice now,
        // we will be keeping the current billing cycle period
        // in subscriptions also.
        //
        $this->setEndedAtIfApplicable($subscription);

        // This is basically just for the pending state
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

        $this->saveAndFireWebhooksOnCaptureSuccessForLatestInvoice(
            $subscription, $invoice, $task, $capturedPayment, $oldStatus);
    }

    protected function saveAndFireWebhooksOnCaptureSuccessForLatestInvoice(
        Entity $subscription,
        Invoice\Entity $invoice,
        Task\Entity $task,
        Payment\Entity $capturedPayment,
        string $oldStatus)
    {
        $updatedStatus = $subscription->getStatus();

        $core = new Core;

        //
        // If the capture is being done for latest invoice,
        // The only statuses that it can be in are:
        //  - authenticated
        //  - active
        //  - pending
        //
        // Hence, the possible flows are:
        //  - authenticated     -> active -> completed  [fire charge, active and completed webhooks]
        //  - active            -> active -> completed  [fire charge and completed webhooks]
        //  - pending           -> active -> completed  [fire charge, active and completed webhooks]
        //
        //  - authenticated     -> active               [fire charge and active webhooks]
        //  - active            -> active               [fire charge webhook]
        //  - pending           -> active               [fire charge and active webhooks]
        //

        switch ($updatedStatus)
        {
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
            case Status::COMPLETED:
                //
                // In all of these cases, the subscription would first move to active and THEN to completed.
                // Hence, in these cases we have to fire both active and completed webhooks.
                // But, in case of active -> active -> completed, we don't have to fire the active webhook
                // and we can directly fire the completed webhook.
                //
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

                $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED, $capturedPayment);

                break;
            default:
                throw new LogicException(
                    'Subscription moved to an unexpected status',
                    ErrorCode::SERVER_ERROR_SUBSCRIPTION_WRONG_STATUS_UPDATE,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'old_status'        => $oldStatus,
                        'new_status'        => $updatedStatus,
                        'invoice_id'        => $invoice->getId(),
                        'payment_id'        => $capturedPayment->getId(),
                        'method'            => $capturedPayment->getMethod()
                    ]);
        }
    }

    protected function handleCaptureSuccessWithoutUpdatingSubscription(
        Entity $subscription,
        Payment\Entity $capturedPayment,
        Invoice\Entity $invoice,
        Task\Entity $task)
    {
        $oldStatus = $subscription->getStatus();

        //
        // If the capture is being done for an older invoice,
        // It can be in any of the following statuses:
        //  - active
        //  - pending
        //  - halted
        //  - cancelled
        //  - completed
        //

        if (in_array($oldStatus, Status::$oldInvoiceStatuses, true) === false)
        {
            throw new LogicException(
                'Should have never reached here. Should have been caught much before in the flow',
                ErrorCode::SERVER_ERROR_SUBSCRIPTION_INVOICE_BAD_STATUS,
                [
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                    'captured_payment_id'   => $capturedPayment->getId(),
                    'invoice_id'            => $invoice->getId(),
                    'method'                => $capturedPayment->getMethod()
                ]);
        }

        if ($subscription->isHalted() === true)
        {
            //
            // We don't reset error fields for pending state, because
            // we don't want to mess with the retry cron.
            //
            $subscription->resetErrorFields();

            //
            // We change the status to active only if it's halted.
            // Pending's status will change to active when the latest
            // invoice is successfully paid.
            //
            $subscription->setStatus(Status::ACTIVE);
        }

        $subscription->incrementPaidCount();

        $this->saveAndFireWebhooksOnCaptureSuccessForOlderInvoice(
            $subscription, $invoice, $task, $capturedPayment, $oldStatus);
    }

    protected function saveAndFireWebhooksOnCaptureSuccessForOlderInvoice(
        Entity $subscription,
        Invoice\Entity $invoice,
        Task\Entity $task,
        Payment\Entity $capturedPayment,
        string $oldStatus)
    {
        $core = new Core;

        //
        // If the capture is being done for older invoice,
        // The only statuses that it can be in are:
        //  - active
        //  - pending
        //  - halted
        //  - cancelled
        //  - completed
        //
        // Hence, the possible flows are:
        //  - active    -> active       [charge webhook]
        //  - pending   -> active       [charge webhook] --- DOES NOT HAPPEN because it's older invoice flow
        //  - pending   -> pending      [charge webhook]
        //  - cancelled -> cancelled    [charge webhook]
        //  - completed -> completed    [charge webhook]
        //  - halted    -> active       [charge and active webhook]
        //

        switch ($oldStatus)
        {
            case Status::ACTIVE:
            case Status::PENDING:
            case Status::CANCELLED:
            case Status::COMPLETED:
                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->eventSubscriptionCharged($subscription, $capturedPayment);

                break;
            case Status::HALTED:
                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->eventSubscriptionCharged($subscription, $capturedPayment);

                $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE, $capturedPayment);

                break;
            default:
                throw new LogicException(
                    'Subscription moved to an unexpected status',
                    ErrorCode::SERVER_ERROR_SUBSCRIPTION_WRONG_STATUS_UPDATED_FROM,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'old_status'        => $oldStatus,
                        'new_status'        => $subscription->getStatus(),
                        'invoice_id'        => $invoice->getId(),
                        'payment_id'        => $capturedPayment->getId(),
                        'method'            => $capturedPayment->getMethod()
                    ]);
        }
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
            $task->updateForSubscription($subscription, $this->mode, true);

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
            $task->updateForSubscription($subscription, $this->mode);

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

        //
        // This function would be called only for latest invoice,
        // since this is not called for manual attempts.
        // If the charge was being attempted for latest invoice,
        // The only statuses that it can be in are:
        //  - authenticated
        //  - active
        //  - pending
        //
        // Hence, the possible flows are:
        //  - authenticated -> pending              [fire charge and pending webhook]
        //  - active        -> pending              [fire charge and pending webhook]
        //  - pending       -> pending              [fire charge and pending webhook]
        //  - pending       -> halted               [fire charge and halted webhook]
        //  - pending       -> halted  -> completed [fire charge, halted and completed webhook]
        //

        switch($updatedStatus)
        {
            case Status::PENDING:
                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->fireWebhookForStatusUpdate($subscription, Status::PENDING, $payment);

                break;
            case Status::HALTED:
                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->fireWebhookForStatusUpdate($subscription, Status::HALTED, $payment);

                break;
            case Status::COMPLETED:
                $currentEndedAt = $subscription->getEndedAt();

                $subscription->setStatus(Status::HALTED);
                $subscription->setEndedAt(null);

                $this->saveSubscriptionAndInvoiceAndTask($subscription, $task, $invoice);

                $core->fireWebhookForStatusUpdate($subscription, Status::HALTED, $payment);

                $subscription->setStatus(Status::COMPLETED);
                $subscription->setEndedAt($currentEndedAt);

                $this->repo->saveOrFail($subscription);

                $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED, $payment);

                break;
            default:
                throw new LogicException(
                    'Subscription moved to an unexpected status',
                    ErrorCode::SERVER_ERROR_SUBSCRIPTION_WRONG_STATUS_UPDATE,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'new_status'        => $updatedStatus,
                        'invoice_id'        => $invoice->getId(),
                    ]);
        }

        $core->triggerSubscriptionFailureNotification($subscription);
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

    protected function updateSubscriptionDetails(
        Entity $subscription,
        Payment\Entity $payment,
        Invoice\Entity $invoice,
        Schedule\Entity $schedule)
    {
        $plan = $subscription->plan;

        $subscription->setStartAt($payment->getCreatedAt());

        //
        // We don't have to update the next_run_at here
        // because `handleCaptureSuccess` will take care of that.
        // When the task was first created, the start_at of subscription
        // would have been null, which means that the next_run_at
        // would have got set to the midnight of subscription creation
        // date (default).
        // It will not get picked up by the cron also because of the
        // subscription status being in created state.
        // Now, since we set `anchor` here, the next_run_at of the task
        // will automatically get set to the correct next_run
        // according to the anchor.
        //

        if ($subscription->isAuthenticated() === false)
        {
            throw new LogicException(
                'Subscription is not in authenticated state. This function should not have been called',
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'status' => $subscription->getStatus(),
                    'method' => $payment->getMethod()
                ]);
        }

        $anchor = $subscription->getAnchorForSchedule();

        $schedule->setAnchor($anchor);

        (new Biller)->updateSubscriptionAndInvoiceBillingPeriod($subscription, $invoice);

        (new Creator)->fillEndAtAndTotalCount($subscription, $plan);
    }

    protected function authorizePayment(Entity $subscription, array $recurringPayload)
    {
        $processor = new Payment\Processor\Processor($subscription->merchant);

        $processor->process($recurringPayload);

        $authorizedPayment = $processor->getPayment();

        return $authorizedPayment;
    }

    // TODO: Implement Slack Logging! Pliss
}
