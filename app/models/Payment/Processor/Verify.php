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
    public function verify($id)
    {
        $payment = $this->retrieve($id);

        $data = array(
            'payment' => $payment->toArray());

        try
        {
            $data = $this->callGatewayFunction(Payment\Action::VERIFY, $data);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $payment->setVerified(false);

            $this->repo->saveOrFail($payment);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $this->notifyInSlack($payment);

            throw $e;
        }

        $payment->setVerified(true);

        $this->repo->saveOrFail($payment);

        return $payment;
    }

    protected function notifyInSlack($payment)
    {
        $data = [
            'payment_id'    =>  $payment->getPublicId(),
            'amount'        =>  $payment->getAmount()
        ];

        $message = 'Payment verification failed';

        $this->slackPost($message, $data, '@harshil @shk', ['color'=>'bad']);
    }
}
