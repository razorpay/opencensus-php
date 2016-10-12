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

        try
        {
            $this->processor = $this->getNewProcessor($subscription->merchant);

            $recurringPayment = $this->processor->process($recurringPayload);

            $job->delete();
        }
        catch (\Exception $ex)
        {
            $data['job_attempts'] = $job->attempts();

            $this->trace->error(
                TraceCode::SUBSCRIPTION_PAYMENT_AUTHORIZE_FAILED,
                $data
            );

            $this->trace->traceException($ex);

            $slackData = [
                'job_attempts'      => $data['job_attempts'],
                'token'             => $data['token'],
                'customer_id'       => $data['customer_id'],
                'subscription_id'   => $data['subscription_id'],
                'error'             => $ex->getMessage(),
            ];

            // Will remove this after a couple of months.
            $this->logToSlack($slackData, $ex);

            if ($job->attempts() > self::MAX_JOB_ATTEMPTS)
            {
                $subscription->setStatus(Status::FAILED);

                $this->repo->saveOrFail($subscription);

                $job->delete();
            }
            else
            {

                $job->release(self::JOB_RELEASE_WAIT);
            }

            return;
        }

        $payment = $this->repo->findOrFail($recurringPayment['razorpay_payment_id']);

        $this->processSuccessfulSubscriptionPayment($subscription, $payment);
    }

    protected function processSuccessfulSubscriptionPayment(Entity $subscription, Payment\Entity $payment)
    {
        $this->captureSubscriptionPayment($subscription, $payment);

        $this->setNextChargeAt($subscription);

        $this->setEndedAtIfApplicable($subscription);

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

    protected function setNextChargeAt(Entity $subscription)
    {
        $plan = $subscription->plan;

        $interval = $plan->getInterval();

        $intervalCount = $plan->getIntervalCount();

        $currentChargeAt = $subscription->getChargeAt();

        $currentChargeAt = Carbon::createFromTimestamp($currentChargeAt);

        $intervalFunc = 'add' . $interval . 's';

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

    protected function setEndedAtIfApplicable(Entity $subscription)
    {
        if ($subscription->getChargeAt() === null)
        {
            $subscription->setEndedAt(time());
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

    protected function getNewProcessor(Merchant\Entity $merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}