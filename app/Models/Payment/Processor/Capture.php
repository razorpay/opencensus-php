<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Currency;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Models\Plan\Subscription;
use RZP\Models\Transaction;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\Trace;
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
            ($payment->getCurrency() === Currency\Currency::INR))
        {
            $input['currency'] = Currency\Currency::INR;
        }

        $payment->getValidator()->validateInput('capture', $input);

        return $this->capturePayment($payment, $input['amount'], $input['currency']);
    }

    /**
     * Captures a payment and sets auto-capture flag true
     *
     * @param  Payment\Entity $payment The payment entity to capture
     *
     * @throws Exception\BadRequestException
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
        catch (Exception\BaseException $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                [
                    'auto_capture' => true,
                    'payment_id'   => $payment->getPublicId(),
                ]);

            $customProperties = [
                'error' => $e->getError(),
                'public_error' => $e->getPublicError(),
                'errMsg' => $e->getDataAsString()
            ];

            $this->app['segment']->trackPayment($payment,
                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                $customProperties);

            // We are not re-throwing $e because we don't want the
            // customer to know that it was a capture error.
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
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

        $gatewayCaptured = $payment->isGatewayCaptured();

        //
        // This is not really required and is here for a more robust check. Can be removed anytime.
        //
        if (($payment->getGateway() === Payment\Gateway::HDFC) and
            ($gatewayCaptured === true))
        {
            $gatewayCaptured = $this->callGatewayForVerifyCapture(['payment' => $payment->toArray()]);
        }

        if ($gatewayCaptured)
        {
            $this->recordTransactionForFailedApiCapture();

            $msg = 'Has been captured on gateway and hence creating a transaction in api.';
        }
        else
        {
            $msg = 'Has not been captured on gateway. Not doing anything on the api side.';
        }

        return ['verify_capture' => $msg];
    }

    public function manualGatewayCapture(Payment\Entity $payment)
    {
        $this->setPayment($payment);

        // Currently doing it for only Cybersource. In case when other gateways start
        // getting similar issues, we will start supporting for them too.
        assert ($payment->getGateway() === Payment\Gateway::CYBERSOURCE);

        assert ($payment->getStatus() === Payment\Status::CAPTURED);

        // Just making sure that the payment has the transaction id.
        assert ($payment->getTransactionId() !== null);

        assert ($payment->hasBeenCaptured());

        $data = $this->getGatewayDataForCapture($payment);

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        // The reason for NOT using verifyCapture Gateway function is because in ManualCapture, we want to add
        // more checks and validations in the gateway function. VerifyCapture takes care of the checks specific
        // to verifyCapture only. Since manualCapture is a very exceptional case and hopefully a one-time execution,
        // we want to add more asserts around it.
        $manualGatewayCaptureResult = $this->callGatewayForManualCapture($data);

        // Here, $manualGatewayCaptureResult=true means that the payment is captured on the gateway side.
        if ($manualGatewayCaptureResult === true)
        {
            $msg = 'Successfully created a capture on gateway';

            $payment->setGatewayCaptured(true);

            $this->repo->saveOrFail($payment);
        }
        else if ($manualGatewayCaptureResult === false)
        {
            $msg = 'DID NOT CREATE A CAPTURE ON GATEWAY. ISSUE!';
        }
        else
        {
            $msg = 'THIS IS UNEXPECTED!';
        }

        return [
            'manual_gateway_capture' => $msg,
            'payment_id'             => $payment->getId(),
        ];
    }

    protected function callGatewayForManualCapture($data)
    {
        $manualGatewayCaptureResult = null;

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_CAPTURE_INITIATED,
            [
                'payment_id'    => $data['payment']['id'],
            ]);

        try
        {
            $manualGatewayCaptureResult = $this->callGatewayFunction(Payment\Action::MANUAL_GATEWAY_CAPTURE, $data);
        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::MANUAL_GATEWAY_CAPTURE_FAILURE
            );

            throw $ex;
        }

        return $manualGatewayCaptureResult;
    }

    protected function getGatewayDataForCapture(Payment\Entity $payment)
    {
        $data = [
            'payment'   => $payment->toArrayGateway(),
            'amount'    => $payment->getBaseAmount(),
        ];

        return $data;
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
     * @param  Payment\Entity $payment
     * @param  integer        $captureAmount
     * @param  string         $currency
     *
     * @return Payment\Entity
     * @throws Exception\BadRequestException
     * @internal param int $amount
     */
    protected function capturePayment(Payment\Entity $payment, int $captureAmount, string $currency)
    {
        //
        // If the fee bearer is customer then please to adjust input amount
        // with the available fee for the payment.
        //
        if ($this->merchant->isFeeBearerCustomer())
        {
            $captureAmount = $captureAmount + $payment->getFee();

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_REQUEST,
                [
                    'payment_id'        => $payment->getId(),
                    'capture_amount'    => $captureAmount,
                    'message'           => 'Adds fee to the amount because fee bearer is customer',
                ]);
        }

        if ($captureAmount !== $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
                Payment\Entity::AMOUNT,
                [
                    'capture_amount' => $captureAmount,
                    'payment_amount' => $payment->getAmount(),
                    'payment_id'     => $payment->getId(),
                ]);
        }

        //$payment->getValidator()->captureAmountValidate($payment, $amount);

        $payment->getValidator()->captureValidate($payment, $captureAmount, $currency);

        $data = array(
            'payment'   => $payment->toArrayGateway(),
            'amount'    => $captureAmount,
            'currency'  => $payment->getCurrency()
        );

        if ($payment->isMethodCardOrEmi())
        {
            $card = $this->repo->card->fetchForPayment($payment);
            $data['card'] = $card->toArray();
        }

        if ($payment->getConvertCurrency() === true)
        {
            $data['amount'] = $payment->getBaseAmount();
            $data['currency'] = Currency\Currency::INR;
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

        $this->mutex->acquireAndRelease(
            $this->payment->getId(),
            function() use ($data)
            {
                $this->callAndHandleCaptureOnGateway($data);

                // In case of a failure (marking the payment as failed),
                // we won't record this capture since we throw the exception
                // after marking the payment as failed.
                $this->recordCapture();
            });
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
        if ($paymentGateway !== Payment\Gateway::HDFC)
        {
            throw $ex;
        }

        $this->trace->traceException($ex);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_ADD_TO_QUEUE,
            ['payment_id' => $this->payment->getId()]);

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
            list($txn, $feesSplit) = $txnCore->createOrUpdateFromPaymentCaptured($payment);

            $this->repo->saveOrFail($txn);

            $this->repo->saveOrFail($payment);

            //TODO: Saving feesplit for backward compatibility
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

            $this->updateSubscriptionStatus($payment);

            $this->tracePaymentInfo(TraceCode::PAYMENT_CAPTURE_SUCCESS);
        });

        $this->eventPaymentCaptured();

        $this->notifyPaymentCaptured();

        //
        // Analytics
        //
        $this->notifyDashboard('payment', $this->payment);
    }

    /**
     * Fires multiple events after payment is captured:
     * - api.order.paid
     * - api.invoice.paid
     *
     * @return null
     */
    protected function eventPaymentCaptured()
    {
        $this->eventOrderPaid();

        $this->eventInvoicePaid();
    }

    /**
     * Triggers notifications after payment is captured.
     *
     * @return null
     */
    protected function notifyPaymentCaptured()
    {
        $hasInvoice = $this->payment->hasInvoice();

        $event = $hasInvoice ? Notify::INVOICE_PAYMENT_CAPTURED : Notify::CAPTURED;

        (new Notify($this->payment))->trigger($event);
    }

    protected function eventOrderPaid()
    {
        $payment = $this->payment;

        if ($payment->getApiOrderId() !== null)
        {
            $this->app['events']->fire('api.order.paid', array($payment));
        }
    }

    protected function eventInvoicePaid()
    {
        $payment = $this->payment;

        if ($payment->hasInvoice() === false)
        {
            return;
        }

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

        $feesSplit = new PublicCollection;

        list($txn, $feesSplit) = $txnCore->createOrUpdateFromPaymentCaptured($payment);

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

    protected function verifyOrderUnpaid(Payment\Entity $payment)
    {
        if ($payment->hasOrder())
        {
            $order = $this->repo->order->fetchForPayment($payment);

            if ($order->getStatus() === Order\Status::PAID)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Corresponding order already has a captured payment.');
            }
        }
    }

    protected function updatePaidOrderStatus(Payment\Entity $payment)
    {
        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->order;

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

        if ($invoice->hasBeenPaid() === true)
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

    /**
     * THIS IS RELEVANT ONLY FOR THE RETRY FLOW. NOT IN THE NORMAL FLOW.
     *
     * @param Payment\Entity $payment
     */
    protected function updateSubscriptionStatus(Payment\Entity $payment)
    {
        $subscription = $payment->subscription;

        if (empty($subscription) === true)
        {
            return;
        }

        $updateSubscription = (new Subscription\Core)->shouldUpdateSubscriptionOnCapture($subscription, $payment);

        if ($updateSubscription === true)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_SUBSCRIPTION_UPDATE,
                [
                    'payment_id'        => $payment->getId(),
                    'subscription_id'   => $subscription->getId(),
                ]);

            // TODO: Fix this flow. Invoice is required here.
            (new Subscription\Charge)->handleCaptureSuccess($subscription, $payment);
        }
    }
}
