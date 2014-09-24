<?php

namespace Models\Payment\Refund;

use Models\Merchant;
use Models\Payment;
use Models\Payment\Action;
use Models\Payment\Refund;
use Trace\TraceCode;

class Process extends Action
{
    /**
     * Refunds a payment
     * @param  string   $id  Payment Id
     *
     * @return Payment\Entity
     */
    public function process($id, $input)
    {
        $payment = $this->retrieve($id);

        $refund = (new Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        $this->refund = $refund;

        $data = array(
                    'payment' => $payment->toArrayWithCard(),
                    'amount' => $refund->getAmount());

        try
        {
            $this->callGatewayFunction(Payment\Action::REFUND, $data);

            $this->recordRefund();

            //
            // Analytics
            //
            $this->dashboardQueueRecord($payment);
        }
        catch(BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            throw $e;
        }

        return $refund;
    }

    protected function recordRefund()
    {
        $this->repo->transaction(function()
        {
            $this->repo->lockForUpdate($this->payment->getKey());

            // (new Ledger\Core)->recordRefund($this->payment);

            $this->updatePaymentRefunded();

            $this->payment->save();
            $this->refund->save();
        });
    }

    protected function updatePaymentRefunded()
    {
        $this->payment->refundAmount($this->refund->getAmount());

        $this->trace(TraceCode::PAYMENT_REFUND_SUCCESS);
    }
}
