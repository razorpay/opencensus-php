<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Status;

trait Verify
{
    /*
     * Run Verify on a given Payment
     * Params : $payment - Payment for which verify should be ran
     *          $filter  - Filter used for running the verify
     *                     null - if ran via dashboard/manually
     *                     all/error/failure/created - via cron
     * Returns : Array having refund and payment data
     */
    public function verify($payment, $filter = null)
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
     * Params : $payment      - Payment for which attributes should be updated
     *          $verifyStatus - Status of verify -
     *                          either of these ERROR,SUCCESS,FAILED
     *          $filter       - Filter used for running the verify
     *                          null - if ran via dashboard/manually
     *                          all/error/failure/created - via cron
     * Returns : null
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $filter)
    {
        //For payment in created state don't update Payment
        if ($payment->getStatus() === Status::CREATED)
        {
            return;
        }

        //  if filter is null, then verify is initiated manually, not via Cron
        //  Dont update VERIFY_BUCKET, in that case
        if (is_null($filter) === true)
        {
            $daysToAdd = 1;

            // Get Verify Boundary to update Verify Bucket
            // We are adding a day when setting Verify Boundary
            // This will prevent cron to pick payments which have crossed last boundary
            $boundary = Constants\Verify::getBoundaryInSeconds($filter, $daysToAdd);

            $diff = time() - $payment->getCreatedAt();

            $verifyBucket = 0;

            foreach ($boundary as $key => $value)
            {
                if ($diff >= $value)
                {
                    $verifyBucket = $key;
                }
            }

            $payment->setVerifyBucket($verifyBucket);
        }

        $payment->setVerified($verifyStatus);

        $this->repo->saveOrFail($payment);
    }

    protected function notifyInSlack($data)
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
