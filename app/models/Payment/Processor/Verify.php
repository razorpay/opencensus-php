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

        $refunds = $payment->refunds;

        $data = array(
            'payment' => $payment->toArray(),
            'refunds' => $refunds->toArray(),
        );

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
        $channel = '#transactions';
        $username = 'transactions';

        $message = '@harhsil @shk Payment verification failed for ' .
                    'payment id - ' . $payment->getPublicId() . ', ' .
                    'amount - ' . $payment->getAmount();

        $app = \App::getFacadeRoot();
        $app['slack']->send($message, $channel, $username);
    }
}
