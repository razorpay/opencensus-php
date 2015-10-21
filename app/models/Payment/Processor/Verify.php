<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;
use Services\SlackPoster;

trait Verify
{
    use SlackPoster;

    public function verify($payment)
    {
        $this->setPayment($payment);

        $refunds = $payment->refunds;

        $data = array(
            'payment' => $payment->toArray(),
            'refunds' => $refunds->toArray(),
        );

        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $payment->setVerified(false);

            $this->repo->saveOrFail($payment);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $data['gateway'] = $e->getData();

            // Slack does not accept nested data
            $slackData = flatten_array($data);

            $this->notifyInSlack($slackData);

            throw $e;
        }

        $payment->setVerified(true);

        $data['payment'] = $payment->toArrayAdmin();

        $this->repo->saveOrFail($payment);

        return $data;
    }

    protected function notifyInSlack($data)
    {
        if (!isset($data['message']))
        {
            $message = 'Payment verification failed.';
        }

        $this->slackPost($message, $data, '', ['color'=>'bad', 'icon' => ':-1:']);
    }
}
