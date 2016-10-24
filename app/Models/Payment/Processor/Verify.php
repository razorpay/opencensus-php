<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Constants;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Status;

trait Verify
{
    /**
     * Run Verify on a given Payment
     *
     * @param Payment\Entity $payment Payment for which verify should be ran
     * @param string         $filter  Filter used for running the verify
     *
     * @return array having refund and payment data
     * @throws Exception\PaymentVerificationException
     * @throws \Exception
     */
    public function verify(Payment\Entity $payment, $filter = null)
    {
        $this->setPayment($payment);

        $refunds = $payment->refunds;

        $data = [
            'payment' => $payment->toArray(),
            'refunds' => $refunds->toArray(),
        ];

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->updatePaymentVerified($payment, Constants\Verify::VERIFIED_FAILED, $filter);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $data['gateway'] = $e->getData();

            $slackData = ['id' => $payment->getDashboardEntityLinkForSlack()];

            $this->notifyInSlack($slackData);

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, Constants\Verify::VERIFIED_ERROR, $filter);

            throw $e;
        }

        $this->updatePaymentVerified($payment, Constants\Verify::VERIFIED_SUCCESS, $filter);

        $data['payment'] = $payment->toArrayAdmin();

        return $data;
    }

    /**
     * Update Payment attributes after running verify
     *
     * @param Payment\Entity $payment       payment for which attributes should be updated
     * @param string         $verifyStatus  status of verify
     * @param string         $filter        filter used for running the verify
     * @return void
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $filter)
    {

        // For Payment in created state, verify bucket should not be updated
        // as we want to run cron on specific interval, till payment is marked as failed/authorized
        // If filter is null, then verify is initiated manually, not via cron
        // Don't update VERIFY_BUCKET, in that case
        if (($payment->getStatus() !== Status::CREATED) and
            (($this->app['basicauth']->isCron() === true) or
            (($this->mode === 'test') and ($filter !== null))))
        {
            // Get Verify Boundary to update Verify Bucket
            $boundaries = Constants\Verify::getBoundaryInSeconds($filter);

            $diff = Carbon::now('Asia/Kolkata')->timestamp - $payment->getCreatedAt();

            $currentVerifyBucket = $this->getCurrentVerifyBucket($diff, $boundaries);

            $nextVerifyBucket = $currentVerifyBucket + 1;

            // We need to set the next verify bucket for the cron to pick up.
            $payment->setVerifyBucket($nextVerifyBucket + 1);
        }

        $payment->setVerified($verifyStatus);

        $this->repo->saveOrFail($payment);
    }

    protected function getCurrentVerifyBucket($diff, $boundaries)
    {
        $verifyBucket = 0;

        foreach ($boundaries as $key => $value)
        {
            if ($diff >= $value)
            {
                $verifyBucket = $key;
            }
        }

        return $verifyBucket;
    }

    protected function notifyInSlack(array $data)
    {
        $message = 'Payment verification failed.';

        // Use the message from $data if it has one
        if (isset($data['message']))
        {
            $message = $data['message'];
            unset($data['message']);
        }

        $app = App::getFacadeRoot();

        $app['slack']->queue(
            $message,
            $data,
            [
                'color' => 'bad',
                'icon' => ':boom:',
                'channel' => Config::get('slack.channels.tech_logs')
            ]);
    }
}
