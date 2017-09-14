<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;

use RZP\Models\Plan;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Invoice;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;

class Charge extends Base\Core
{
    protected $app;

    /**
     * @var Trace
     */
    protected $trace;

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

        $this->processor = new Payment\Processor\Processor($subscription->merchant);

        if ($manual === false)
        {
            //
            // This needs to be incremented every time we attempt to authorize a payment.
            // Using this attribute, we would decide whether to retry or not.
            //
            $subscription->incrementAuthAttempts();
        }

        $payment = null;

        try
        {
            $payment = $this->authorizePayment($data['recurring_payload']);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            if ($manual === false)
            {
                $this->handleAuthorizationOrCaptureFailure($subscription, $invoice, $payment);
            }

            return false;
        }

        //
        // If it's already captured, `handleCaptureSuccess` would have been
        // called in the auto capture flow itself.
        // Hence, we don't have to handle for captured successfully flow, here.
        //
        if (($payment->isCaptured() === false) and
            ($manual === false))
        {
            $this->handleAuthorizationOrCaptureFailure($subscription, $invoice, $payment, true);

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
        if (($this->isLatestInvoiceForSubscription($subscription, $invoice) === true) and
            (($subscription->getStatus() === Status::ACTIVE) or
             ($subscription->getStatus() === Status::PENDING)))
        {
            //
            // Schedule task needs to be updated before setting time
            // fields in subscription, as the next_run_at of
            // schedule_task is used to set subscription charge_at
            //
            $this->updateScheduleTask($subscription);

            //
            // Even though we are updating it in the invoice now,
            // we will be keeping the current billing cycle period
            // in subscriptions also.
            //
            $this->updateChargeAtAndEndedAt($subscription);
        }

        //
        // Not sending webhook here because the transaction might fail later
        // in the flow. Will be sending it after the transaction is committed.
        //
        $subscription->setStatus(Status::ACTIVE);

        $this->resetErrorFields($subscription);

        $this->incrementPaidCount($subscription);

        if ($oldStatus === Status::AUTHENTICATED)
        {
            $this->setActivatedAt($subscription, $capturedPayment);
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

        $core = (new Core);

        if ($oldStatus !== Status::ACTIVE)
        {
            $core->fireWebhookForStatusUpdate($subscription, Status::ACTIVE, $capturedPayment);
        }

        $core->eventSubscriptionCharged($subscription, $capturedPayment);

        if ($subscription->isCompleted() === true)
        {
            $core->fireWebhookForStatusUpdate($subscription, Status::COMPLETED);
        }

        //
        // This must be sent after saving the invoice and subscription
        // to ensure that we don't send an email when we were not able
        // to charge the subscription.
        //
        // $this->sendInvoiceEmail($invoice);
    }

    /**
     * Update charge_at and ended_at. Current period does not need to
     * be updated as these were set before the charge was attempted.
     * See updateSubscriptionInvoiceBillingPeriod.
     *
     * @param  Entity $subscription
     */
    public function updateChargeAtAndEndedAt(Entity $subscription)
    {
        $subscription->setChargeAt($subscription->task->getNextRunAt());

        $this->setEndedAtIfApplicable($subscription);
    }

    /**
     * Checks if invoice is the latest one generated for the subscription.
     * This would be the case if the invoice billing period matches that
     * of the subscription, which is always current.
     *
     * @param  Entity         $subscription
     * @param  Invoice\Entity $invoice
     * @return boolean
     */
    protected function isLatestInvoiceForSubscription(Entity $subscription, Invoice\Entity $invoice)
    {
        $isLatest = false;

        if (($subscription->getCurrentStart() === $invoice->getBillingStart()) and
            ($subscription->getCurrentEnd() === $invoice->getBillingEnd()))
        {
            $isLatest = true;
        }

        return $isLatest;
    }

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

        if ($authAttempts < self::MAX_AUTH_ATTEMPTS)
        {
            // Charge has failed an acceptable number of times
            $subscription->setStatus(Status::PENDING);

            // Update task by a day
            $this->updateScheduleTask($subscription, true);

            // TODO: Replace below function with updateSubscriptionTimeFields?
            // This will call setEndedAtIfApplicable, which could end up setting
            // the wrong time as ended_at, if current_start is not equal to current
            // time (happens in case of retries).
            $subscription->setChargeAt($subscription->task->getNextRunAt());
        }
        else if ($authAttempts === self::MAX_AUTH_ATTEMPTS)
        {
            // TODO: Make this merchant configurable. It can either
            // go into halted or cancelled state.
            $subscription->setStatus(Status::HALTED);
            $invoice->setSubscriptionStatus(Invoice\Status::HALTED);

            // Update task by a full plan period
            $this->updateScheduleTask($subscription);

            // TODO: Replace below function with updateSubscriptionTimeFields?
            // This will call setEndedAtIfApplicable, which could end up setting
            // the wrong time as ended_at, if current_start is not equal to current
            // time (happens in case of retries).
            $subscription->setChargeAt($subscription->task->getNextRunAt());
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

    protected function validateInvoiceStatusBeforeCharging(
        Invoice\Entity $invoice,
        Entity $subscription,
        bool $manual)
    {
        $valid = true;

        //
        // This happens when two crons picked up the same invoice
        // and queued the charge on them.
        // If one of the queue picks it up first, it would have marked the
        // invoice as paid and now this queue gets executed.
        //
        if ($invoice->isPaid() === true)
        {
            $traceCode = TraceCode::SUBSCRIPTION_INVOICE_ALREADY_PAID;

            $valid = false;
        }
        //
        // When a different cron picked up the invoice for a charge
        // and got queued, the status could have gone into
        // halted. If this happened, we should not attempt
        // to charge the subscription now.
        //
        else if (($invoice->getSubscriptionStatus() === Invoice\Status::HALTED) and
                 ($manual === false))
        {
            $traceCode = TraceCode::SUBSCRIPTION_INVOICE_HALTED;

            $valid = false;
        }

        if ($valid === false)
        {
            $this->trace->critical(
                $traceCode,
                [
                    'invoice_id'        => $invoice->getId(),
                    'subscription_id'   => $subscription->getId(),
                ]);
        }

        return $valid;
    }

    protected function authorizePayment(array $recurringPayload)
    {
        $recurringPayment = $this->processor->process($recurringPayload);

        $authorizedPayment = $this->repo->payment->findByPublicId($recurringPayment['razorpay_payment_id']);

        return $authorizedPayment;
    }

    public function resetErrorFields(Entity $subscription)
    {
        $subscription->setFailedAt(null);
        $subscription->setErrorStatus(null);
        $subscription->resetAuthAttempts();
    }

    /**
     * This just uses the captured_at.
     *
     * @param Entity $subscription
     * @param Payment\Entity $capturedPayment
     */
    protected function setActivatedAt(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $capturedAt = $capturedPayment->getCaptureTimestamp();

        $subscription->setActivatedAt($capturedAt);
    }

    protected function incrementPaidCount(Entity $subscription)
    {
        $subscription->incrementPaidCount();
    }

    /**
     * In case of retries, we would explicitly change the task's next_run_at
     * to the next day instead of next month or so. If the retry is successful,
     * we would call this function and the next_run_at will get set to
     * whatever it's supposed to get set to initially without retry.
     *
     * @param Entity $subscription
     * @param bool   $retry
     */
    public function updateScheduleTask(Entity $subscription, $retry = false)
    {
        $task = $subscription->task;

        if ($retry === true)
        {
            $task->incrementNextRunByOneDayAndUpdateLastRun();

            return;
        }

        //
        // Calling updateNextRunAndLastRun for task sets the next_run starting
        // from current time. This works fine in most cases, since charge time
        // is usually equal to current time. But in the merchant-initiated test
        // charge flow, we allow merchants to simulate a future charge for a
        // subscription. So in this case, using current time will give the wrong
        // result. So we use charge_at instead, which is equal to current time
        // in normal flow, and equal to simulated current time in test charge flow.
        //
        $referenceTime = $subscription->getChargeAt();

        // However, if charge_at is currently null, that means this is the auth txn.
        // In that case, we can use actual current time as the reference time.
        if ($referenceTime === null)
        {
            $referenceTime = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $referenceTime = Carbon::createFromTimestamp($referenceTime, Timezone::IST);

        $task->updateNextRunAndLastRunFromGivenRefTime($referenceTime, false);
    }

    /**
     * Count the number of invoices generated for the subscription that were part of
     * the plan (so exclude upfront amounts with future start_at). When this count
     * is equal to the total count for the subscription, it is complete.
     * We also mark charge_at to null at this stage.
     *
     * @param Entity $subscription
     */
    protected function setEndedAtIfApplicable(Entity $subscription)
    {
        $planChargeInvoiceCount = $subscription->getPlanChargeInvoicesCount();

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

            $subscription->setChargeAt(null);

            $subscription->setStatus(Status::COMPLETED);
        }
    }

    protected function logToSlack(array $data)
    {
        // Do not log for test mode
        if ($this->app['rzp.mode'] === Mode::TEST)
        {
            return;
        }

        $settings = $this->getSlackSettings();

        $headline = 'Subscription Payment Failed';

        $this->app['slack']->queue($headline, $data, $settings);
    }

    protected function getSlackSettings()
    {
        $settings['channel'] = $this->app['config']->get('slack.channels.subscriptions');
        $settings['color'] = 'danger';

        return $settings;
    }

    protected function getPeriodFunction(Plan\Entity $plan)
    {
        $period = $plan->getPeriod();

        return 'add' . $period . 's';
    }
}
