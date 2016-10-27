<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Constants;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Verify\Status as VerifyStatus;

trait Verify
{
    /**
     * Run Verify on a given Payment
     *
     * @param Payment\Entity $payment Payment for which verify should be ran
     *
     * @return array having refund and payment data
     * @throws Exception\PaymentVerificationException
     * @throws \Exception
     */
    public function verify(Payment\Entity $payment)
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
            $this->updatePaymentVerified($payment, VerifyStatus::FAILED);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $data['gateway'] = $e->getData();

            $slackData = ['id' => $payment->getDashboardEntityLinkForSlack()];

            // @todo: No need now to notify on individual payments verify failure.
            // $this->notifyInSlack($slackData);

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            throw $e;
        }

        $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS);

        $data['payment'] = $payment->toArrayAdmin();

        return $data;
    }

    /**
     * Update Payment attributes after running verify
     *
     * @param Payment\Entity $payment       payment for which attributes should be updated
     * @param string         $verifyStatus  status of verify
     * @return void
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus)
    {
        $payment->setVerified($verifyStatus);

        $this->repo->saveOrFail($payment);
    }

    protected function notifyInSlack(array $data)
    {
        $message = 'Payment verification failed.';

        // Use the message from $data if it has one
        if (isset($data['message']))
        {
            $message = $data['message'];
            unset($data['message']);
        }

        $this->app['slack']->queue(
            $message,
            $data,
            [
                'color' => 'bad',
                'icon' => ':boom:',
                'channel' => Config::get('slack.channels.tech_logs')
            ]);
    }
}
