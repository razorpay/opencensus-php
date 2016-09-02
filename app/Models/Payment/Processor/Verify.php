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
            $payment->setVerified(VerifyResult::FAILED);

            $this->repo->saveOrFail($payment);

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
            $payment->setVerified(VerifyResult::ERROR);

            $this->repo->saveOrFail($payment);

            throw $e;
        }

        $payment->setVerified(VerifyResult::SUCCESS);

        $data['payment'] = $payment->toArrayAdmin();

        $this->repo->saveOrFail($payment);

        return $data;
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
