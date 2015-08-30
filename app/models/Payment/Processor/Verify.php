<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

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

            $this->notifyInSlack($data);

            throw $e;
        }

        $payment->setVerified(true);

        $data['payment'] = $payment->toArrayAdmin();

        $this->repo->saveOrFail($payment);

        return $data;
    }

    protected function notifyInSlack($data)
    {
        $channel = '#transactions';
        $username = 'transactions';

        $message = 'Payment verification failed. ' .
                    'data - ' . json_encode($data, JSON_PRETTY_PRINT);

        $app = \App::getFacadeRoot();
        $app['slack']->send($message, $channel, $username);
    }
}
