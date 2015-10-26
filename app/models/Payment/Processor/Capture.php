<?php

namespace Models\Payment\Processor;

use Models\Merchant;
use Models\Payment;
use Models\Transaction;
use Trace\TraceCode;

trait Capture
{
    /**
     * Captures a previous auth payment
     *
     * @param  string  $id      Id of payment to be captured
     * @param  integer $amount  Amount to capture
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function capture($id, array $input = array())
    {
        $payment = $this->retrieve($id);

        (new Payment\Validator)->captureValidate($payment, $input);

        return $this->capturePayment($payment, $input['amount']);
    }

    /**
     * Captures a payment and sets auto-capture flag true
     *
     * @param  Payment\Entity $payment The payment entity to capture
     * @return boolean
     */
    public function autoCapturePayment($payment)
    {
        $this->payment = $payment;

        $amount = $payment->getAmount();

        // set auto-capture 1
        $payment->setAutoCaptureTrue();

        try
        {
            $payment = $this->capturePayment($payment, $amount);
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->error(
                TraceCode::TRACE_MISC_CODE,
                ['auto_capture' => 1,
                'payment_id' => $payment->getPublicId()]);

            return false;
        }

        return true;
    }

    /**
     * Captures the payment.
     *
     * @param  Payment\Entity   $payment
     * @param  integer          $amount
     * @return Payment\Entity
     */
    protected function capturePayment($payment, $amount)
    {
        $data = array(
            'payment' => $payment->toArray(),
            'amount' => $amount);

        if ($payment->getMethod() === Payment\Method::CARD)
        {
            $data['card'] = $payment->card->toArray();
        }

        $payment->setCaptureAmount($amount);

        $this->captureOnGateway($data);

        return $payment;
    }

    protected function captureOnGateway($data)
    {
        try
        {
            $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

            $this->recordCapture();
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $e;
        }
    }

    protected function recordCapture()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->payment->getKey());

            $this->updatePaymentCaptured();

            $this->createTransactionFromCapturedPayment($this->payment);

            $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
        });

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);

        $notifier = new Notify($this->payment);
        $notifier->trigger(Notify::CAPTURED);
    }

    protected function updatePaymentCaptured()
    {
        $this->payment->setStatus(Payment\Status::CAPTURED);

        $this->payment->setCaptureTimestamp();
    }

    protected function createTransactionFromCapturedPayment($payment)
    {
        $txnCore = new Transaction\Core;

        $auth = ($payment->transaction === null);

        if ($auth === true)
        {
            $txn = $txnCore->createFromPaymentCaptured($payment);
        }
        else
        {
            $txn = $txnCore->updateOnCapture($payment);
        }

        $txn->saveOrFail();
        $payment->saveOrFail();
    }
}
