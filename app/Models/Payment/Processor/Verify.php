<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\VerifyResult;

trait Verify
{

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
            $this->updatePaymentVerified($payment, VerifyResult::FAILED, $filter);

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
            $this->updatePaymentVerified($payment, VerifyResult::ERROR, $filter);

            throw $e;
        }

        $this->updatePaymentVerified($payment, VerifyResult::SUCCESS, $filter);

        $data['payment'] = $payment->toArrayAdmin();

        return $data;
    }

    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $filter)
    {
        //For payment in created state don't update Payment
        if ($payment->getStatus() === Status::CREATED)
        {
            return;
        }

        //  if filter is null, then verify is initiated manually, not via Cron
        //  Dont update VERIFY_BUCKET, in that case
        if ($filter !== null)
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
        // Use the message from $data if it has one
        if (isset($data['message']))
        {
            $message = $data['message'];
            unset($data['message']);
        }
        else
        {
            $message = 'Payment verification failed.';
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
