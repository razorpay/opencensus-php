<?php

namespace Models\Payment;

use Models\Merchant;
use Models\Payment;
use Trace\TraceCode;

class Capture extends Action
{
    /**
     * Capture a previous auth payment
     *
     * @param  string              $id  Id of payment to be captured
     *
     * @return Payment\Entity       Payment\Entity object
     */
    public function process($id, array $input = array())
    {
        $payment = $this->retrieve($id);

        (new Payment\Validator)->captureValidate($payment, $input);

        $data = array(
                    'payment' => $payment->toArrayWithCard(),
                    'amount' => $input['amount']);

        $payment->setCaptureAmount($input['amount']);

        try
        {
            $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

            $this->recordCapture();

            //
            // Analytics
            //
            $this->dashboardQueueRecord($payment);
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $e;
        }

        return $payment;
    }

    protected function recordCapture()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->payment->getKey());

            // (new Transaction\Core)->recordCapture($this->$payment);

            $this->updatePaymentCaptured();

            $this->payment->save();
        });
    }


    protected function updatePaymentCaptured()
    {
        $this->payment->setStatus(Payment\Status::CAPTURED);

        $this->payment->setCaptureTimestamp();

        $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
    }
}
