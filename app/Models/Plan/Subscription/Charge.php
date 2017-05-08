<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Jobs\InvoiceAction;
use RZP\Models\Schedule\Library;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Plan;
use RZP\Models\Invoice;
use RZP\Models\Base;
use RZP\Models\Schedule\Task;

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

    /**
     * Maximum authorization attempts allowed for subscription charge.
     *
     * TODO: Make this merchant configurable.
     */
    const MAX_AUTH_ATTEMPTS = 3;

    /**
     * This is called via the queue to initiate the actual
     * charge process.
     *
     * @param $data
     *
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

        $recurringPayload = $data['recurring_payload'];

        $subscription = $this->repo->subscription->findOrFail($data['subscription_id']);

        $invoice = $this->repo->invoice->findOrFail($data['invoice_id']);

        $valid = $this->validateInvoiceStatusBeforeCharging($invoice, $subscription);

        if ($valid === false)
        {
            return;
        }

        $this->processor = new Payment\Processor\Processor($subscription->merchant);

        $manual = $data['manual'];

        if ($manual === false)
        {
            //
            // This needs to be incremented every time we attempt to authorize a payment.
            // Using this attribute, we would decide whether to retry or not.
            //
            $subscription->incrementAuthAttempts();
        }

        $authorizedPayment = null;

        try
        {
            $authorizedPayment = $this->authorizePayment($recurringPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            if ($manual === false)
            {
                $this->handleAuthorizationFailure($subscription);
            }

            return;
        }

        //
        // This is being done outside the try-catch-finally block because we
        // do not want to invoke the function handleAuthorizationFailure
        // if an exception gets thrown in handleAuthorizationSuccess.
        //
        $this->handleAuthorizationSuccess($authorizedPayment, $subscription);
    }

    protected function validateInvoiceStatusBeforeCharging(Invoice\Entity $invoice, Entity $subscription)
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
        // on_hold. If this happened, we should not attempt
        // to charge the subscription now.
        //
        else if ($invoice->getSubStatus() === Invoice\Status::ON_HOLD)
        {
            $traceCode = TraceCode::SUBSCRIPTION_INVOICE_ON_HOLD;

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

    protected function handleAuthorizationSuccess(Payment\Entity $payment, Entity $subscription)
    {
        $this->resetErrorFields($subscription);

        //
        // If it's already captured, `handleCaptureSuccess` would have been
        // called in the auto capture flow itself.
        // Hence, we don't have to handle for captured successfully flow, here.
        //
        if ($payment->isCaptured() === false)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_PAYMENT_CAPTURE_FAILED,
                [
                    'subscription_id'   => $subscription->getId(),
                ]);

            // TODO: Decide on what status to keep here. How to handle?
            // TODO: Also decide how to handle in case of manual retry.
            $subscription->setStatus(Status::ON_HOLD);
            $subscription->setErrorStatus(Status::CAPTURE_FAILURE);
        }

        $this->repo->saveOrFail($subscription);
    }

    protected function resetErrorFields(Entity $subscription)
    {
        $subscription->setFailedAt(null);
        $subscription->setErrorStatus(null);
        $subscription->resetAuthAttempts();
    }

    protected function handleAuthorizationFailure(Entity $subscription)
    {
        $this->trace->critical(
            TraceCode::SUBSCRIPTION_PAYMENT_AUTHORIZE_FAILED,
            [
                'subscription_id'   => $subscription->getId(),
            ]);

        $subscription->setErrorStatus(Status::AUTH_FAILURE);

        $authAttempts = $subscription->getAuthAttempts();

        if ($authAttempts < self::MAX_AUTH_ATTEMPTS)
        {
            $subscription->setStatus(Status::OVERDUE);
            $this->incrementChargeAtByOneDay($subscription);
            $this->updateScheduleTask($subscription->task, true);
        }
        else if ($authAttempts === self::MAX_AUTH_ATTEMPTS)
        {
            // TODO: Make this merchant configurable. It can either
            // go into on_hold or cancelled state.
            $subscription->setStatus(Status::ON_HOLD);

            // TODO: Notify merchant about the failure.
        }
        else
        {
            throw new LogicException(
                'Should not have reached here. Auth Attempts cannot be greater than 3.',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'auth_attempts'     => $authAttempts,
                ]);
        }

        $this->repo->saveOrFail($subscription);

        (new Core)->fireWebhookForStatusUpdate($subscription, $subscription->getStatus());
    }

    /**
     * This function is only called during an auth_failure.
     *
     * @param Entity $subscription
     */
    protected function incrementChargeAtByOneDay(Entity $subscription)
    {
        $currentChargeAt = $subscription->getChargeAt();

        $currentChargeAt = Carbon::createFromTimestamp($currentChargeAt);

        $nextChargeAt = $currentChargeAt->addDay()->timestamp;

        $subscription->setChargeAt($nextChargeAt);
    }

    protected function capturePayment(Payment\Entity $authorizedPayment)
    {
        $paymentId = $authorizedPayment->getId();

        $capturePayload = [
            Payment\Entity::AMOUNT => $authorizedPayment->getAmount(),
        ];

        $capturedPayment = $this->processor->capture($paymentId, $capturePayload);

        return $capturedPayment;
    }

    public function handleCaptureSuccess(Entity $subscription, Payment\Entity $capturedPayment, Invoice\Entity $invoice)
    {
        $task = $subscription->task;

        //
        // Cannot move this to a variable because the instance values change later.
        //
        $this->trace->info(
            TraceCode::SUBSCRIPTION_BEFORE_CAPTURE_UPDATE,
            [
                'subscription_details' => $subscription->toArray(),
                'invoice_details' => $invoice->toArray(),
                'task_details' => $task->toArray(),
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
        // Not sending webhook here because the transaction might fail later
        // in the flow. Will be sending it after the transaction is committed.
        //
        $subscription->setStatus(Status::ACTIVE);

        $this->resetErrorStatusForSuccessfulCapture($subscription, $capturedPayment);

        //
        // Even though we are updating it in the invoice now,
        // we will be keeping the current billing cycle period
        // in subscriptions also.
        //
        // Ensure that setting billing period functions are called
        // before incrementing the paid count, since the logic
        // is dependent on that.
        //
        $this->setCurrentPeriod($subscription);

        $this->setInvoiceBillingPeriod($subscription, $invoice);

        $this->setNextChargeAt($subscription);

        $this->updateScheduleTask($task);

        $this->incrementPaidCount($subscription);

        $this->setEndedAtIfApplicable($subscription);

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

        (new Core)->fireWebhookForStatusUpdate($subscription, Status::ACTIVE);

        //
        // This must be sent after saving the invoice and subscription
        // to ensure that we don't send an email when we were not able
        // to charge the subscription.
        //
        // $this->sendInvoiceEmail($invoice);
    }

    protected function setInvoiceBillingPeriod(Entity $subscription, Invoice\Entity $invoice)
    {
        $invoice->setBillingStart($subscription->getCurrentStart());
        $invoice->setBillingEnd($subscription->getCurrentEnd());
    }

    protected function getBillingPeriod(Entity $subscription)
    {
        $schedule = $subscription->schedule;
        $task = $subscription->task;

        $billingPeriod = [];

        if ($subscription->getPaidCount() === 0)
        {
            $billingPeriod['start'] = $subscription->getStartAt();
        }
        else
        {
            $billingPeriod['start'] = $task->getNextRunAt();
        }

        $currentTime = Carbon::now('Asia/Kolkata');
        $lastRun = Carbon::createFromTimestamp($task->getNextRunAt(), 'Asia/Kolkata');
        $currentEnd = Library::computeFutureRun($schedule, $currentTime, $lastRun, false);

        $billingPeriod['end'] = $currentEnd->timestamp;

        return $billingPeriod;
    }

    protected function resetErrorStatusForSuccessfulCapture(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $errorStatus = $subscription->getErrorStatus();

        //
        // At this point of the flow, if there is an error, it should be capture failure only.
        // If it was auth_failure, capture shouldn't have been called at all for the payment.
        //
        // The auth_failure error status is reset as soon as successful authorization is done.
        //
        // We check for null also here so that we can trace everything which is unexpected.
        // null is expected when there is no error.
        //

        if (($errorStatus === null) or
            ($errorStatus === Status::CAPTURE_FAILURE))
        {
            $subscription->setErrorStatus(null);
        }
        else
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_ERROR_STATUS_UNEXPECTED,
                [
                    'payment_id'        => $capturedPayment->getId(),
                    'subscription_id'   => $subscription->getId(),
                    'error_status'      => $errorStatus,
                ]);
        }
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
     * Gets the current period's end and assigns that to charge_at.
     * If the current period's end is greater than the end_at of the subscription,
     * we set the charge_at to null.
     *
     * We cannot take the current charge_at and just add the interval to it
     * for the next charge_at because charge_at can be modified during auth failures.
     *
     * @param Entity $subscription
     */
    protected function setNextChargeAt(Entity $subscription)
    {
        $currentEnd = $subscription->getCurrentEnd();

        $nextChargeAt = $currentEnd;
        $endAt = $subscription->getEndAt();

        if ($nextChargeAt > $endAt)
        {
            $nextChargeAt = null;
        }

        $subscription->setChargeAt($nextChargeAt);
    }

    /**
     * In case of retries, we would explicitly change the task's next_run_at
     * to the next day instead of next month or so. If the retry is successful,
     * we would call this function and the next_run_at will get set to
     * whatever it's supposed to get set to initially without retry.
     *
     * @param Task\Entity $task
     * @param bool        $retry
     */
    protected function updateScheduleTask(Task\Entity $task, $retry = false)
    {
        if ($retry === true)
        {
            $task->incrementNextRunByOneDayAndUpdateLastRun();

            return;
        }

        $task->updateNextRunAndLastRun(false);
    }

    /**
     * If first subscription, set
     * [currentStart, currentEnd] = [startAt, startAt + interval]
     * If NOT first subscription, set
     * [currentStart, currentEnd] = [currentStart+interval, currentStart + (2 * interval)]
     *
     * @param Entity $subscription
     */
    protected function setCurrentPeriod(Entity $subscription)
    {
        $billingPeriod = $this->getBillingPeriod($subscription);

        $subscription->setCurrentStart($billingPeriod['start']);
        $subscription->setCurrentEnd($billingPeriod['end']);
    }

    /**
     * If the chargeAt is null, it means that the subscription has ended.
     * We set the end_at to the current period's end_at in this case.
     *
     * @param Entity $subscription
     */
    protected function setEndedAtIfApplicable(Entity $subscription)
    {
        if ($subscription->getChargeAt() === null)
        {
            $subscription->setEndedAt($subscription->getCurrentEnd());

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
