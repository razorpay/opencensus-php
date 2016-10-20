<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception\LogicException;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Plan;

class Charge
{
    protected $app;
    protected $trace;
    protected $repo;
    protected $processor;

    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 300;

    const MAX_AUTH_ATTEMPTS = 3;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->repo = $this->app['repo'];
    }

    // Called through queue
    public function fireCharge($job, $data)
    {
        $this->trace->info(
            TraceCode::SUBSCRIPTION_PAYMENT_QUEUE_DATA,
            $data);

        // This is required so that the mode and the db connection are set.
        // Since this is via queue, this will not set on its own.
        $this->app['basicauth']->checkAndSetKeyId($data['key_id']);

        $recurringPayload = $data['recurring_payload'];

        $subscription = $this->repo->subscription->findOrFail($data['subscription_id']);

        $this->processor = new Payment\Processor\Processor($subscription->merchant);

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

        // This is being done outside the try-catch-finally block because we
        // do not want to invoke the function handleAuthorizationFailure
        // if an exception gets thrown in handleAuthorizationSuccess.
        $this->handleAuthorizationSuccess($authorizedPayment, $subscription);
    }

    protected function authorizePayment(array $recurringPayload)
    {
        $recurringPayment = $this->processor->process($recurringPayload);

        $authorizedPayment = $this->repo->findOrFail($recurringPayment['razorpay_payment_id']);

        return $authorizedPayment;
    }

    protected function handleAuthorizationSuccess(Payment\Entity $authorizedPayment, Entity $subscription)
    {
        $authorizedPayment->subscription()->associate($subscription);
        $this->repo->saveOrFail($authorizedPayment);

        $this->resetErrorFields($subscription);
        $this->repo->saveOrFail($subscription);

        try
        {
            $capturedPayment = $this->capturePayment($authorizedPayment);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $this->handleCaptureFailure($subscription);

            return;
        }

        // This is being done outside the try-catch block because we do not
        // want to invoke handleCaptureFailure if an exception gets thrown
        // in handleCaptureSuccess flow.
        $this->handleCaptureSuccess($subscription, $capturedPayment);
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

        if ($authAttempts < self::MAX_AUTH_ATTEMPTS)
        {
            $subscription->setStatus(Status::ON_HOLD);
            $this->incrementChargeAtByOneDay($subscription);
        }
        else if ($authAttempts === self::MAX_AUTH_ATTEMPTS)
        {
            $subscription->setStatus(Status::FAILED);
            $subscription->setFailedAt(time());

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

    public function handleCaptureSuccess(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $plan = $subscription->plan;

        $subscription->setStatus(Status::PROCESSED);

        $this->resetErrorStatusForSuccessfulCapture($subscription, $capturedPayment);

        $this->setCurrentPeriod($subscription, $plan);

        $this->setNextChargeAt($subscription, $plan);

        $this->incrementPaidCount($subscription);

        $this->setEndedAtIfApplicable($subscription);

        $this->setProcessedAt($subscription, $capturedPayment);

        $this->repo->saveOrFail($subscription);
    }

    protected function resetErrorStatusForSuccessfulCapture(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $errorStatus = $subscription->getErrorStatus();

        // At this point of the flow, if there is an error, it should be capture failure only.
        // If it was auth_failure, capture shouldn't have been called at all for the payment.

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
    protected function setProcessedAt(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $capturedAt = $capturedPayment->getCaptureTimestamp();

        $subscription->setProcessedAt($capturedAt);
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
        $intervalFunc = $this->getIntervalFunction($plan);

        $intervalCount = $plan->getIntervalCount();

        if ($subscription->getPaidCount() === 0)
        {
            $currentStart = $subscription->getStartAt();
            $subscription->setCurrentStart($currentStart);

            $currentEnd = Carbon::createFromTimestamp($currentStart)->$intervalFunc($intervalCount);
            $subscription->setCurrentEnd($currentEnd->timestamp);
        }
        else
        {
            $currentStart = Carbon::createFromTimestamp($subscription->getCurrentStart());

            $currentStart->$intervalFunc($intervalCount)->timestamp;
            $subscription->setCurrentStart($currentStart);

            // To get $currentEnd, we need to add the same interval to $currentStart (new $currentStart).
            $currentStart->$intervalFunc($intervalCount)->timestamp;
            $subscription->setCurrentEnd($currentStart);
        }
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

    protected function handleCaptureFailure(Entity $subscription)
    {
        $this->trace->error(
            TraceCode::SUBSCRIPTION_PAYMENT_CAPTURE_FAILED,
            [
                'subscription_id'   => $subscription->getId(),
            ]);

        $subscription->setStatus(Status::ON_HOLD);
        $subscription->setErrorStatus(Status::CAPTURE_FAILURE);

        $this->repo->saveOrFail($subscription);
    }

    protected function captureSubscriptionPayment(Entity $subscription, Payment\Entity $payment)
    {
        try
        {
            $paymentId = $payment->getId();
            $paymentAmount = $payment->getAmount();

            $this->processor->capture($paymentId, [Payment\Entity::AMOUNT => $paymentAmount]);

            $subscription->setStatus(Status::PROCESSED);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            $this->trace->error(
                TraceCode::SUBSCRIPTION_PAYMENT_CAPTURE_FAILED,
                [
                    'payment_id'        => $payment->getId(),
                    'subscription_id'   => $subscription->getId(),
                ]);

            $slackData = [
                'error'             => $ex->getMessage(),
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $subscription->getId(),
            ];

            $this->logToSlack($slackData);

            $subscription->setStatus(Status::FAILED);
        }

        $this->repo->saveOrFail($subscription);
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

    protected function getIntervalFunction(Plan\Entity $plan)
    {
        $interval = $plan->getInterval();

        return 'add' . $interval . 's';
    }
}