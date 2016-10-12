<?php

namespace RZP\Models\Payment\Processor;

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

        $payment->getValidator()->validateInput('capture', $input);

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
        $payment->setAutoCapturedTrue();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_CAPTURE, ['payment_id' => $payment->getId()]);

        try
        {
            $payment = $this->capturePayment($payment, $amount);
        }
        catch (Exception\RecoverableException $e)
        {
            $this->trace->error(
                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                ['auto_capture' => 1,
                'payment_id' => $payment->getPublicId()]);

            return false;
        }

        return true;
    }

    /**
     * We check if the capture status on the gateway is successful and on the api, it's not captured.
     * If it's successful on the gateway side, we create a transaction on the api side.
     * This does not follow the convention where all authAndCapture supported gateways should have
     * the payment in captured state for a transaction to be created. But, these are edge cases where capture
     * succeeded on gateway and failed on api side due to some reason. This should ideally never happen.
     * We cannot create a transaction by capturing it because, the merchant may not actually want to capture
     * this payment anymore. Hence, we just create a transaction and leave it at that.
     *
     * If the merchant wants to capture the payment later, he can capture it and the process would
     * be like how it is for not AuthAndCapture supported gateways. [THIS NEEDS TO BE CHECKED].
     *
     * @param $payment
     * @return array
     */
    public function verifyCapture($payment)
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
            $this->recordTransactionForFailedApiCapture();

            $msg = 'Has been captured on gateway and hence creating a transaction in api.';
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
    protected function capturePayment($payment, $amount)
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
                    'amount' => $amount,
                    'message' => 'Adds fee to the amount because fee bearer is customer',
                ]
            );
        }

        $payment->getValidator()->captureValidate($payment, $amount);

        $data = array(
            'payment' => $payment->toArray(),
            'amount' => $amount);

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
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

            try
            {
                $this->callGatewayFunction(Payment\Action::CAPTURE, $data);
            }
            catch (Exception\GatewayTimeoutException $ex)
            {
                //
                // We are currently doing capture queue for HDFC, as we don't want to mark
                // the captured payment on gateway as failed on API
                // Note: Capture shouldn't be done again for Cybersource
                // as cybersource settles the amount from CH account again
                //
                if ($this->payment->getGateway() !== Payment\Gateway::HDFC)
                {
                    throw $ex;
                }

                $this->trace->traceException($ex);

                $data['mode'] = $this->mode;

                $this->trace->info(
                    TraceCode::PAYMENT_CAPTURE_ADD_TO_QUEUE, ['payment_id' => $this->payment->getId()]
                );

                $this->app['queue']->push('RZP\Jobs\Capture', ['data' => $data]);
            }

            $this->recordCapture();
        }
        catch (Exception\BaseException $ex)
        {
            // For validation failures, we shouldn't mark capture as failed ever.
            if (($ex instanceof Exception\BadRequestValidationFailureException) or
                ($ex instanceof Exception\BadRequestException))
            {
                throw $ex;
            }

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

            $this->updatePaymentFailed($ex, TraceCode::PAYMENT_CAPTURE_FAILURE);

            throw $ex;
        }
        finally
        {
            $this->releaseMutexOnPayment($this->payment);
        }
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
            $txn = $txnCore->createFromPaymentAuthorized($payment);

            $this->repo->saveOrFail($txn);
            $this->repo->saveOrFail($payment);

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

    protected function updatePaymentCaptured($payment, $autoCaptured = false)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setCaptureTimestamp();

        $payment->setAutoCaptured($autoCaptured);
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

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($payment);
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

            $this->repo->saveOrFail($order);
        }
    }
}
