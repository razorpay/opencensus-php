<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Transaction;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

trait Capture
{
    /**
     * Captures a previous auth payment
     *
     * @param  string  $id  Id of payment to be captured
     * @param  array $input
     *
     * @return Payment\Entity   Payment\Entity object
     */
    public function capture($id, array $input = array())
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_REQUEST,
            [
                'payment_id' => $id,
                'input' => $input,
            ]
        );

        $payment = $this->retrieve($id);

        //
        // If the fee bearer is customer then please to adjust input amount
        // with the available fee for the payment.
        //
        if ($this->merchant->isFeeBearerCustomer())
        {
            $input['amount'] = $input['amount'] + $payment->getFee();

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_REQUEST,
                [
                    'payment_id' => $id,
                    'amount' => $input['amount'],
                    'message' => 'Adds fee to the amount because fee bearer is customer',
                ]
            );
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
            'payment'   => $payment->toArray(),
            'amount'    => $amount
        );

        if (($payment->getMethod() === Payment\Method::CARD) or
            ($payment->getMethod() === Payment\Method::EMI))
        {
            $data['card'] = $payment->card->toArray();
        }

        $payment->setCaptureAmount($amount);

        $this->captureOnGateway($data);

        return $payment;
    }

    /**
     * If gateway call for capture times out, we catch the exception thrown
     * and push it into a queue. We continue with the normal flow afterwards.
     *
     * @param $data
     * @throws Exception\BaseException
     */
    protected function captureOnGateway($data)
    {
        $this->verifyOrderUnpaid($this->payment);

        $paymentCopy = $this->payment->replicate();

        try
        {
            try
            {
                $this->callGatewayFunction(Payment\Action::CAPTURE, $data);
            }
            catch (Exception\GatewayTimeoutException $ex)
            {
                $this->trace->traceException($ex);

                $data['mode'] = $this->mode;

                $this->trace->info(
                    TraceCode::PAYMENT_CAPTURE_ADD_TO_QUEUE, ['payment_id' => $this->payment->getId()]
                );

                $this->app['queue']->push('RZP\Jobs\Capture', ['data' => $data]);
            }

            $this->recordCapture();
        }
        catch (Exception\BadRequestException $ex)
        {
            // For validation failures, we shouldn't mark capture as failed ever.
            throw $ex;
        }
        catch (Exception\BadRequestValidationFailureException $ex)
        {
            // For validation failures, we shouldn't mark capture as failed ever.
            throw $ex;
        }
        catch (Exception\BaseException $ex)
        {
            //
            // We need to use the old payment
            // because the recordCapture would have made some changes
            // to payment entity but not committed due to which payment
            // entity will have corrupted data
            //

            if ($ex->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT)
            {
                // If pricing rule is not found, we should not mark capture as failed ever.
                throw $ex;
            }

            $this->payment = $paymentCopy;

            $this->updatePaymentFailed(
                    $ex->getError(),
                    TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $ex;
        }
    }

    protected function recordCapture()
    {
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment)
        {
            $this->lockForUpdateAndReload($payment);

            if ($payment->hasBeenCaptured() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED);
            }

            $this->updatePaymentCaptured($payment);

            $this->createTransactionFromCapturedPayment($payment);

            $this->updatePaidOrderStatus($payment);

            $this->trace(TraceCode::PAYMENT_CAPTURE_SUCCESS);
        });

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);

        $notifier = new Notify($this->payment);
        $notifier->trigger(Notify::CAPTURED);
    }

    protected function updatePaymentCaptured($payment)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setCaptureTimestamp();
    }

    protected function createTransactionFromCapturedPayment(Payment\Entity $payment)
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
        $order = $this->repo->order->getOrderForPayment($payment);

        if ((empty($order) === false) and
            ($order->getStatus() === Order\Status::PAID))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Corresponding order already has a captured payment.');
        }
    }

    protected function updatePaidOrderStatus($payment)
    {
        $order = $payment->order;

        if (isset($order) === true)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_ORDER_UPDATE,
                [
                    'payment_id' => $payment->getId(),
                    'order_id' => $order->getId(),
                ]
            );

            $order->setStatus(Order\Status::PAID);

            $order->saveOrFail();

            // TODO: Should we de-couple orders and invoices? With more complexity
            // in invoices, the orders flow might get messy and complicated.
            // If we add more features which use orders, this flow will get really bad.
            if ($order->invoice !== null)
            {
                $this->updatePaidInvoiceStatus($order);
            }
        }
    }

    protected function updatePaidInvoiceStatus($order)
    {
        $invoice = $order->invoice;

        assert($invoice);

        if ($invoice->getStatus() === Invoice\Status::PAID)
        {
            throw new Exception\LogicException(
                'The invoice is already paid for.',
                null,
                [
                    'payment_id'    => $order->payment->getId(),
                    'invoice_id'    => $invoice->getId(),
                    'order_id'      => $order->getId(),
                ]);
        }

        $invoice->setStatus(Invoice\Status::PAID);

        $invoice->saveOrFail();
    }
}
