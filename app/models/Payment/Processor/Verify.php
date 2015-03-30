<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Verify
{
    public function verify($id)
    {
        $payment = $this->retrieve($id);

        $data = array(
            'payment' => $payment->toArray(),
            'terminal' => $payment->terminal);

        try
        {
            $data = $this->callGatewayFunction(Payment\Action::VERIFY, $data);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $payment->setVerified(false);

            $this->repo->saveOrFail($payment);

            $this->notifyInSlack($payment);

            throw $e;
        }

        $payment->setVerified(true);
        $this->repo->saveOrFail($payment);

        return $data;
    }

    protected function notifyInSlack($payment)
    {
        $channel = '#transactions';
        $username = 'transactions';

        $id = $payment->getPublicId();

        $message = '@harhsil @shk Payment verification failed for payment id - ' . $id;

        $app = \App::getFacadeRoot();
        $app['slack']->send($message, $channel, $username);
    }
}
