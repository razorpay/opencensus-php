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
        $data = [
            'payment_id'    =>  $payment->getPublicId(),
            'amount'        =>  $payment->getAmount()
        ];

        $message = 'Payment verification failed. ' .
                    'data - ' . json_encode($data, JSON_PRETTY_PRINT);

        $this->slackPost($message, $data, '@harshil @shk', ['color'=>'bad']);
    }
}
