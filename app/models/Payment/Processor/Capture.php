<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Merchant;
use Models\Payment;
use Models\Order;
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

        /*
            If the fee bearer is customer then please to adjust input amount
            with the available fee for the payment.
         */
        if ($this->merchant->isFeeBearerCustomer())
        {
            $input['amount'] = $input['amount'] + $payment->getFee();
        }

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

        if (($payment->getMethod() === Payment\Method::CARD) or
            ($payment->getMethod() === Payment\Method::EMI))
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

            $this->verifyOrderUnpaid($this->payment);

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
            $this->paymentRepo->lockForUpdate($this->payment->getKey());

            $this->updatePaymentCaptured();

            $this->createTransactionFromCapturedPayment($this->payment);

            $this->updatePaidOrderStatus($this->payment);

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

        $payment->setServiceTax($txn->getServiceTax());

        if ($this->merchant->isFeeBearerCustomer() === false)
        {
            //set and fee values from txn
            $payment->setFee($txn->getFee());
        }

        $txn->saveOrFail();
        $payment->saveOrFail();
    }

    protected function verifyOrderUnpaid($payment)
    {
        $order = $payment->order;

        if (isset($order) and ($order->getStatus() === Order\Status::PAID))
        {
            throw new Exception\BadRequestValidationFailureException(
            'Corresponding order already has a captured payment.');
        }
    }

    protected function updatePaidOrderStatus($payment)
    {
        $order = $payment->order;

        if (isset($order))
        {
            $order->setStatus(Order\Status::PAID);

            $order->saveOrFail();
        }
    }
}
