<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Jobs\InvoiceAction;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Plan;
use RZP\Models\Invoice;
use RZP\Models\Base;

class Charge extends Base\Core
{
    protected $app;
    protected $trace;
    protected $repo;
    protected $processor;

    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 300;

    const MAX_AUTH_ATTEMPTS = 3;

    /**
     * This is called via the queue to initiate the actual
     * charge process.
     *
     * @param $job
     * @param $data
     *
     * @throws LogicException
     */
    public function fireCharge($job, $data)
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_PAYMENT_QUEUE_DATA,
            $data);

        //
        // This is required so that the mode and the db connection are set.
        // Since this is via queue, this will not set on its own.
        //
        $this->app['basicauth']->checkAndSetKeyId($data['key_id']);

        $recurringPayload = $data['recurring_payload'];

        $subscription = $this->repo->subscription->findOrFail($data['subscription_id']);

        $this->processor = new Payment\Processor\Processor($subscription->merchant);

        //
        // This needs to be incremented every time we attempt to authorize a payment.
        // Using this attribute, we would decide whether to retry or not.
        //
        $subscription->incrementAuthAttempts();

        $authorizedPayment = null;

        try
        {
            $authorizedPayment = $this->authorizePayment($recurringPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $this->handleAuthorizationFailure($subscription);

            return;
        }
        finally
        {
            $job->delete();
        }

        //
        // This is being done outside the try-catch-finally block because we
        // do not want to invoke the function handleAuthorizationFailure
        // if an exception gets thrown in handleAuthorizationSuccess.
        //
        $this->handleAuthorizationSuccess($authorizedPayment, $subscription);
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
            $this->trace->error(
                TraceCode::SUBSCRIPTION_PAYMENT_CAPTURE_FAILED,
                [
                    'subscription_id'   => $subscription->getId(),
                ]);

            // TODO: Decide on what status to keep here. How to handle?
            $subscription->setStatus(Status::ON_HOLD);
            $subscription->setErrorStatus(Status::CAPTURE_FAILURE);
        }

        $this->repo->saveOrFail($subscription);
    }

    protected function resetErrorFields(Entity $subscription)
    {
        $subscription->setFailedAt(null);
        $subscription->setErrorStatus(null);
    }

    protected function handleAuthorizationFailure(Entity $subscription)
    {
        $this->trace->error(
            TraceCode::SUBSCRIPTION_PAYMENT_AUTHORIZE_FAILED,
            [
                'subscription_id'   => $subscription->getId(),
            ]);

        $subscription->setErrorStatus(Status::AUTH_FAILURE);

        $authAttempts = $subscription->getAuthAttempts();

        // TODO: Make max_auth_attempts configurable at a merchant level.
        if ($authAttempts < self::MAX_AUTH_ATTEMPTS)
        {
            $subscription->setStatus(Status::OVERDUE);
            $this->incrementChargeAtByOneDay($subscription);
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
        $plan = $subscription->plan;

        $subscription->setStatus(Status::ACTIVE);

        $this->resetErrorStatusForSuccessfulCapture($subscription, $capturedPayment);

        //
        // Even though we are updating it in the invoice now,
        // we will be keeping the current billing cycle period
        // in subscriptions also.
        //
        $this->setCurrentPeriod($subscription, $plan);

        $this->setInvoiceBillingPeriod($subscription, $invoice);

        $this->setNextChargeAt($subscription, $plan);

        $this->incrementPaidCount($subscription);

        $this->setEndedAtIfApplicable($subscription);

        $this->setActivatedAt($subscription, $capturedPayment);

        $this->sendInvoiceEmail($invoice);
    }

    protected function sendInvoiceEmail(Invoice\Entity $invoice)
    {
        (new Invoice\Core)->dispatchQueueJob(
            $this->mode,
            InvoiceAction::SUBSCRIPTION_CHARGED,
            $invoice->getId()
        );
    }

    protected function setInvoiceBillingPeriod(Entity $subscription, Invoice\Entity $invoice)
    {
        $invoice->setBillingStart($subscription->getCurrentStart());
        $invoice->setBillingEnd($subscription->getCurrentEnd());

        $this->repo->saveOrFail($invoice);
    }

    protected function getBillingPeriod(Entity $subscription)
    {
        $plan = $subscription->plan;

        $billingPeriod = [];

        $period = $plan->getPeriod();

        $carbonAddFunc = Plan\Cycle::getCarbonFunction($period, 'add');

        $interval = $plan->getInterval();

        if ($subscription->getPaidCount() === 0)
        {
            $currentStart = $subscription->getStartAt();

            $billingPeriod['start'] = $currentStart;

            $currentEnd = Carbon::createFromTimestamp($currentStart)
                                ->$carbonAddFunc($interval);

            $billingPeriod['end'] = $currentEnd->timestamp;
        }
        else
        {
            $currentStart = Carbon::createFromTimestamp($subscription->getCurrentStart());

            $currentStart->$carbonAddFunc($interval)->timestamp;

            $billingPeriod['start'] = $currentStart;

            // To get $currentEnd, we need to add the same period to $currentStart (new $currentStart).
            $currentStart->$carbonAddFunc($interval)->timestamp;

            $billingPeriod['end'] = $currentStart;
        }

        return $billingPeriod;
    }

    protected function resetErrorStatusForSuccessfulCapture(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $errorStatus = $subscription->getErrorStatus();

        //
        // At this point of the flow, if there is an error, it should be capture failure only.
        // If it was auth_failure, capture shouldn't have been called at all for the payment.
        //

        if (($errorStatus === null) or
            ($errorStatus === Status::CAPTURE_FAILURE))
        {
            $subscription->setErrorStatus(null);
        }
        else
        {
            $this->trace->error(
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
     * @param Plan\Entity $plan
     */
    protected function setNextChargeAt(Entity $subscription, Plan\Entity $plan)
    {
        $currentEnd = $subscription->getCurrentEnd();

        $nextChargeAt = $currentEnd;
        $endAt = $subscription->getEndAt();

        if ($nextChargeAt > $endAt)
        {
            $nextChargeAt = null;
        }

        $subscription->setChargeAt($nextChargeAt);

        // $currentChargeAt = $subscription->getChargeAt();
        //
        // $currentChargeAt = Carbon::createFromTimestamp($currentChargeAt);
        //
        // $intervalFunc = $this->getIntervalFunction($plan);
        //
        // $intervalCount = $plan->getIntervalCount();
        //
        // // Modifies currentChargeAt variable.
        // $currentChargeAt->$intervalFunc($intervalCount);
        //
        // $nextChargeAt = $currentChargeAt->timestamp;
        //
        // $endAt = $subscription->getEndAt();
        //
        // if ($nextChargeAt > $endAt)
        // {
        //     $nextChargeAt = null;
        // }
        //
        // $subscription->setChargeAt($nextChargeAt);
    }

    /**
     * If first subscription, set
     * [currentStart, currentEnd] = [startAt, startAt + interval]
     * If NOT first subscription, set
     * [currentStart, currentEnd] = [currentStart+interval, currentStart + (2 * interval)]
     *
     * @param Entity $subscription
     * @param Plan\Entity $plan
     */
    protected function setCurrentPeriod(Entity $subscription, Plan\Entity $plan)
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
