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
     * @param  string              $id  Id of txn to be captured
     *
     * @return Payment\Entity       Payment\Entity object
     */
    public function process($id, array $input = array())
    {
        $txn = $this->retrieve($id);

        (new Payment\Validator)->captureValidate($txn, $input);

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $input['amount']);

        $txn->setCaptureAmount($input['amount']);

        try
        {
            $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

            $this->recordCapture();

            //
            // Analytics
            //
            $this->dashboardQueueRecord($txn);
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $e;
        }

        return $txn;
    }

    protected function recordCapture()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->txn->getKey());

            // (new Ledger\Core)->recordCapture($this->$txn);

            $this->updatePaymentCaptured();

            $this->txn->save();
        });
    }


    protected function updatePaymentCaptured()
    {
        $this->txn->setStatus(Payment\Status::CAPTURED);

        $this->txn->setCaptureTimestamp();

        $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
    }
}
