<?php

namespace Models\Payment\Processor;

use BasicAuth;
use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Card;
use Models\Merchant;
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
    protected function refund($id, $input)
    {
        $payment = $this->retrieve($id);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        if ($this->payment->isCaptured())
        {
            $this->validateMerchantBalance($refund);
        }

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

        $this->callGatewayForRefund($payment, $data);

        //
        // Analytics
        //
        $this->notifyDashboard('refund', $this->refund);

        return $refund;
    }

    public function refundAuthorizedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

        if ($this->payment->isAuthorized() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        return $this->refund($id, $input);
    }

    public function refundCapturedPayment($id, $input)
    {
        $payment = $this->retrieve($id);

        if ($this->payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        return $this->refund($id, $input);
    }

    protected function callGatewayForRefund($payment, $data)
    {
        try
        {
            $gateway = $payment->getGateway();

            if ((Payment\Gateway::supportsAuthAndCapture($gateway) === false) or
                ($payment->isAuthorized() === false))
            {
                $this->callGatewayFunction(Payment\Action::REFUND, $data);
            }

            $this->recordRefund();
        }
        catch(BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            throw $e;
        }
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

    protected function validateMerchantBalance($refund)
    {
        $merchant = $refund->merchant;

        $balance = (new Merchant\Repository)->getMerchantBalance($merchant);

        if ($balance->getBalance() < $refund->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE);
        }
    }
}