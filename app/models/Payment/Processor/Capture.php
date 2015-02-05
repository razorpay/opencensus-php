<?php

namespace Models\Payment\Processor;

use Models\Merchant;
use Models\Payment;
use Models\Transaction;
use Trace\TraceCode;

trait Capture
{
    /**
     * Capture a previous auth payment
     *
     * @param  string           $id  Id of payment to be captured
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function capture($id, array $input = array())
    {
        $payment = $this->retrieve($id);

        (new Payment\Validator)->captureValidate($payment, $input);

        return $this->capturePayment($payment, $input['amount']);
    }

    public function capturePayment($payment, $amount)
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

            $txn = (new Transaction\Core)->createFromPayment($this->payment);

            $txn->save();
            $this->payment->save();
        });

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);
    }

    protected function updatePaymentCaptured()
    {
        $this->payment->setStatus(Payment\Status::CAPTURED);

        $this->payment->setCaptureTimestamp();

        $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
    }
}
