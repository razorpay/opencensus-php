<?php

namespace RZP\Models\Plan\Subscription;

use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
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

        $this->processor = $this->getNewProcessor($subscription->merchant);

        $success = true;
        $authorizedPayment = null;

        $subscription->incrementAuthAttempts();

        try
        {
            $authorizedPayment = $this->authorizePayment($recurringPayload);
        }
        catch (\Exception $ex)
        {
            $success = false;

            $this->handleAuthorizationFailure($job, $ex, $subscription);
        }

        $this->repo->saveOrFail($subscription);

        $job->delete();

        if ($success === true)
        {
            $this->handleAuthorizationSuccess($authorizedPayment, $subscription);
        }
    }

    protected function authorizePayment(array $recurringPayload)
    {
        $recurringPayment = $this->processor->process($recurringPayload);

        $authorizedPayment = $this->repo->findOrFail($recurringPayment['razorpay_payment_id']);

        return $authorizedPayment;
    }

    protected function handleAuthorizationSuccess(Payment\Entity $authorizedPayment, Entity $subscription)
    {
        try
        {
            $capturedPayment = $this->capturePayment($authorizedPayment);

            $this->handleCaptureSuccess($subscription, $capturedPayment);
        }
        catch (\Exception $ex)
        {
            $this->handleCaptureFailure($authorizedPayment);
            return;
        }
    }

    protected function handleAuthorizationFailure(array $job, \Exception $ex, Entity $subscription)
    {
        $subscription->setErrorStatus(Status::AUTH_FAILURE);

        if ($subscription->getAuthAttempts() < self::MAX_AUTH_ATTEMPTS)
        {
            $subscription->setStatus(Status::ON_HOLD);
        }
        else
        {
            $subscription->setStatus(Status::FAILED);
        }

        $this->repo->saveOrFail($subscription);
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

    protected function handleCaptureSuccess(Entity $subscription, Payment\Entity $capturedPayment)
    {
        $plan = $subscription->plan;

        $subscription->setStatus(Status::PROCESSED);

        $this->setCurrentPeriod($subscription, $plan);

        $this->setNextChargeAt($subscription, $plan);

        $this->incrementPaidCount($subscription);

        $this->setEndedAtIfApplicable($subscription);

        $this->setProcessedAt($subscription, $capturedPayment);

        $this->repo->saveOrFail($subscription);
    }

    /**
     * This basically uses the captured_at.
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
     * Gets the current chargeAt and adds the interval to it to get the nextChargeAt.
     * If the nextChargeAt is greater than the endAt, we set the chargeAt to null.
     *
     * @param Entity $subscription
     * @param Plan\Entity $plan
     */
    protected function setNextChargeAt(Entity $subscription, Plan\Entity $plan)
    {
        $currentChargeAt = $subscription->getChargeAt();

        $currentChargeAt = Carbon::createFromTimestamp($currentChargeAt);

        $intervalFunc = $this->getIntervalFunction($plan);

        $intervalCount = $plan->getIntervalCount();

        // Modifies currentChargeAt variable.
        $currentChargeAt->$intervalFunc($intervalCount);

        $nextChargeAt = $currentChargeAt->timestamp;

        $endAt = $subscription->getEndAt();

        if ($nextChargeAt > $endAt)
        {
            $nextChargeAt = null;
        }

        $subscription->setChargeAt($nextChargeAt);
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

    protected function handleCaptureFailure()
    {

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

    protected function getNewProcessor(Merchant\Entity $merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    protected function getIntervalFunction(Plan\Entity $plan)
    {
        $interval = $plan->getInterval();

        return 'add' . $interval . 's';
    }
}