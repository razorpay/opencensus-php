<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Payment;
use Models\Payment\VerifyResult;
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
            $payment->setVerified(VerifyResult::FAILED);

            $this->repo->saveOrFail($payment);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $data['gateway'] = $e->getData();

            $this->notifyInSlack($data);

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

        $this->slackPost(
            $message,
            $data,
            [
                'color' => 'bad',
                'icon' => ':boom:',
                'channel' => '#tech_logs'
            ]);
    }
}
