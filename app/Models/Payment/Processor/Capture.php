<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Gateway\Base\Metric;
use RZP\Jobs;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Diag\EventCode;
use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Models\Order;
use RZP\Models\Invoice;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Currency;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Models\Transaction;
use Illuminate\Support\Str;
use RZP\Models\VirtualAccount;
use RZP\Jobs\Order\OrderUpdate;
use RZP\Models\Partner\Commission;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\Capture as CaptureJob;
use RZP\Models\Merchant\Preferences;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Jobs\Order\OrderUpdateByOutbox;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Offer;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Ledger\CaptureJournalEvents;
use RZP\Base\Database\DetectsLostConnections;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\OrderOutbox\Entity as OrderOutboxEntity;
use RZP\Models\OrderOutbox\Constants as OrderOutboxConstants;
use RZP\Models\QrCode\NonVirtualAccountQrCode as NonVAQr;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Jobs\MerchantBasedBalanceUpdateV1;
use RZP\Jobs\MerchantBasedBalanceUpdateV2;
use RZP\Jobs\MerchantBasedBalanceUpdateV3;

trait Capture
{
    /**
     * Captures a previous auth payment
     *
     * @param Payment\Entity $payment to be captured
     * @param  array         $input
     *
     * @return Payment\Entity Payment\Entity object
     */
    private function coreCapture(Payment\Entity $payment, array $input = [])
    {
        $inputTrace = $input;

        unset($inputTrace['email'], $inputTrace['password']);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_REQUEST,
            [
                'payment_id' => $payment->getPublicId(),
                'input'      => $inputTrace,
            ]
        );

        $ctx = app('request.ctx');

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CAPTURE_INITIATED,
            $payment,
            null,
            [],
            [
                'input'  => $input,
                'source_route' => $ctx->getRoute(),
                'source_auth_type' =>  $ctx->getAuth(),
            ]);

        $this->setPayment($payment);

        $input['currency'] = $payment->getCurrency();

        $payment->getValidator()->validateInput('capture', $input);

        return $this->capturePayment($payment, $input['amount'], $input['currency']);
    }

    public function gatewayCapturePaymentViaQueue($payment)
    {
        $this->setPayment($payment);

        $amount = $payment->getAmount();

        if ($payment->isFeeBearerCustomer() === true)
        {
            $amount -= $payment->getFee();
        }

        $currency = $payment->getCurrency();

        $data = $this->getCaptureData($payment, $amount, $currency);

        $this->dispatchAsyncCapture($data);
    }

    /**
     * Wrap logic of `coreCapture` with tracing instrumentation
     *
     * @param Payment\Entity $payment to be captured
     * @param  array         $input
     *
     * @return Payment\Entity Payment\Entity object
     */
    public function capture(Payment\Entity $payment, array $input = [])
    {
        return Tracer::inSpan(['name' => 'payment.capture'],
            function() use ($payment, $input){
                return $this->coreCapture($payment, $input);
            }
        );
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
        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CAPTURE_INITIATED,
            $payment,
            null,
            [
                'metadata' => [
                    'payment' => [
                        'id' => $payment->getPublicId(),
                        'auto_capture' => 1
                    ]
                ],
                'read_key' => array('payment.id'),
                'write_key' => 'payment.id'
            ],
            [
                'auto_capture' => 1
            ]);

        $this->setPayment($payment);

        $amount = $payment->getAmount();

        if ($payment->isFeeBearerCustomer() === true)
        {
            $amount -= $payment->getFee();
        }

        // set auto-capture 1
        $payment->setAutoCapturedTrue();

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_CAPTURE, ['payment_id' => $payment->getId(),
            'order_id'                => $payment->getApiOrderId(),
            'merchant_id'             => $payment->getMerchantId(),]);

        $this->app['segment']->trackPayment($payment, TraceCode::PAYMENT_AUTO_CAPTURE);

        $currency = $payment->getCurrency();

        try
        {
            $this->capturePayment($payment, $amount, $currency);
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
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                null,
                [
                    'payment_id'     => $payment->getId(),
                    'payment_status' => $payment->getStatus(),
                    'method'         => $payment->getMethod()
                ]);
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
        assertTrue ($payment->hasBeenCaptured() === false);

        // If the transaction is already present for this, we should not be running verifyCapture at all.
        assertTrue ($payment->getTransactionId() === null);

        // The payment should be in authorized or refunded state only.
        assertTrue ($payment->isStatusCreatedOrFailed() === false);

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

        // skip if payment is not via card
        if ($payment->isMethodCardOrEmi() === false)
        {
            return false;
        }

        if ($payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE)
         {
            $payment->enableCardPaymentService();
         }


        $data = $this->getGatewayDataForCapture($payment);

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        $result = $this->callGatewayForManualCapture($data);

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_CAPTURE_RESPONSE,
            [
                'payment_id'    => $payment->getId(),
                'result'        => $result,
            ]);

        return $result;
    }

    protected function callGatewayForManualCapture($data)
    {
        try
        {
            $this->trace->info(
                TraceCode::MANUAL_GATEWAY_CAPTURE_INITIATED,
                [
                    'payment_id' => $this->payment->getId(),
                ]);

            return $this->mutex->acquireAndRelease(
                $this->payment->getId(),
                function() use ($data)
                {
                    $this->repo->reload($this->payment);

                    // Just making sure that the payment has the transaction id.
                    if (($this->payment->getTransactionId() === null) or
                        ($this->payment->hasBeenCaptured() === false))
                    {
                        return false;
                    }

                    if ($this->payment->isGatewayCaptured() === false)
                    {
                        $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

                        $this->payment->setGatewayCaptured(true);

                        $this->repo->saveOrFail($this->payment);

                        $this->trace->info(
                            TraceCode::PAYMENT_GATEWAY_CAPTURED,
                            [
                                'payment_id'        => $this->payment->getId(),
                            ]);


                        if ($this->payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                        {
                            (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($this->payment);
                        }

                        $this->createLedgerEntriesForGatewayCapture($this->payment);
                    }

                    return true;
                });

        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::MANUAL_GATEWAY_CAPTURE_FAILURE
            );

            return false;
        }
    }

    public function createLedgerEntriesForGatewayCapture(Payment\Entity $payment)
    {
        if(($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false)
        or ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true))
        {
            return;
        }

        try
        {
            $transactionMessage = CaptureJournalEvents::createTransactionMessageForGatewayCapture($payment);

            if (empty($transactionMessage) === false)
            {
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);

                $this->trace->info(
                    TraceCode::GATEWAY_CAPTURED_EVENT_TRIGGERED,
                    [
                        'payment_id'        => $payment->getId(),
                        'message'           => $transactionMessage,
                    ]);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                []);
        }
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
        try
        {
            if($this->repo->isTransactionActive())
            {
                $ex = new \Exception();

                $this->trace->info(TraceCode::CAPTURE_PAYMENT_TRACE,
                    [
                        'payment_id'          => $payment->getPublicId(),
                        'transaction_level'   => $this->repo->getTransactionLevel(),
                        'stack_trace'         => $ex->getTraceAsString(),
                    ]
                );
            }

            $autoCaptureStartTime = microtime(true);

            $autoCaptured = $payment->getAutoCaptured();

            $data = $this->getCaptureData($payment, $captureAmount, $currency);

            $this->mutex->acquireAndRelease(
                $this->payment->getId(),
                function() use ($data, $autoCaptured, $autoCaptureStartTime)
                {
                    $this->trace->info(TraceCode::AUTO_CAPTURE_PAYMENT_ID_MUTEX_TIME_TAKEN,
                        [
                            'mutex_time_taken'   => (microtime(true) - $autoCaptureStartTime) * 1000
                        ]
                    );

                    $this->captureOnGateway($data, $autoCaptured);
                });

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_PROCESSED, $payment);



            return $payment;
        }
        catch (\Throwable $e)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_PROCESSED, $payment, $e);

            (new Payment\Metric)->pushExceptionMetrics($e, Payment\Metric::PAYMENT_CAPTURE_FAILED);

            if (($e instanceof \Exception) and
                ($this->causedByLostConnection($e)))
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_CAPTURE_FAILED_MYSQL_HAS_GONE_AWAY
                );

                $this->trace->info(TraceCode::RETRY_CAPTURE,
                    [
                        'payment_id'          => $payment->getPublicId(),
                        'transaction_level'   =>  $this->repo->getTransactionLevel()
                    ]
                );

                return $this->retryCapture($payment);
            }
            else
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_CAPTURE_FAILURE
                );

                throw $e;
            }
        }
    }

    protected function getCaptureData(Payment\Entity $payment, int $captureAmount, string $currency)
    {
        $this->modifyCaptureAmountForDiscountedOrder($payment, $captureAmount);

        $this->modifyCaptureAmountForPaymentFee($payment, $captureAmount);

        $payment->getValidator()->captureValidate($payment, $captureAmount, $currency);

        $data = [
            'payment' => $payment->toArrayGateway(),
            'amount' => $payment->getGatewayAmount(),
            'currency' => $payment->getGatewayCurrency()
        ];

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

        if ($payment->isUpiOtm() === true)
        {
            $data['upi'] = $payment->getUpiMetadata()->toArray();
        }

        return $data;
    }

    /**
     * If the fee bearer is customer then adjust input amount
     * with the available fee for the payment.
     * @todo: fee bearer cannot work with mandate registration
     *
     * @param  Payment\Entity $payment
     * @param  integer        $captureAmount
     */
    protected function modifyCaptureAmountForPaymentFee(Payment\Entity $payment, int & $captureAmount)
    {
        if ($payment->isFeeBearerCustomer() === true)
        {
            $captureAmount = $captureAmount + $payment->getFee();

            $this->trace->info(
                TraceCode::PAYMENT_CAPTURE_REQUEST,
                [
                    'payment_id' => $payment->getId(),
                    'capture_amount' => $captureAmount,
                    'message' => 'Adds fee to the amount because fee bearer is customer',
                ]);
        }
    }

    protected function modifyCaptureAmountForDiscountedOrder(Payment\Entity $payment, int & $captureAmount)
    {
        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->order;

        if($payment->getOffer() === null)
        {
            return;
        }

        if($payment->getOffer()->getOfferType() !== Offer\Constants::INSTANT_OFFER)
        {
            return;
        }

        $discount = $payment->discount;

        if ($payment->discount === null)
        {
            return;
        }

        $captureAmount = $discount->offer->getDiscountedAmountForPayment($order->getAmount(), $payment);
    }

    /**
     * If gateway call for capture times out, we catch the exception thrown
     * and push it into a queue. We continue with the normal flow afterwards.
     *
     * @param      $data
     * @param bool $autoCaptured
     */
    protected function captureOnGateway($data, $autoCaptured = false)
    {
        //mutex for order id to avoid multiple capture for same order at the same time.
        if ($this->payment->hasOrder())
        {
            $captureGatewayStartTime = microtime(true);

            $this->mutex->acquireAndRelease(
                $this->payment->getApiOrderId(),
                function() use ($data, $autoCaptured, $captureGatewayStartTime)
                {
                    $this->trace->info(TraceCode::AUTO_CAPTURE_ORDER_ID_MUTEX_TIME_TAKEN,
                        [
                            'mutex_time_taken'   => (microtime(true) - $captureGatewayStartTime) * 1000
                        ]
                    );
                    $this->verifyOrderUnpaid($this->payment);

                    $this->repo->reload($this->payment);

                    $this->callAndHandleCaptureOnGateway($data);

                    // In case of a failure (marking the payment as failed),
                    // we won't record this capture since we throw the exception
                    // after marking the payment as failed.
                    $this->recordCapture($autoCaptured);
                }
            );
        }
        else
        {
            $this->verifyOrderUnpaid($this->payment);

            $this->repo->reload($this->payment);

            $this->callAndHandleCaptureOnGateway($data);

            // In case of a failure (marking the payment as failed),
            // we won't record this capture since we throw the exception
            // after marking the payment as failed.
            $this->recordCapture($autoCaptured);
        }

        $this->triggerPaymentCapturedEvents();

        $this->publishMessageToSqsBarricade($this->payment);

        $this->notifyPaymentCaptured();

        // temporarily disabling metric push for "api_payment_captured_v1_bucket"
        //
        //(new Payment\Metric)->pushCapturedMetrics($this->payment);
    }

    protected function callAndHandleCaptureOnGateway(array $data)
    {
        try
        {
            $callToGatewayStartTime = microtime(true);

            if ($this->payment->isGatewayCaptured() === false)
            {
                if (($this->merchant->isFeatureEnabled(Feature\Constants::ASYNC_CAPTURE) === true) or
                    ($this->shouldDelayCapture() === true))
                {
                    $this->dispatchAsyncCapture($data);
                }
                else
                {
                    $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_GATEWAY_INITIATED, $this->payment);

                    $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

                    $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_GATEWAY_SUCCESS, $this->payment);

                    $this->payment->setGatewayCaptured(true);

                    // Saving this here itself because recordCapture will perform other actions too,
                    // in a transaction, which could fail and end up rolling back.
                    $this->repo->saveOrFail($this->payment);

                    $this->trace->info(
                        TraceCode::PAYMENT_GATEWAY_CAPTURED,
                        [
                            'payment_id'        => $this->payment->getId(),
                        ]);


                    if ($this->payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                    {
                        (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($this->payment);
                    }

                    $this->createLedgerEntriesForGatewayCapture($this->payment);
                }
            }

            $this->trace->info(TraceCode::AUTO_CAPTURE_CALL_TO_GATEWAY_TIME_TAKEN,
                [
                    'gateway_time_taken'   => (microtime(true) - $callToGatewayStartTime) * 1000
                ]
            );
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_GATEWAY_FAILED, $this->payment, $ex);

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYMENT_CAPTURE_FAILURE_EXCEPTION,
                [
                    'gateway'       => $data['payment']['gateway'],
                ]);

            $this->handleExceptionOnCapture($data, $ex);
        }
    }

    protected function shouldDelayCapture()
    {
        if (($this->payment->isMethodCardOrEmi() === false) or
            ($this->payment->hasCard() === false) or
            ($this->payment->card->isRuPay() === false) or
            ($this->payment->getGateway() !== Payment\Gateway::PAYSECURE))
        {
            return false;
        }

        return (bool) Admin\ConfigKey::get(Admin\ConfigKey::DELAY_RUPAY_CAPTURE, false);
    }

    /**
     * If the feature is enabled, we will always mark it as captured on our end.
     * If the feature is not enabled, we will mark it as captured on our based on some conditions.
     *
     * @param           $data
     * @param Throwable $ex
     *
     * @throws Throwable
     */
    protected function handleExceptionOnCapture(array $data, \Throwable $ex)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::CAPTURE_QUEUE) === true)
        {
            $this->dispatchAsyncCapture($data);
        }
        else
        {
            if ($this->shouldDispatchCaptureOnFailure($ex) === true)
            {
                $this->dispatchAsyncCapture($data);
            }
            else
            {
                throw $ex;
            }
        }
    }

    protected function shouldDispatchCaptureOnFailure(\Throwable $ex)
    {
        $payment = $this->payment;
        //
        // If the capture times out for HDFC, we mark it as captured on API and add the captureOnGateway
        // to a queue. We then try to capture on HDFC.
        // We do a similar thing for Cybersource. But, right now, we are not adding to the queue. We will
        // fix these later (by around 19th-20th Dec). We need to first check whether capture succeeded or not
        // and only then capture on Cybersource gateway if required. Otherwise, it'll capture multiple times.
        //
        switch ($payment->getGateway())
        {
            case Payment\Gateway::HDFC:
                return (($ex instanceof Exception\GatewayTimeoutException) === true);
            case Payment\Gateway::FULCRUM:
            case Payment\Gateway::PAYSECURE:
                return true;
        }

        return false;
    }

    // Initiate Capture Asynchronously
    protected function dispatchAsyncCapture(array $data)
    {
        $data['mode'] = $this->mode;

        $customProperties = [
            'payment'  => [
                'status'            => $this->payment->getStatus(),
                'gateway_captured'  => $this->payment->getGatewayCaptured(),
            ],
        ];

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_QUEUE, $this->payment, null, [], $customProperties);

        (new Payment\Metric)->pushCaptureQueueMetrics($this->payment,Metric::INITIATED);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_ADD_TO_QUEUE,
            ['payment_id' => $this->payment->getId()]);

        //
        // Adding a delay here because some gateways return back an error if a capture request
        // is sent within a few seconds of the first capture request.
        // Example : HDFC sends FS00002 error if capture request is sent within 20 seconds of the
        // previous capture request.
        //
        if ($this->payment->getGateway() !== Payment\Gateway::PAYSECURE)
        {
            CaptureJob::dispatch($data)->delay(self::CAPTURE_QUEUE_DELAY);
        }
        else
        {
            CaptureJob::dispatch($data)->delay(self::PAYSECURE_CAPTURE_QUEUE_DELAY);
            //CaptureJob::dispatch($data);
        }
    }

    /**
     * This function is called when we captured successfully on the gateway side but threw an error on the API side.
     * So, as far as the merchant is concerned, the capture did not happen and
     * we are not going to settle any money to him.
     * Hence, we should not save any fees details in this case.
     */
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

            $this->tracePaymentInfo(TraceCode::TRANSACTION_CREATED_IN_VERIFY_CAPTURE);
        });
    }

    protected function recordCapture($autoCaptured = false)
    {
        /** @var Payment\Entity $payment */
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment, $autoCaptured)
        {
            $this->lockForUpdateAndReload($payment);

            if ($payment->hasBeenCaptured() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED);
            }

            $this->updatePaymentCaptured($payment, $autoCaptured);

            //
            // We want to take balance lock towards the end of the transaction.
            //
            // If you are adding more merchant IDs here, ensure credits stuff is handled in `handleLateBalanceUpdate`.
            // Currently, since we are doing this only for Dream11, we are not handling credits.
            // Also, need to handle credits in `setFeeDefaults` in Transaction\Processor\Base
            //

            if (($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_BALANCE_UPDATE) === false) and
                (($payment->getMerchantId() === 'CCIJ8fB9RncDsV') or
                ($payment->getMerchantId() === Preferences::MID_DREAM11)))
            {
                $payment->setLateBalanceUpdate();
            }

            $txn = null;

            /* Get Original Payment Fee (i.e Fee In Same as Merchant Initiated Currency)
             * from Payment Entity Before Transaction Creation Since it will update Payment Fee to INR
            */
            $originalPaymentFee = $payment->getFee();

            if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
            {
                list($txn, $merchantBalance) = $this->createTransactionFromCapturedPayment($payment);
            }
            else
            {
                $discount = $this->getDiscountIfApplicableForLedger($payment);

                [$fee, $tax] = (new ReverseShadowPaymentsCore())->createLedgerEntryForMerchantCaptureReverseShadow($payment, $discount);

                $this->trace->info(TraceCode::PAYMENT_MERCHANT_CAPTURED_REVERSE_SHADOW, [
                    LedgerConstants::PAYMENT_ID => $payment->getId(),
                    LedgerConstants::FEES       => $fee,
                    LedgerConstants::TAX        =>$tax
                ]);

                $this->repo->payment->saveOrFail($payment);
            }

            $this->updateVirtualAccountStatusIfApplicable($payment);

            $this->updateAnalyticsIfApplicable($payment);

            $this->createPartnerCommission($payment);

            $this->postPaymentCaptureSubscriptionRegistrationProcessing($payment);

            if ($payment->isLateBalanceUpdate() === true)
            {
                $this->handleLateBalanceUpdate($txn, $merchantBalance);
            }

            // We will create ledger entries in central ledger for shadow only if async_txn_fill_details feature is not set.
            // If it is set then fee and tax get updated later and we will create ledger entries at that point.
            if ($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === false)
            {
                $this->trace->info(
                    TraceCode::PAYMENT_MERCHANT_CAPTURED,
                    [
                        'payment_id' => $payment->getId(),
                    ]);

                $this->createLedgerEntriesForMerchantCapture($payment, $txn);
            }

            // Please keep this function at the end of transaction block, as
            // we are updating orders which lies in PG Router service now.
            // This has been done to temporarily handle the distributed transaction failures.
            $this->updateOrderAfterCapture($payment,$originalPaymentFee);
        });

        if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            $this->handleAsyncUpdateBalanceIfApplicable($payment, $payment->transaction);
        }

        if (($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === false) and
            ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false))
        {
            $this->processTransferIfApplicable($payment);
        }

        if($payment->isDCC()
            and ($payment->isMethodInternationalApp() or $payment->isCard()))
        {
            try
            {
                (new Invoice\DccEInvoiceCore())->dispatchForInvoice($payment->getId(),Invoice\Constants::PAYMENT_FLOW);
            }
            catch (\Exception $e){

                $this->trace->info(
                    TraceCode::DCC_PAYMENT_E_INVOICE_MESSAGE_DISPATCH_FAILED,[
                        'reference_id'       => $payment->getId(),
                        'reference_type'     => Invoice\Constants::PAYMENT_FLOW,
                    ]
                );
            }
        }

        $this->tracePaymentInfo(TraceCode::PAYMENT_CAPTURE_SUCCESS);
    }

    public function createLedgerEntriesForMerchantCapture(Payment\Entity $payment, Transaction\Entity $txn = null)
    {

        if (($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === false) or
            ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true))
        {
            return;
        }

        try
        {
            $discount = $this->getDiscountIfApplicableForLedger($payment);

            $transactionMessage = CaptureJournalEvents::createTransactionMessageForMerchantCapture($payment, $txn, $discount);

            \Event::dispatch(new TransactionalClosureEvent(function () use ($txn, $transactionMessage) {
                // Job will be dispatched only if the transaction commits.
                LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
            }));

            $this->trace->info(
                TraceCode::PAYMENT_MERCHANT_CAPTURED_EVENT_TRIGGERED,
                [
                    'payment_id'            => $payment->getId(),
                    'message'               => $transactionMessage
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                []);
        }
    }


    protected function handleAsyncUpdateBalanceIfApplicable(Payment\Entity $payment, Transaction\Entity $txn)
    {
        try
        {
            if ((($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_BALANCE_UPDATE) === false) and
                ($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === false)) or
                ($txn->isBalanceUpdated() === true))
            {
                return;
            }

            $input = [
                'payment_id'  => $payment->getId(),
                'mode'        => $this->mode,
            ];

            $this->trace->info(
                TraceCode::MERCHANT_BALANCE_UPDATE_INIT,
                [
                    'input' => $input,
                    'merchant_id' => $payment->getMerchantId(),
                ]);

            $asyncBalancePushedAt = time();

            if($this->pushedToMerchantsBasedBalanceUpdateQueue($input, $payment->getMerchantId(), $asyncBalancePushedAt) === true)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_BASED_BALANCE_UPDATE_QUEUE,
                    [
                        'input' => $input,
                        'merchant_id' => $payment->getMerchantId(),
                    ]);
                return;
            }

            Jobs\MerchantBalanceUpdate::dispatch($input, $this->mode, $asyncBalancePushedAt);
        }
         catch (\Throwable $e)
        {
            $this->trace->critical(
                TraceCode::MERCHANT_BALANCE_UPDATE_SQS_PUSH_FAILED,
                [
                    'payment_id' => $payment->getId(),
                    'message'    => $e->getMessage(),
                ]);

            $this->updateMerchantBalance($payment, $txn);
        }
    }

    public function updateMerchantBalance(Payment\Entity $payment, Transaction\Entity $txn)
    {
        $this->payment = $payment;

        $this->repo->transaction(function() use ($payment, $txn)
        {
            (new Transaction\Core)->asyncUpdateMerchantBalance($payment, $txn);

            if ($payment->merchant->isFeatureEnabled(Feature\Constants::ASYNC_TXN_FILL_DETAILS) === true)
            {
                $payment->setTax($txn->getTax());

                if ($payment->isFeeBearerCustomer() === false)
                {
                    //set and fee values from txn
                    $payment->setFee($txn->getFee());
                }

                $this->calculateAndSetMdrFeeIfApplicable($payment, $txn);

                $this->repo->saveOrFail($payment);

                $this->repo->saveOrFail($txn);

                $this->trace->info(
                    TraceCode::PAYMENT_MERCHANT_CAPTURED_ASYNC_TXN_FILL_DETAILS,
                    [
                        'payment_id' => $payment->getId(),
                    ]);

                $this->createLedgerEntriesForMerchantCapture($payment, $txn);

                $this->processTransferIfApplicable($payment);

                // dispatching txn data to new settlement service after updating credit and debit value
                (new Transaction\Core)->dispatchForSettlementBucketing($txn);
            }
        });
    }

    protected function handleLateBalanceUpdate(Transaction\Entity $txn, $merchantBalance)
    {
        //
        // The inspiration for this block of code is from Transaction\Processor\Base
        // block where `isLateBalanceUpdate` is being used.
        //

        // NOTE: THIS MUST BE USED ONLY IN PAYMENT CAPTURE FLOW
        // SINCE THIS DOES NOT HAVE BALANCE GOING NEGATIVE CHECK!
        $amountToUpdate = $txn->getNetAmount();

        $this->trace->info(
            TraceCode::MERCHANT_BALANCE_DATA,
            [
                'old_balance' => $merchantBalance->getBalance(),
                'amount_to_update' => $amountToUpdate,
                'new_balance' => $merchantBalance->getBalance() + $amountToUpdate,
                'method_name' => 'handleLateBalanceUpdate',
            ]);

        $this->repo->balance->updateBalanceDirectly($merchantBalance, $amountToUpdate);

        // Not updating transaction balance for now and will do it later via offline cron.
        // $txn->setBalance($merchantBalance->getBalance());
        // $this->repo->saveOrFail($txn);

        // NOTE: Since we are doing this only for Dream11, we are not handling credits as of now.
    }

    /**
     * Creates partner commission entities from a captured payment
     *
     * @param Payment\Entity $payment
     */
    public function createPartnerCommission(Payment\Entity $payment)
    {
        try
        {
            (new Commission\Core)->createFromCapturedPayment($payment);
        }
        catch (\Throwable $e)
        {
            $dimensions = [
                'message'       => $e->getMessage(),
                'code'          => $e->getCode(),
            ];

            $this->trace->count(PartnerMetric::PAYMENT_COMMISSION_CREATE_FAILED, $dimensions);
            $this->trace->critical(
                TraceCode::COMMISSION_CREATE_FAILED,
                [
                    'payment_id' => $payment->getId(),
                    'message'    => $e->getMessage(),
                ]);
        }
    }

    /**
     * Fires multiple events after payment is captured:
     * - api.order.paid
     * - api.invoice.paid
     */
    protected function triggerPaymentCapturedEvents()
    {
        $this->eventPaymentCaptured();

        $this->eventOrderPaid();

        $this->eventInvoicePaid();

        $this->eventVirtualAccountCredited();

        $this->eventQrCodeCredited();
    }

    /**
     * Triggers notifications after payment is captured.
     */
    protected function notifyPaymentCaptured()
    {
        if ($this->payment->hasSubscription() === true)
        {
            return;
        }

        $event = Payment\Event::CAPTURED;

        if ($this->payment->hasInvoice() === true)
        {
            $event = Payment\Event::INVOICE_PAYMENT_CAPTURED;
        }

        (new Notify($this->payment))->trigger($event);
    }

    public function eventOrderPaid()
    {
        $payment = $this->payment;

        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->order;

        //
        // Order's status when is partially_paid continues to stay in attempted state.
        // Once fully paid it's amount_paid=amount and status=paid. Also partial payment
        // feature is not directly exposed on order for now(until we decide on the inter-
        // -mediate status).
        //
        // Finally, we don't want to fire order.paid if an order is not yet fully paid.
        //
        if ($order->isPaid() === false)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->dispatch('api.order.paid', $eventPayload);
    }

    protected function eventInvoicePaid()
    {
        $payment = $this->payment;

        if ($payment->hasInvoice() === false)
        {
            return;
        }

        //
        // Using order's invoice as that gets updated in the capture flow
        // else if to use payment's invoice relation, will need to reload that.
        //
        $invoice = $payment->order->invoice;

        $event = ($invoice->isPaid() === true) ? 'api.invoice.paid' : 'api.invoice.partially_paid';

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->dispatch($event, $eventPayload);
    }

    protected function eventVirtualAccountCredited()
    {
        $payment = $this->payment;

        // Skip event trigger if receiver is an instance of QRv2
        if ($payment->getReceiverType() === VirtualAccount\Receiver::POS or $payment->isQrV2Payment() === true)
        {
            return;
        }

        if (($payment->isBankTransfer() === false) and
            ($payment->isBharatQr() === false) and
            ($payment->isUpiTransfer() === false) and
            ($payment->isOffline() === false))
        {
            return;
        }

        (new VirtualAccount\Core)->eventVirtualAccountCredited($payment);
    }

    protected function eventQrCodeCredited()
    {
        $payment = $this->payment;

        // Skip event trigger if receiver is not an instance of QRv2
        if ($payment->getReceiverType() === VirtualAccount\Receiver::POS or $payment->isQrV2Payment() === false)
        {
            return;
        }

        $receiver = $payment->getReceiver();

        if ($receiver->getRequestSource() === NonVAQr\RequestSource::CHECKOUT)
        {
            return;
        }

        (new NonVAQr\Service)->publishQrCodeEvent($payment, NonVAQr\Event::CREDITED);
    }

    public function eventPaymentCaptured()
    {
        $payment = $this->payment;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->dispatch('api.payment.captured', $eventPayload);

    }

    protected function updatePaymentCaptured($payment, $autoCaptured = false)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setCaptureTimestamp();

        $payment->setRefundAt(null);

        $payment->setAutoCaptured($autoCaptured);

        $this->setVerifyPaymentIfApplicable($payment);

        $this->trace->info(
            TraceCode::PAYMENT_STATUS_CAPTURED,
            [
                'payment_id'    => $payment->getId(),
                'auto_capture'  => $autoCaptured,
            ]);
    }

    public function createTransactionFromCapturedPayment(Payment\Entity $payment, $txnId = null)
    {
        $txnCore = new Transaction\Core;

        list($txn, $feesSplit) = $txnCore->createOrUpdateFromPaymentCaptured($payment, $txnId);

        if($payment->isHdfcNonDSSurcharge())
        {
            $txn->setTax(0);

            $txn->setFee(0);

            $txn->setMdr(0);
        }

        $payment->setTax($txn->getTax());

        if ($payment->isFeeBearerCustomer() === false)
        {
            //set and fee values from txn
            $payment->setFee($txn->getFee());
        }

        // $merchantBalance is required in the caller function only if lateBalanceUpdate is set to true.

        $merchantBalance = null;

        if ($payment->isLateBalanceUpdate() === true)
        {
            $merchantId = $txn->getMerchantId();

            $merchantBalance = $this->repo->balance->findOrFail($merchantId);

            $txn->accountBalance()->associate($merchantBalance);
        }

        $this->calculateAndSetMdrFeeIfApplicable($payment, $txn);

        $this->trace->debug(TraceCode::TRANSACTION_DETAILS,
            [
                'merchant_id'           => $txn->getMerchantId(),
                'transaction_id'        => $txn->getId(),
                'payment_id'            => $txn->getEntityId(),
                'transaction_credit'    => $txn->getCredit(),
                'transaction_debit'     => $txn->getDebit(),
                'transaction_amount'    => $txn->getAmount(),
                'transaction_fee'       => $txn->getFee()
            ]
        );

        if ($payment->isFeeBearerCustomer() === true and
            $payment->merchant->isCustomerFeeBearerAllowedOnInternational() and
            $payment->isInternational() === true)
        {
            // set and fee values from txn as it will have INR For Both DCC or MCC Payments
            $payment->setFee($txn->getFee());
        }

        $this->repo->saveOrFail($txn);

        $this->repo->saveOrFail($payment);

        $txnCore->saveFeeDetails($txn, $feesSplit);

        return [$txn, $merchantBalance];
    }

    protected function setVerifyPaymentIfApplicable(Payment\Entity & $payment)
    {
        $gateway = $payment->getGateway();

        // Ignore QR payments
        if ($payment->isBharatQr() === true)
        {
            return;
        }

        if (in_array($gateway, Payment\Gateway::$captureVerifyEnabled, true) === true)
        {
            $payment->setVerifyBucket(0);

            $payment->setVerifyAt(time());
        }
    }

    protected function verifyOrderUnpaid(Payment\Entity $payment)
    {
        if ($payment->hasOrder())
        {
            $order = $this->repo->order->fetchForPayment($payment);

            $this->repo->reload($order);

            if($order->isExternal() === true)
            {
                $order = $this->repo->order->findOrFail($order->getId());
            }

            if ($this->merchant->isFeatureEnabled(Feature\Constants::DISABLE_AMOUNT_CHECK) === true)
            {
                return;
            }

            if ($order->getStatus() === Order\Status::PAID)
            {
                // Setting Refund At value so that auto refund cron can pick payment to refund
                // in case order is already paid
                $payment->setRefundAt(Carbon::now()->getTimestamp());

                $this->repo->saveOrFail($payment);

                throw new Exception\BadRequestValidationFailureException(
                    'Corresponding order already has a captured payment.');
            }

            $amount = $payment->getAdjustedAmountWrtCustFeeBearer();

            $amount = $payment->getAmountWithoutConvenienceFeeIfApplicable($amount, $order);

            if (($amount > $order->getAmountDue($payment)) and
                ($this->merchant->isFeatureEnabled(Feature\Constants::EXCESS_ORDER_AMOUNT) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE);
            }
        }
    }

    /**
     * Update attributes of Order entity post corresponding payment is captured.
     *
     * @param Payment\Entity $payment
     */
    protected function updateOrderAfterCapture(Payment\Entity $payment, $originalPaymentFee)
    {
        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->order;

        $this->mutex->acquireAndRelease(
            $order->getId() . "_order_update_after_capture",
            function () use ($payment, $originalPaymentFee, $order) {

                $isOrderOutboxEnabled = false;

                $variant = $this->app->razorx->getTreatment($payment->getMerchantId(),
                         Merchant\RazorxTreatment::ORDER_OUTBOX_ONBOARDING, $this->mode);

                if (strtolower($variant) === 'on')
                {
                    $isOrderOutboxEnabled = true;
                }

                $this->trace->info(TraceCode::ORDER_OUTBOX_ONBOARDING_RAZORX_VARIANT, [
                    'merchant_id'           => $payment->getMerchantId(),
                    'variant'               => $variant,
                    'isOrderOutboxEnabled'  => $isOrderOutboxEnabled,
                ]);

                if ($isOrderOutboxEnabled === true)
                {
                    // Updating order fields(amount_paid and status) if we have an entry in the order outbox table
                    $order = (new Order\Core)->mergeOrderOutbox($order);
                }

                $paidAmount = $payment->getAdjustedAmountWrtMCCCustFeeBearer($originalPaymentFee);

                $paidAmount = $payment->getAmountWithoutConvenienceFeeIfApplicable($paidAmount, $order);

                $order->incrementAmountPaidBy($paidAmount);

                $this->updateOrderStatusPaidIfApplicable($order, $payment);

                if ($order->isExternal() === true) {
                    $outboxerEvent = OrderOutboxConstants::ORDER_AMOUNT_PAID_EVENT;

                    $input = [
                        Order\Entity::AMOUNT_PAID => $order->getAmountPaid()
                    ];

                    if ($order->getStatus() === Order\Status::PAID) {
                        $input[Order\Entity::STATUS] = Order\Status::PAID;

                        $outboxerEvent = OrderOutboxConstants::ORDER_STATUS_PAID_EVENT;
                    }

                    if ($isOrderOutboxEnabled === true)
                    {
                        $outbokerInput = [
                            OrderOutboxEntity::ORDER_ID => $order->getId(),
                            OrderOutboxEntity::MERCHANT_ID => $order->getMerchantId(),
                            OrderOutboxEntity::EVENT_NAME => $outboxerEvent,
                            OrderOutboxEntity::PAYLOAD => json_encode($input),
                        ];

                        $orderOutbox = new OrderOutboxEntity;

                        $orderOutbox->build($outbokerInput);

                        //Saving the order update in the in the order outboxer table,
                        //the order update to pg-router will go in sync on the basis of db commit hook
                        $this->repo->saveorFail($orderOutbox);

                        \Event::dispatch(new TransactionalClosureEvent(function () use ($orderOutbox) {
                            OrderUpdateByOutbox::dispatchNow($this->mode, $orderOutbox->getId());

                            $this->trace->info(TraceCode::ORDER_OUTBOX_PUSH_SUCCESS, [
                                'order_outbox' => $orderOutbox,
                            ]);
                        }));
                    }
                    else
                    {
                        $this->app['pg_router']->updateInternalOrder($input,$order->getId(),$order->getMerchantId(), true);
                    }

                } else {
                    $this->repo->saveOrFail($order);
                }
            }
        );

        $this->trace->info(
            TraceCode::ORDER_STATUS_PAID,
            [
                'payment_id' => $payment->getId(),
                'order_id'   => $order->getId(),
            ]);
    }

    protected function updateVirtualAccountStatusForVaPayment(Payment\Entity $payment)
    {
        $virtualAccountCore = new VirtualAccount\Core;

        if ($payment->isBankTransfer() === true)
        {
            $virtualAccount = $payment->bankTransfer->virtualAccount;
        }
        else if($payment->isUpiTransfer() === true)
        {
            $virtualAccount = $payment->upiTransfer->virtualAccount;
        }
        else if($payment->isOffline() === true)
        {
            $virtualAccount = $payment->receiver->virtualAccount;
        }

        if (($virtualAccount->hasAmountExpected() === true) and
                ($virtualAccount->getAmountPaid() >= $virtualAccount->getAmountExpected()))
        {
            $virtualAccountCore->updateStatus($virtualAccount, VirtualAccount\Status::PAID);
            return;
        }

        /*
          *  If amount paid is lesser than amount expected, then
          *  we will check the flag ACCEPT_LOWER_AMOUNT is enabled/not.
          * if not enabled the virtual account will stay in active state
          *  and payment will be refunded later by cron.
          */

        $order = $virtualAccount->entity;

        $merchant = $virtualAccount->merchant;

        if (($order !== null) and ($order->isPartialPaymentAllowed() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::ACCEPT_LOWER_AMOUNT) === true) and
            ($virtualAccount->getAmountPaid() > 0))
        {
            $virtualAccountCore->updateStatus($virtualAccount, VirtualAccount\Status::PAID);
        }

    }

    protected function updateVirtualAccountStatusForOrder(Payment\Entity $payment)
    {
        $virtualAccountCore = new VirtualAccount\Core;

        $order = $payment->order;

        $virtualAccount = $this->repo
                               ->virtual_account
                               ->findActiveVirtualAccountByOrder($order);

        if ($virtualAccount !== null)
        {
            $virtualAccountCore->updateStatus($virtualAccount, VirtualAccount\Status::CLOSED);
        }
    }

    public function processTransferIfApplicable(Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::ORDER_TRANSFER_PROCESS_INITIATED,
            [
                'order_id'   => $payment->getApiOrderId(),
                'payment_id' => $payment->getId(),
            ]
        );

        try
        {
            if ($this->shouldProcessOrderTransfer($payment) === false)
            {
                return;
            }

            $orderId = $payment->getApiOrderId();

            $transfersCount = Tracer::inSpan(['name' => 'order.transfer.update_status'], function() use ($orderId)
            {
                return $this->repo
                            ->transfer
                            ->updateTransferStatusBySourceTypeAndId(Constants\Entity::ORDER, $orderId, Transfer\Status::PENDING);
            });

            $input = [
                'order_id'    => $orderId,
                'payment_id'  => $payment->getId(),
                'mode'        => $this->mode,
            ];

            if($transfersCount > 0)
            {
                $this->trace->info(
                    TraceCode::ORDER_TRANSFER_PROCESS_SQS_PUSH_INIT,
                    [
                        'input' => $input,
                    ]);

                (new Transfer\Core())->dispatchForTransferProcessing(Transfer\Constant::ORDER, $payment);
            }
            else
            {
                $this->trace->info(
                    TraceCode::NO_TRANSFERS_FOR_ORDERS,
                    [
                        'input' => $input,
                    ]);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->critical(
                TraceCode::ORDER_TRANSFER_PROCESS_SQS_PUSH_FAILED,
                [
                    'order_id'   => $payment->getApiOrderId(),
                    'payment_id' => $payment->getId(),
                    'message'    => $e->getMessage(),
                ]);
        }
    }

    protected function updateVirtualAccountStatusIfApplicable(Payment\Entity $payment)
    {
        if ($payment->qrPayment !== null)
        {
            return;
        }

        if (($payment->isBankTransfer() === true) or
            ($payment->isUpiTransfer() === true) or
            ($payment->isOffline() === true))
        {
            /*
             *  If any payment is a Bank Transfer or offline and If amount
             *  paid is not lesser than amount expected, then
             *  we will mark the Virtual Account as paid.
             */

            $this->updateVirtualAccountStatusForVaPayment($payment);
        }
        else if ($payment->hasOrder() === true)
        {
            /*
             *  If payment is not a Bank Transfer, then we will close
             *  the Virtual Account that was created for the order.
             */

            $this->updateVirtualAccountStatusForOrder($payment);
        }
    }

    /*
     * For some of our older plugins (like Prestashop), the only indicator that
     * the payment was made via the plugin is in the user agent of the capture
     * request. To store this in the analytics table, we do a regex search on
     * the user-agent looking for plugin names and a semantic version number.
     *
     * Eg. User-Agent: Razorpay/v1 PHPSDK/2.0.2 PHP/7.0.33 Prestashop/2.0.0
     */
    protected function updateAnalyticsIfApplicable(Payment\Entity $payment)
    {
        if ($payment->analytics === null)
        {
            return;
        }

        // For newer plugins, the information sent in the payment request
        // (and set during time of authorization) takes precedence.
        if ($payment->analytics->getIntegration() !== null)
        {
            return;
        }

        $userAgent = $this->app['request']->header('User-Agent');

        foreach (Payment\Analytics\Metadata::INTEGRATION_VALUES as $integration => $_code)
        {
            $matches = null;

            $integrationSemVerRegexParts = [
                '/',                        // Regex delimiter
                '(' . $integration . ')',   // Group 1: Integration name
                '\/',                       // Forward slash dividing name from version
                '(\d+\.\d+\.\d+)',          // Group 2: Semantic Version
                '/'                         // Regex delimiter
            ];

            $integrationSemVerRegex = implode('', $integrationSemVerRegexParts);

            if (preg_match($integrationSemVerRegex, strtolower($userAgent), $matches) > 0)
            {
                $integration = $matches[1];

                $payment->analytics->setIntegration($integration);

                $integrationVersion = $matches[2];

                $payment->analytics->setIntegrationVersion($integrationVersion);

                $this->repo->saveOrFail($payment->analytics);

                break;
            }
        }
    }

    protected function updateOrderStatusPaidIfApplicable(Order\Entity $order, Payment\Entity $payment)
    {
        if ($this->shouldMarkOrderPaid($order, $payment) === true)
        {
            $order->setStatus(Order\Status::PAID);
        }
    }

    protected function shouldMarkOrderPaid(Order\Entity $order, Payment\Entity $payment)
    {
        if ($order->getAmountPaid() === $order->getAmount())
        {
            return true;
        }

        if ($order->getAmountPaid() > $order->getAmount())
        {
            return true;
        }

        $merchant = $order->merchant;
        if (($order->isPartialPaymentAllowed() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::ACCEPT_LOWER_AMOUNT) === true) and
            ($order->getAmountPaid() > 0))
        {
            return true;
        }

        return $this->isPaymentAndOrderAmountSame($order, $payment);
    }

    protected function isPaymentAndOrderAmountSame(Order\Entity $order, Payment\Entity $payment)
    {
        $discount = 0;

        if($payment->discount !== null)
        {
            $discount = $payment->discount->getAmount();
        }

        if (($payment->getAmount() + $discount) === $order->getAmount())
        {
            return true;
        }

        return false;
    }

    protected function postPaymentCaptureSubscriptionRegistrationProcessing(Payment\Entity $payment)
    {
        if ($payment->hasInvoice() === false)
        {
            return;
        }

        $invoice = $payment->invoice;

        if ($invoice->getEntityType() !== Constants\Entity::SUBSCRIPTION_REGISTRATION)
        {
            return;
        }

        $subscriptionRegistration = $invoice->entity;

        $token = $payment->getGlobalOrLocalTokenEntity();

        (new SubscriptionRegistration\Core)->authenticate($subscriptionRegistration, $token);
    }

    public function calculateAndSetMdrFeeIfApplicable(Payment\Entity $payment, Transaction\Entity $txn)
    {
        $paymentBaseAmount = $payment->getBaseAmount();
        $txnFee            = $txn->getFee();

        switch (true)
        {
            // BharatQr needs to be checked first, as the method in this case can be card
            // but the mdr rate is different from card / emi payments
            case $payment->isBharatQr():
                $mdrFee = $this->calculateMdr($paymentBaseAmount, $txnFee, $rate = 0.008);

                break;

            case $payment->isMethodCardOrEmi():
                $mdrFee = ($payment->card->isCredit() === true) ?
                    $txnFee :
                    $this->calculateMdr($paymentBaseAmount, $txnFee, $rate = 0.009);

                break;

            case $payment->isUpi():
                $mdrFee = $this->calculateMdr($paymentBaseAmount, $txnFee, $rate = 0.009);

                break;

            default:
                $mdrFee = $txnFee;

                break;
        }

        $payment->setMdr($mdrFee);
        $txn->setMdr($mdrFee);
    }

    protected function calculateMdr(int $paymentBaseAmount, int $txnFee, float $rate): int
    {
        $calculatedMdr = intval(ceil($paymentBaseAmount * $rate));

        return ($paymentBaseAmount <= self::MIN_MDR_PAYMENT_AMOUNT) ? 0 : min($calculatedMdr, $txnFee);
    }

    public function shouldProcessOrderTransfer(Payment\Entity $payment)
    {
        if ($payment->isCaptured() !== true or
            $payment->hasOrder() !== true)
        {
            $this->trace->info(TraceCode::ORDER_TRANSFER_PROCESS_PAYMENT_NOT_CAPTURED,
                               [
                                   'payment_id' => $payment->getId()
                               ]);
            return false;
        }

        $order = $payment->order;

        if ($order->getStatus() !== Order\Status::PAID or
            $order->isPartialPaymentAllowed() === true)
        {
            $this->trace->info(TraceCode::ORDER_TRANSFER_PROCESS_ORDER_NOT_PAID,
                               [
                                   'payment_id' => $payment->getId(),
                                   'order_id'   => $order->getId()
                               ]);
            return false;
        }

        return true;
    }

    /**
     * @param Payment\Entity $payment
     * @param $e
     * @return Payment\Entity
     * retrying capture for lost connect issue
     */
    protected function retryCapture(Payment\Entity $payment): Payment\Entity
    {
        //setting this to avoid lock on balance table
        $payment->setLateBalanceUpdate();

        //calling record capture to retry the capture
        $this->recordCapture(true);

        $this->triggerPaymentCapturedEvents();

        $this->publishMessageToSqsBarricade($this->payment);

        $this->notifyPaymentCaptured();

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CAPTURE_PROCESSED, $payment);

        return $payment;
    }

    protected function causedByLostConnection(\Exception $e)
    {
        $message = $e->getMessage();

        // should use the DetectsLostConnections Trait
        return Str::contains($message, [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order. Expected',
            'Adaptive Server connection failed',
            'Communication link failure',
            'connection is no longer usable',
            'Login timeout expired',
            'Connection refused',
            'running with the --read-only option so it cannot execute this statement',
            'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Name or service not known',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for',
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected',
            'SQLSTATE[HY000] [2002] Connection timed out',
            'SSL: Connection timed out',
            'SQLSTATE[HY000]: General error: 1105 The last transaction was aborted due to Seamless Scaling. Please retry.',
            'Temporary failure in name resolution',
            'SSL: Broken pipe',
            'SQLSTATE[08S01]: Communication link failure',
            'SQLSTATE[08006] [7] could not connect to server: Connection refused Is the server running on host',
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: No route to host',
            'The client was disconnected by the server because of inactivity. See wait_timeout and interactive_timeout for configuring this behavior.',
            'SQLSTATE[08006] [7] could not translate host name',
            'TCP Provider: Error code 0x274C',
            'SQLSTATE[HY000] [2002] No such file or directory',
            'SSL: Operation timed out',
            'Reason: Server is in script upgrade mode. Only administrator can connect at this time.',
            'Unknown $curl_error_code: 77',
        ]);
    }

    public function getDiscountIfApplicableForLedger($payment)
    {
        if ($payment->isAppCred() === true)
        {
            $discount = $this->repo->discount->fetchForPayment($payment);

            if ($discount !== null) {
                return $discount->getAmount();
            }
        }

        /* For the walnut369 sourced merchant, we don't apply our pricing on the payment and instead settle the amount
         * based on the subvention/mdr received in the payment which is used to create discount entity
         * */
        if (($payment->isCardlessEmiWalnut369() === true) and ($payment->merchant->isFeatureEnabled(Feature\Constants::SOURCED_BY_WALNUT369) === true))
        {
            $discount = $this->repo->discount->fetchForPayment($payment);

            if ($discount !== null) {
                $this->fees = 0;
                $this->tax = 0;
                return $discount->getAmount();
            }
        }
        return null;
    }

    protected function pushedToMerchantsBasedBalanceUpdateQueue($input, $merchantId, $asyncBalancePushedAt): bool
    {
        try{
            /* One or multiple merchants can be mapped to any of merchant based queues for balance update.
            * sample redis data:-  merchant_based_balance_update_queue -> {mid1 -> 'Queue1', mid2 -> 'Queue2'}
            * */
            $redisData = $this->app['redis']->hGetAll('merchant_based_balance_update_queue');

            if((isset($redisData[$merchantId]) === true) and ($redisData[$merchantId] === 'Queue1'))
            {
                MerchantBasedBalanceUpdateV1::dispatch($input, $this->mode, $asyncBalancePushedAt);
                return true;
            }
            else if((isset($redisData[$merchantId]) === true) and ($redisData[$merchantId] === 'Queue2'))
            {
                MerchantBasedBalanceUpdateV2::dispatch($input, $this->mode, $asyncBalancePushedAt);
                return true;
            }
            else if((isset($redisData[$merchantId]) === true) and ($redisData[$merchantId] === 'Queue3'))
            {
                MerchantBasedBalanceUpdateV3::dispatch($input, $this->mode, $asyncBalancePushedAt);
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::MERCHANT_BASED_BALANCE_UPDATE_QUEUE_FAILURE
            );
        }
        return false;
    }
}
