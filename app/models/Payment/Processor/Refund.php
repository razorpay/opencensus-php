<?php

namespace Models\Payment\Processor;

use BasicAuth;
use EE\Exception;
use Http\Route;
use Models\Card;
use Models\Payment;
use Models\Transaction;
use Request;
use Trace\Trace;
use Trace\TraceCode;

trait Refund
{
    /**
     * Refunds a payment
     * @param  string   $id  Payment Id
     *
     * @return Payment\Entity
     */
    public function refund($id, $input)
    {
        $payment = $this->retrieve($id);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        $this->refund = $refund;

        $data = array(
                    'payment' => $payment->toArray(),
                    'refund' => $refund->toArray(),
                    'amount' => $refund->getAmount());

        $method = $refund->payment->getMethod();

        if ($method === Payment\Method::CARD)
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        try
        {
            $this->callGatewayFunction(Payment\Action::REFUND, $data);

            $this->recordRefund();

            //
            // Analytics
            //
            $this->notifyDashboard('refund', $this->refund);
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

            $this->updatePaymentRefunded();

            $txn = (new Transaction\Core)->createFromRefund($this->refund);

            $txn->save();
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