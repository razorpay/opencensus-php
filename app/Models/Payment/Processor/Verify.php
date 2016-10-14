<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\VerifyResult;

trait Verify
{

    public function verify($payment)
    {
        $this->setPayment($payment);

        $refunds = $payment->refunds;

        $data = array(
            'payment' => $payment->toArray(),
            'refunds' => $refunds->toArray(),
        );

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
            $this->updatePayment($payment, VerifyResult::FAILED);

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
            $this->updatePayment($payment, VerifyResult::ERROR);

            throw $e;
        }

        $this->updatePayment($payment, VerifyResult::SUCCESS);

        $data['payment'] = $payment->toArrayAdmin();

        return $data;
    }

    protected function updatePayment($payment, $verifyStatus)
    {
        //TODO : move this boundary to common place

        $boundary = [
            1 =>  15*60,            // 15 minute
            2 =>  60*60,            // 60 minute
            3 =>  1440*60,          // 1 day
            4 =>  2880*60,          // 2 day
            5 =>  4320*60,          // 3 day
            6 =>  5760*60,          // 4 day
            7 =>  7200*60,          // 5 day
            8 =>  8640*60,          // 6 day
            9 =>  10080*60,         // 7 day
            10 => 11520*60          // anything in this bucket will be skipped for verify
        ];

        $payment->setVerified($verifyStatus);

        $diff = time() - $payment->getCreatedAt();

        $verfiyBucket = 0;

        foreach ($boundary as $key => $value)
        {
            if($diff >= $value)
            {
                $verfiyBucket = $key;
            }
        }

        $payment->setVerifyBucket($verfiyBucket);

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
