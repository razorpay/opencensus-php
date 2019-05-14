<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Exception;
use RZP\Constants;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Models\Payment\Verify\Action as VerifyAction;

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
        $this->app['diag']->trackPaymentVerifyEvent(EventCode::PAYMENT_VERIFICATION_INITIATED, $payment);

        $this->setPayment($payment);

        $refunds = $this->repo->refund->findForPayment($payment);

        $data = [
            'payment' => $payment->toArrayGateway(),
            'refunds' => $refunds->toArrayGateway(),
            'merchant' => $this->merchant,
        ];

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $this->repo->card->fetchForPayment($payment)->toArray();
        }

        // So that verification calls can be made with the relevant token related information
        if ($payment->getGlobalOrLocalTokenEntity())
        {
            $data['token'] = $payment->getGlobalOrLocalTokenEntity();
        }

        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);

            $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS, $data['gateway']);

            $data['payment'] = $payment->toArrayAdmin();

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $action = $e->getAction();

            // If action is BLOCK, RETRY, FINISH we don't update Verify Status
            if ($action === null)
            {
                $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $e->getData());

                $this->trace->info(
                    TraceCode::PAYMENT_VERIFY_FAILED,
                    $e->getData());
            }
            else
            {
                $this->updatePaymentVerified($payment, VerifyStatus::UNKNOWN);
            }

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }
        catch (\Error $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }

        return $data;
    }

    /**
     * Update Payment attributes after running verify
     * @param Payment\Entity $payment payment for which attributes should be updated
     * @param string $verifyStatus status of verify
     * @param array $gatewayData contains error, request , response and other gateway data
     * @return void
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $gatewayData = null)
    {
        $payment->setVerified($verifyStatus);

        $this->updateErrorInPaymentFromGatewayIfApplicable($payment, $gatewayData);

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
                'color'   => 'bad',
                'icon'    => ':boom:',
                'channel' => Config::get('slack.channels.tech_logs_verify')
            ]);
    }

    protected function updateErrorInPaymentFromGatewayIfApplicable($payment, $data)
    {
        if (empty($data['error']) === true)
        {
            return;
        }

        $error = $data['error'];

        $internalErrorCode = $error['internal_error_code'];

        $errorCode = $error['code'];

        $errorDescription = $error['description'];

        $payment->setError($errorCode, $errorDescription, $internalErrorCode);
    }
}
