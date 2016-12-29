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
use RZP\Models\Base\PublicCollection;

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

        // set the input currency if missing and payment currency is INR
        if ((isset($input['currency']) === false) and
            ($payment->getCurrency() === Payment\Currency::INR))
        {
            $input['currency'] = Payment\Currency::INR;
        }

        $payment->getValidator()->validateInput('capture', $input);

        return $this->capturePayment($payment, $input['amount'], $input['currency']);
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

        if ($this->merchant->isFeeBearerCustomer())
        {
            $amount -= $payment->getFee();
        }

        // set auto-capture 1
        $payment->setAutoCapturedTrue();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_CAPTURE, ['payment_id' => $payment->getId()]);

        $this->app['segment']->trackPayment($payment, TraceCode::PAYMENT_AUTO_CAPTURE);

        $currency = $payment->getCurrency();

        try
        {
            $payment = $this->capturePayment($payment, $amount, $currency);
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->error(
                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                [
                    'auto_capture' => true,
                    'payment_id' => $payment->getPublicId()
                ]);

            $customProperties = [
                'error' => $e->getError(),
                'public_error' => $e->getPublicError(),
                'errMsg' => $e->getDataAsString()
            ];

            $this->app['segment']->trackPayment($payment,
                                                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                                                $customProperties);

            return false;
        }

        return true;
    }

    /**
     * We check if the capture status on the gateway is successful (by checking on gateway and gateway_captured
     * flag in the payment entity) and on the api, it's not captured.
     * If it's successful on the gateway side, we create a transaction on the api side from authorized state.
     * This does not follow the convention where all authAndCapture supported gateways should have
     * the payment in captured state for a transaction to be created. But, these are edge cases where capture
     * succeeded on gateway and failed on api side due to some reason. This should ideally never happen.
     * We cannot create a transaction by capturing it because, the merchant may not actually want to capture
     * this payment anymore. Hence, we just create a transaction and leave it at that.
     *
     * If the merchant wants to capture the payment later, he can capture it. We will not send a request
     * to the gateway for capture (since gateway captured flag would be set). We will just record the capture
     * in our system and update the existing transaction for the payment, as applicable.
     *
     * NOTE: This function is not really needed now since we have gateway_captured flag in the payment entity.
     * If it is set, we just record the capture in our system (without calling gateway) and go through the normal flow.
     *
     * TODO: add segment here
     *
     * @param Payment\Entity $payment
     *
     * @return array
     */
    public function verifyCapture(Payment\Entity $payment)
    {
        $this->setPayment($payment);

        // If it has already been captured, we would have a transaction for it.
        assert ($payment->hasBeenCaptured() === false);

        // If the transaction is already present for this, we should not be running verifyCapture at all.
        assert ($payment->getTransactionId() === null);

        // The payment should be in authorized or refunded state only.
        assert ($payment->isStatusCreatedOrFailed() === false);

        // Currently going to do this only for HDFC. If we find issues with other gateways too,
        // we will add the support for them.
        assert ($payment->getGateway() === Payment\Gateway::HDFC);

        $data = [
            'payment'   => $payment->toArray(),
        ];

        $verify = $this->callGatewayForVerifyCapture($data);

        // Here, verify=true means that the payment is captured on the gateway side.
        if ($verify === true)
        {
            $msg = 'Has been captured on gateway. Gateway captured flag is set to false. It\'s a bug!';

            if ($payment->isGatewayCaptured())
            {
                $this->recordTransactionForFailedApiCapture();

                $msg = 'Has been captured on gateway and hence creating a transaction in api.';
            }
        }
        else if ($verify === false)
        {
            $msg = 'Has not been captured on gateway. Not doing anything on the api side.';
        }
        else
        {
            $msg = 'Could not perform verify capture.';
        }

        return ['verify_capture' => $msg];
    }

    protected function callGatewayForVerifyCapture($data)
    {
        try
        {
            $verifyCaptureResult = $this->callGatewayFunction(Payment\Action::VERIFY_CAPTURE, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->tracePaymentFailed(
                $e->getError(),
                TraceCode::PAYMENT_VERIFY_CAPTURE_FAILURE);

            throw $e;
        }

        return $verifyCaptureResult;
    }

    /**
     * Captures the payment.
     *
     * @param  Payment\Entity   $payment
     * @param  integer          $amount
     * @return Payment\Entity
     */
    protected function capturePayment($payment, $amount, $currency)
    {
        //
        // If the fee bearer is customer then please to adjust input amount
        // with the available fee for the payment.
        //
        if ($this->merchant->isFeeBearerCustomer())
        {
            $amount = $amount + $payment->getFee();

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_REQUEST,
                [
                    'payment_id' => $payment->getId(),
                    'amount'     => $amount,
                    'message'    => 'Adds fee to the amount because fee bearer is customer',
                ]);
        }

        $payment->getValidator()->captureValidate($payment, $amount, $currency);

        $data = array(
            'payment'   => $payment->toArrayGateway(),
            'amount'    => $amount,
            'currency'  => $payment->getCurrency()
        );

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        if ($payment->getConvertCurrency() === true)
        {
            $data['amount'] = $payment->getBaseAmount();
            $data['currency'] = Payment\Currency::INR;
        }

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

        $paymentCopy = clone $this->payment;

        try
        {
            $this->acquireMutexOnPayment($this->payment);

            $this->callAndHandleCaptureOnGateway($data);

            $this->recordCapture();
        }
        catch (Exception\BaseException $ex)
        {
            $this->updatePaymentIfApplicableOnCaptureFailure($ex, $paymentCopy);
        }
        finally
        {
            $this->releaseMutexOnPayment($this->payment);
        }
    }

    protected function updatePaymentIfApplicableOnCaptureFailure(
        Exception\BaseException $ex,
        Payment\Entity $paymentCopy)
    {
        // For validation failures, we shouldn't mark capture as failed ever.
        if (($ex instanceof Exception\BadRequestValidationFailureException) or
            ($ex instanceof Exception\BadRequestException) or
            ($ex instanceof Exception\GatewayRequestException))
        {
            throw $ex;
        }

        if ($ex->getCode() === ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT)
        {
            // If pricing rule is not found, we should not mark capture as failed ever.
            throw $ex;
        }

        $this->trace->traceException($ex);

        //
        // We need to use the old payment
        // because the recordCapture would have made some changes
        // to payment entity but not committed due to which payment
        // entity will have corrupted data
        //
        $this->payment = $paymentCopy;

        $this->updatePaymentFailed($ex, TraceCode::PAYMENT_CAPTURE_FAILURE);

        throw $ex;
    }

    protected function callAndHandleCaptureOnGateway(array $data)
    {
        try
        {
            if ($this->payment->isGatewayCaptured() === false)
            {
                $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

                $this->payment->setGatewayCaptured(true);

                // Saving this here itself because recordCapture will perform other actions too,
                // in a transaction, which could fail and end up rolling back.
                $this->repo->saveOrFail($this->payment);
            }
        }
        catch (Exception\GatewayTimeoutException $ex)
        {
            $this->handleGatewayTimeoutOnCapture($data, $ex);
        }
    }

    protected function handleGatewayTimeoutOnCapture(array $data, Exception\GatewayTimeoutException $ex)
    {
        $paymentGateway = $this->payment->getGateway();

        //
        // If the capture times out for HDFC, we mark it as captured on API and add the captureOnGateway
        // to a queue. We then try to capture on HDFC.
        // We do a similar thing for Cybersource. But, right now, we are not adding to the queue. We will
        // fix these later (by around 19th-20th Dec). We need to first check whether capture succeeded or not
        // and only then capture on Cybersource gateway if required. Otherwise, it'll capture multiple times.
        //
        if (($paymentGateway !== Payment\Gateway::HDFC) and
            ($paymentGateway !== Payment\Gateway::CYBERSOURCE))
        {
            throw $ex;
        }

        $curlMessage = strtolower($ex->getData()['message']);

        //
        // GatewayTimeoutException is thrown for various reasons (`checkTimeout`).
        // We want to mark the payment as successful only if the error
        // message says that the operation timed out.
        //
        if (strpos($curlMessage, 'operation timed out') === false)
        {
            throw $ex;
        }

        $this->trace->traceException($ex);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_ADD_TO_QUEUE, ['payment_id' => $this->payment->getId()]
        );

        // We will be removing this piece of code once the capture queue is written
        // for Cybersource to handle. Being tracked in the issue #1842
        if ($paymentGateway === Payment\Gateway::CYBERSOURCE)
        {
            return;
        }

        //
        // Adding a delay here because some gateways return back an error if a capture request
        // is sent within a few seconds of the first capture request.
        // Example : HDFC sends FS00002 error if capture request is sent within 20 seconds of the
        // previous capture request.
        //
        $this->app['queue']->later(self::CAPTURE_QUEUE_DELAY, \RZP\Jobs\Capture::class, ['data' => $data]);
    }

    protected function recordTransactionForFailedApiCapture()
    {
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment)
        {
            $txnCore = new Transaction\Core;

            // This could be actually misleading.
            // We are creating a transaction even if the payment
            // is in refunded state.

            list($txn, $feesSplit) = $txnCore->createFromPaymentAuthorized($payment);

            $this->repo->saveOrFail($txn);
            $this->repo->saveOrFail($payment);

            $this->saveFeeDetails($txn, $feesSplit);

            $this->tracePaymentInfo(TraceCode::TRANSACTION_CREATED_IN_VERIFY_CAPTURE);
        });
    }

    protected function recordCapture()
    {
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment)
        {
            $autoCaptured = $payment->getAutoCaptured();

            $this->lockForUpdateAndReload($payment);

            if ($payment->hasBeenCaptured() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED);
            }

            $this->updatePaymentCaptured($payment, $autoCaptured);

            $this->createTransactionFromCapturedPayment($payment);

            $this->updatePaidOrderStatus($payment);

            $this->tracePaymentInfo(TraceCode::PAYMENT_CAPTURE_SUCCESS);
        });

        $this->eventOrderPaid();
        $this->notifyInvoicePaid();

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);

        $notifier = new Notify($this->payment);
        $notifier->trigger(Notify::CAPTURED);
    }

    protected function eventOrderPaid()
    {
        $payment = $this->payment;

        if ($payment->getApiOrderId() !== null)
        {
            $this->app['events']->fire('api.order.paid', array($payment));
        }
    }

    protected function notifyInvoicePaid()
    {
        $payment = $this->payment;
        $invoice = null;

        if ($payment->getApiOrderId() === null)
        {
            return;
        }

        $order = $payment->order;
        $invoice = $order->invoice;

        if ($invoice === null)
        {
            return;
        }

        $this->eventInvoicePaid($payment);

        $this->communicateInvoicePaid($invoice);
    }

    protected function communicateInvoicePaid(Invoice\Entity $invoice)
    {
        $notifier = new Notify($this->payment, $invoice);

        $trigger = Notify::INVOICE_PAID;

        $notifier->trigger($trigger);
    }

    protected function eventInvoicePaid($payment)
    {
        $this->app['events']->fire('api.invoice.paid', array($payment));
    }

    protected function updatePaymentCaptured($payment, $autoCaptured = false)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setCaptureTimestamp();

        $payment->setAutoCaptured($autoCaptured);

        $this->trace->info(
            TraceCode::PAYMENT_STATUS_CAPTURED,
            [
                'payment_id'    => $payment->getId(),
                'auto_capture'  => $autoCaptured,
            ]);
    }

    protected function createTransactionFromCapturedPayment(Payment\Entity $payment)
    {
        $txnCore = new Transaction\Core;

        $auth = ($payment->transaction === null);

        $feesSplit = new PublicCollection;

        if ($auth === true)
        {
            list($txn, $feesSplit) = $txnCore->createFromPaymentCaptured($payment);
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

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($payment);

        $this->saveFeeDetails($txn, $feesSplit);
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

    protected function updatePaidOrderStatus(Payment\Entity $payment)
    {
        $order = $payment->order;

        if (isset($order) === true)
        {
            $order->setStatus(Order\Status::PAID);

            $this->trace->info(
                TraceCode::ORDER_STATUS_PAID,
                [
                    'payment_id' => $payment->getId(),
                    'order_id' => $order->getId(),
                ]);

            $this->repo->saveOrFail($order);

            if ($order->invoice !== null)
            {
                $this->updatePaidInvoiceStatus($order, $payment);
            }
        }
    }

    protected function updatePaidInvoiceStatus(Order\Entity $order, Payment\Entity $payment)
    {
        $invoice = $order->invoice;

        assert($invoice !== null);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_INVOICE_UPDATE,
            [
                'payment_id'    => $payment->getId(),
                'invoice_id'    => $invoice->getId(),
                'order_id'      => $order->getId(),
            ]);

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

        $this->repo->saveOrFail($invoice);
    }
}
