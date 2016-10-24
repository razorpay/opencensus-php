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
    /*
     * Run Verify on a given Payment
     *
     * @param Payment\Entity $payment Payment for which verify should be ran
     * @param string         $filter  Filter used for running the verify
     *
     * @return array having refund and payment data
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

    /*
     * Update Payment attributes after running verify
     * @param Payment\Entity $payment       payment for which attributes should be updated
     * @param string         $verifyStatus  status of verify
     * @param string         $filter        filter used for running the verify
     * @return void
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $filter)
    {
        //For payment in created state don't update Payment
        if ($payment->getStatus() === Status::CREATED)
        {
            return;
        }

        $app = App::getFacadeRoot();

        //  if filter is null, then verify is initiated manually, not via Cron
        //  Dont update VERIFY_BUCKET, in that case
        if (($app['basicauth']->isCron() === true) or
            (($this->mode === 'test') and ($filter !== null)))
        {
            // Get Verify Boundary to update Verify Bucket
            $boundary = Constants\Verify::getBoundaryInSeconds($filter);

            $diff = Carbon::now()->timestamp - $payment->getCreatedAt();

            $verifyBucket = 0;

            foreach ($boundary as $key => $value)
            {
                if ($diff >= $value)
                {
                    $verifyBucket = $key;
                }
            }

            $payment->setVerifyBucket($verifyBucket + 1);
        }

        $payment->setVerified($verifyStatus);

        $this->repo->saveOrFail($payment);
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
