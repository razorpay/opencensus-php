<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use RZP\Exception;
use RZP\Models\Bank;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Currency;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Jobs\ScroogeRefund;
use RZP\Models\BankTransfer;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\BankAccount;
use RZP\Models\Customer\Token;
use RZP\Models\Card\NetworkName;
use RZP\Models\Feature\Constants as Feature;
use RZP\Jobs\ScroogeRefundRetry;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Metric as RefundMetric;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

trait Refund
{
    /**
     * @param Payment\Entity    $payment
     * @param array             $input   Refund input params
     * @param Batch\Entity|null $batch
     *
     * @return Payment\Refund\Entity
     *
     * @throws Exception\BadRequestException
     */
    protected function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        if ($payment->getGateway() === Payment\Gateway::BHARAT_QR)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED);
        }

        if ($payment->isDisputed() === true)
        {
            $openNonFraudDisputes = $this->repo->dispute->getOpenNonFraudDisputes($payment);

            if (count($openNonFraudDisputes) > 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED,
                    null,
                    ['input' => $input, 'payment_id' => $payment->getId()]);
            }
        }

        $refund = $this->buildRefundEntity($payment, $input, $batch);

        $this->processRefund();

        $this->pushMetrics();

        return $refund;
    }

    protected function pushMetrics()
    {
        $dimensions = RefundMetric::getDimensions($this->refund);

        $this->trace->count(RefundMetric::REFUND_CREATED_TOTAL, $dimensions);

        if ($this->payment->hasBeenCaptured() === true)
        {
            $this->trace->histogram(
                RefundMetric::REFUND_CREATED_FROM_CAPTURED_MINUTES,
                $this->refund->getCapturedToCreateTimeInMinutes(),
                $dimensions
            );
        }
        else
        {
            $this->trace->histogram(
                RefundMetric::REFUND_CREATED_FROM_AUTHORIZED_MINUTES,
                $this->refund->getAuthorizedToCreateTimeInMinutes(),
                $dimensions
            );
        }

        if ($this->refund->isBatch() === true)
        {
            $this->trace->histogram(
                RefundMetric::REFUND_CREATION_TIME_FOR_BATCH_MINUTES,
                $this->refund->getTimeFromCreatedInMinutes(),
                $dimensions
            );
        }
    }

    public function createRefundOnApiFromRecon(
        Payment\Entity $payment,
        string $refundId,
        int $refundAmount)
    {
        $this->createRefundOnApiSeparately($payment, $refundId, $refundAmount);
    }

    public function createRefundFromMerchantFile(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        return $this->refund($payment, $input, $batch);
    }

    public function createRefundOnApiForCancelledBilldeskRefund(
        Payment\Entity $payment,
        string $refundId,
        int $refundAmount)
    {
        $this->createRefundOnApiSeparately($payment, $refundId, $refundAmount);
    }

    public function verifyInternalRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $gateway = $payment->getGateway();

        Payment\Refund\Validator::validateVerifyInternalRefundAllowed($gateway);

        $data = $this->getGatewayDataForRefund($refund, $payment);

        if ($payment->isMethodCardOrEmi())
        {
            $card = $this->repo->card->fetchForPayment($refund->payment);
            $data['card'] = $card->toArray();
        }

        $msg = $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment, $refund)
        {
            $verify = $this->callGatewayForVerifyInternalRefund($data);

            // Flag indicating if this is a buggy case fix.
            $this->verifyRefundStatus = $verify;

            $msg = 'Refund verification unsuccessful.';

            if ($verify === false)
            {
                $refund->setGatewayRefunded(true);

                $this->recordTransactionAndUpdatePaymentForRefund();

                $this->trace->info(
                    TraceCode::VERIFY_REFUND_TRANSACTION_CREATED,
                    [
                        'payment_id'    => $payment->getId(),
                        'refund_id'     => $refund->getId(),
                    ]
                );

                //$this->sendRefundNotification($payment, $refund);

                $msg = 'Refund verification failed and Refund performed.';
            }
            else if ($verify === true)
            {
                $msg = 'Refund verified successfully.';
            }

            return $msg;
        });

        return ['verify_refund' => $msg];
    }

    public function scroogeGatewayRefund(RefundEntity $refund, array $input)
    {
        $payment = $refund->payment;

        //
        // Refunds are typically retried in groups using long-running
        // loops. This ensures that if a refund has been updated by a
        // different process, it is processed accordingly here.
        //
        $this->repo->reload($refund);

        $this->setPaymentAndRefundInfo($refund, $payment);

        $refundValidator = $refund->getValidator();

        $refundValidator->validateInput('scrooge_gateway_refund', $input);

        //
        // If refund is already processed, do not do anything. Return successful response from here.
        //
        if ($refund->isProcessed() === true)
        {
            return $this->prepareScroogeRefundResponse(
                                [Payment\Gateway::GATEWAY_RESPONSE => 'Refund has already been processed'],
                                true);
        }

        try
        {
            $refundValidator->validateScroogeGatewayRefund($payment);

            $verifyResponse = $scroogeResponse = $this->verifyRefund($refund);

            //
            // `verifyRefund` always returns back the response in the key
            // `success`. For scrooge, this key contains much more data
            // than just the value of success. It's an object.
            // For non-scrooge gateways, this is just a boolean value.
            // But since this function is called only for scrooge gateways,
            // we take the verifyRefundResponse['success'] and then go ahead
            // with the normal flow; i.e., reading the "actual" success flag
            //
            $callRefund = ($verifyResponse[Payment\Gateway::SUCCESS] !== true);

            if ($callRefund === true)
            {
                $scroogeResponse = $this->callRefundFunctionForScroogeWithData($refund, $input);
            }
        }
        catch (\Exception $ex)
        {
            $gatewayRefunded = false;

            $scroogeResponse = $this->prepareScroogeRefundResponse([], $gatewayRefunded, $ex);
        }

        $this->traceScroogeResponse(TraceCode::REFUND_SCROOGE_RESPONSE,
                                    $refund,
                                    $scroogeResponse);

        return $scroogeResponse;

        //
        // Note that we don't save any refund attributes when we call refund
        // via Scrooge. There's a different API which Scrooge will call to
        // update the refund attributes on successful processing.
        //
    }

    protected function callRefundFunctionForScroogeWithData(RefundEntity $refund, array $input)
    {
        $payment = $refund->payment;

        $data = $this->getGatewayDataForRefund($refund, $payment);

        $input['reverse'] = $data['refund']['reverse'];
        $data['refund'] = $input;

        $gatewayRefundResponse = $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($data, $payment)
            {
                return $this->callRefundFunction($payment, $data);
            });

        //
        // For non-scrooge gateways, this would generally be null.
        // We would be only reading `success` key and returning that back.
        // For scrooge gateways, we would get the `success` key along with
        // along with other keys like `gateway_refund_id`, etc
        // `gateway_refund_id` and other keys are present under `gateway_response`.
        // `gateway_response` ALSO contains the `success` key in it.
        //
        // Going forward, even non-scrooge gateways should be responding back
        // with whether the refund is success or not, instead of API relying on
        // "if exception, refund not successful. if no exception, refund successful"
        //

        return $gatewayRefundResponse;
    }

    public function scroogeGatewayVerifyRefund(RefundEntity $refund)
    {
        $payment = $refund->payment;

        //
        // Refunds are typically retried in groups using long-running
        // loops. This ensures that if a refund has been updated by a
        // different process, it is processed accordingly here.
        //
        $this->repo->reload($refund);

        $this->setPaymentAndRefundInfo($refund, $payment);

        if ($refund->isProcessed() === true)
        {
            return $this->prepareScroogeRefundResponse(
                [Payment\Gateway::GATEWAY_RESPONSE => 'Refund has already been processed'],
                true);
        }

        $refundValidator = $refund->getValidator();

        //
        // Scrooge gateways return back an object in the `success` key
        // non-scrooge gateways return back a boolean
        // Scrooge gateways return back lot of data like `status_code`,
        // `gateway_refund_id`, `success` etc in the object.
        //
        try
        {
            $refundValidator->validateScroogeGatewayRefund($payment);

            $gatewayVerifyRefundResponse = $this->verifyRefund($refund);
        }
        catch (\Exception $ex)
        {
            $gatewayVerifyRefundResponse = $this->prepareScroogeRefundResponse([], false, $ex);
        }

        $this->traceScroogeResponse(TraceCode::REFUND_SCROOGE_VERIFY_RESPONSE,
                                    $refund,
                                    $gatewayVerifyRefundResponse);

        return $gatewayVerifyRefundResponse;
    }

    /**
     * Traces response sent to scrooge
     *
     * @param string $traceCode
     * @param RefundEntity $refund
     * @param array $data
     */
    protected function traceScroogeResponse(string $traceCode, RefundEntity $refund, array $data)
    {
        $this->trace->info($traceCode, [
                'refund_id' => $refund->getId(),
                'gateway'   => $refund->getGateway(),
                'response'  => $data
        ]);
    }

    public function manualGatewayRefund(RefundEntity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $gateway = $payment->getGateway();

        Payment\Refund\Validator::validateManualGatewayRefundAllowed($gateway);

        // The refund should have already been successful and everything on the api side.
        assert ($refund->getTransactionId() !== null);

        // Just making sure that the payment also has the transaction id. Refund will not have a transaction
        // if payment does not have a transaction, anyway.
        assert ($payment->getTransactionId() !== null);

        // The payment should have been captured. Otherwise, refund transaction should not have been created.
        // Though, there are some edge cases where refund transaction was created even though the payment has not
        // been captured. Check PR #905 and #909.

        // Commenting this because we have reached past this stage.
        // A refund transaction could have been created even if the payment is not captured.
        // assert (($payment->hasBeenCaptured() === true) or
        //         (in_array($payment->card->getNetworkCode(),
        //                   [Card\Network::MAES, Card\Network::RUPAY, Card\Network::DICL]) === true));

        $data = $this->getGatewayDataForRefund($refund, $payment);

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $payment->card->toArray();
        }

        // The reason for NOT using verifyRefund Gateway function is because in ManualRefund, we want to add
        // more checks and validations in the gateway function. VerifyRefund takes care of the checks specific
        // to verifyRefund only. Since manualRefund is a very exceptional case and hopefully a one-time execution,
        // we want to add more asserts around it.
        $manualGatewayRefundResult = $this->callGatewayForManualRefund($data);

        // Here, $manualGatewayRefundResult=true means that the payment is refunded on the gateway side.
        if ($manualGatewayRefundResult === true)
        {
            $msg = 'Successfully created a refund on gateway';
        }
        else if ($manualGatewayRefundResult === false)
        {
            $msg = 'DID NOT CREATE A REFUND ON GATEWAY. ISSUE!';
        }
        else
        {
            $msg = 'THIS IS UNEXPECTED!';
        }

        return [
            'manual_gateway_refund' => $msg,
            'payment_id'            => $payment->getId(),
            'refund_id'             => $refund->getId(),
        ];
    }

    /**
     * Calls verifyRefund on gateway.
     * Identifies if the refund passed here was processed
     * by the gateway.
     *
     * @param RefundEntity $refund
     *
     * @return array
     */
    public function verifyRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        if ($this->isFundTransferAttemptRefund($payment) === true)
        {
            $verifyRefundResult = $this->prepareScroogeRefundResponse([], false);
        }
        else
        {
            $this->setPaymentAndRefundInfo($refund, $payment);

            $gateway = $payment->getGateway();

            Payment\Refund\Validator::validateVerifyRefundAllowed($gateway);

            $data = $this->getGatewayDataForRefund($refund, $payment);

            //
            // In case of scrooge gateways, `verifyRefundResult` contains
            // a key `success` with true/false, along with some other
            // keys like `gateway_response`. We return back the whole
            // thing to Scrooge as it is.
            // In case of non-scrooge gateways, `verifyRefundResult` is
            // a plain true or false and nothing else.
            //
            // This is handled in the respective (scrooge/non-scrooge) callers.
            //
            $verifyRefundResult = $this->callGatewayForVerifyRefund($data);
        }

        return $verifyRefundResult;
    }

    public function createGatewayRefundRecord(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        // The refund should have already been successful and everything on the api side.
        // Because on timeout, we would have ignored it and created a refund as it was successful.
        assert ($refund->getTransactionId() !== null);

        // Just making sure that the payment also has the transaction id. Refund will not have a transaction
        // if payment does not have a transaction, anyway.
        assert ($payment->getTransactionId() !== null);

        $data = [
            'payment'   => $payment->toArrayGateway(),
            'refund'    => $refund->toArrayGateway(),
            'amount'    => $refund->getAmount(),
            'currency'  => $refund->getCurrency()
        ];

        return $this->callGatewayForCreateRefundRecord($data);
    }

    public function refundAuthorizedPayment(Payment\Entity $payment, array $input = [])
    {
        $this->trace->info(
            TraceCode::REFUND_FROM_AUTHORIZED_REQUEST,
            [
                'payment_id'    => $payment->getId(),
                'input'         => $input,
            ]);

        // Some bank transfer payments cannot be refunded.
        if ($payment->isBankTransfer() === true)
        {
            $this->validateBankTransferPaymentForRefund($payment);
        }

        $this->setPayment($payment);

        if ($this->payment->isAuthorized() === false)
        {
            throw new Exception\InvalidArgumentException(
                'Can only refund authorized payments here but ' .
                'the status is ' . $payment->getStatus());
        }

        // For now allow refunding authorized payments immediately.
        // $days = 5;

        // if ($this->payment->getDaysSinceAuthorized() <= $days)
        // {
        if ((isset($input['force'])) and
            ($input['force'] === '1'))
        {
            unset($input['force']);
        }
        //     else
        //     {
        //         throw new Exception\BadRequestValidationFailureException(
        //             'The authorized payment is not older than: ' . $days . ' days');
        //     }
        // }

        return $this->refund($payment, $input);
    }

    public function refundPaymentViaMerchant($paymentId, $input)
    {
        $payment = $this->retrieve($paymentId);

        // From subscription service we will always refund authorized payments
        if ($this->ba->isSubscriptionsApp() === true)
        {
            return $this->refundAuthorizedPayment($payment, $input);
        }

        return $this->refundCapturedPayment($payment, $input);
    }

    /**
     * Process refund on a payment that has Marketplace transfers
     *
     * @param array $input
     */
    public function processRefundWithTransfers(array $input)
    {
        if (isset($input['reversals']) === false)
        {
            return;

            // throw new Exception\BadRequestValidationFailureException(
            //         'The reversals parameter is required for this refund request');
        }

        $this->repo->transaction(function () use ($input)
        {
            $this->processReversals($input['reversals']);

            unset($input['reversals']);
        });
    }

    public function refundPaymentViaBatchEntry(Payment\Entity $payment, Batch\Entity $batch, array $input)
    {
        //
        // Check if a refund already exists.
        // If one exists, then we should not fire a new one else two refunds will happen.
        //
        $refund = $this->findExistingRefundForBatch($batch, $payment);

        if ($refund !== null)
        {
            return $refund;
        }

        // No refund existed so fire a new one.
        return $this->refundCapturedPayment($payment, $input, $batch);
    }

    /**
     * @param Payment\Refund\Entity $refund
     * @param Payment\Entity $payment
     *
     * @return null|Transaction\Entity
     * @throws Exception\LogicException
     */
    public function createTransactionForRefund(
        Payment\Refund\Entity $refund, Payment\Entity $payment)
    {
        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_REQUEST,
            [
                'refund_id'     => $refund->getId(),
                'payment_id'    => $payment->getId(),
            ]);

        if ($refund->getTransactionId() !== null)
        {
            throw new Exception\LogicException(
                'Transaction should not already been created for this',
                null,
                [
                    'refund_id'     => $refund->getId(),
                    'payment_id'    => $payment->getId(),
                ]);
        }

        //
        // We cannot have gateway_captured check here because
        // if payment transaction is not created, we cannot
        // create refund transaction. The ledger flow will not
        // be right if we do that. We should create a refund
        // transaction ONLY after payment transaction is created
        // to ensure the ledger flow is correct.
        //
        if ($payment->getTransactionId() === null)
        {
            return null;
        }

        $txn = (new Transaction\Core)->createFromRefund($refund);

        $this->repo->saveOrFail($txn);

        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATED,
            [
                'payment_id'        => $payment->getId(),
                'refund_id'         => $refund->getId(),
                'transaction_id'    => $txn->getId(),
            ]);

        return $txn;
    }

    /**
     * Get the type of refund being processed - FULL / PARTIAL,
     * based on the refund amount and amount already refunded
     *
     * @param array $input
     *
     * @return string
     */
    protected function getPaymentRefundType(array $input)
    {
        $type = Payment\RefundStatus::PARTIAL;

        if ((isset($input['amount']) === false) or
            ((int) $input['amount'] === $this->payment->getAmountUnrefunded()))
        {
            $type = Payment\RefundStatus::FULL;
        }

        return $type;
    }

    protected function callGatewayForVerifyInternalRefund($data)
    {
        $verifyRefundResult = null;

        try
        {
            $verifyRefundResult = $this->callGatewayFunction(Payment\Action::VERIFY_INTERNAL_REFUND, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_VERIFY_INTERNAL_REFUND_FAILURE);

            throw $e;
        }

        return $verifyRefundResult;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function callGatewayForVerifyRefund($data)
    {
        $verifyRefundResult = $this->callGatewayFunction(Payment\Action::VERIFY_REFUND, $data);

        //
        // For Scrooge gateways, the result will be an object.
        // For non-scrooge gateways, it'll be just a boolean value.
        // We convert it into a proper object and send it back.
        //
        if (is_bool($verifyRefundResult) === false)
        {
            return $verifyRefundResult;
        }

        return $this->prepareScroogeRefundResponse([], $verifyRefundResult);
    }

    protected function callGatewayForAlreadyRefunded($data)
    {
        $gatewayRefunded = $this->callGatewayFunction(Payment\Action::ALREADY_REFUNDED, $data);

        return $gatewayRefunded;
    }

    protected function callGatewayForManualRefund($data)
    {
        $manualGatewayRefundResult = null;

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_REFUND_INITIATED,
            [
                'payment_id'    => $data['payment']['id'],
                'refund_id'     => $data['refund']['id'],
            ]);

        try
        {
            $manualGatewayRefundResult = $this->callGatewayFunction(Payment\Action::MANUAL_GATEWAY_REFUND, $data);
        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::MANUAL_GATEWAY_REFUND_FAILURE
            );

            throw $ex;
        }

        return $manualGatewayRefundResult;
    }

    protected function callGatewayForCreateRefundRecord(array $data)
    {
        try
        {
            return $this->callGatewayFunction(Payment\Action::CREATE_REFUND_RECORD, $data);
        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::CREATE_GATEWAY_REFUND_RECORD_FAILED
            );

            throw $ex;
        }
    }

    protected function callGatewayForRefundValidation(array $data)
    {
        try
        {
            return $this->callGatewayFunction(
                Payment\Action::VALIDATE_UNKNOWN_REFUND, $data);
        }
        catch (Exception\BaseException $ex)
        {
            $this->tracePaymentFailed(
                $ex->getError(),
                TraceCode::GATEWAY_REFUND_VALIDATION_FAILED
            );

            throw $ex;
        }
    }

    protected function refundOnGateway($data, $retry = false)
    {
        $gatewayRefunded = false;

        //
        // For scrooge gateways, we will get a proper gateway response back
        // For non-scrooge gateways, we will not receive anything in the response (null).
        //
        $gatewayResponse = [];

        $e = null;

        try
        {
            //
            // In case of emandate payments, the amount is 0.
            // For non-emandate payments also, if the refund amount
            // is 0, we don't have to send it to the gateway at
            // all since there's no money to be refunded here as
            // such. We can just mark it as processed.
            //
            if ($this->refund->getAmount() !== 0)
            {
                $gatewayResponse = $this->callGatewayFunction(Payment\Action::REFUND, $data);
            }

            $gateway = $data['payment'][Payment\Entity::GATEWAY];
            $merchantId = $data['payment'][Payment\Entity::MERCHANT_ID];

            //
            // TODO: Remove for Scrooge
            // For refunds on Scrooge-enabled gateways, Scrooge
            // makes an API call to mark it as processed, later.
            //
            // Marking refund as processed always in case of retry
            // because for older refunds of scrooge gateways,
            // scrooge will not call API to mark processed as older refunds
            // are retried via API code itself and not via scrooge.
            //
            if ((Payment\Gateway::isScroogeGatewayAndMerchant($gateway, $merchantId) === false) or
                ($retry === true))
            {
                $this->refund->setStatusProcessed();
            }

            $gatewayRefunded = true;
        }
        catch (Exception\BaseException $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYMENT_REFUND_FAILURE);

            $this->tracePaymentFailed(
                $e->getError(),
                TraceCode::PAYMENT_REFUND_FAILURE);

            $this->updateRefundFailed($e);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYMENT_REFUND_FAILURE);
        }
        finally
        {
            if (isset($e) === true)
            {
                $this->app['segment']->trackPayment(
                    $this->payment, TraceCode::PAYMENT_REFUND_FAILURE);

                $this->refund->setStatus(Payment\Refund\Status::FAILED);

                // Only BaseException would have `getData` function
                if ($e instanceof Exception\BaseException)
                {
                    $gatewayResponse = $e->getData();
                }
            }
        }

       return $this->prepareScroogeRefundResponse($gatewayResponse, $gatewayRefunded, $e);
    }

    protected function prepareScroogeRefundResponse($gatewayResponse, $gatewayRefunded, $exception = null)
    {
        return [
            Payment\Gateway::SUCCESS            => $gatewayRefunded,
            Payment\Gateway::STATUS_CODE        => ($gatewayRefunded === true) ?
                                                   'REFUND_SUCCESSFUL' :
                                                   (
                                                       (empty($exception) === false) ?
                                                       (string) $exception->getCode() :
                                                       ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED
                                                   ),
            Payment\Gateway::GATEWAY_RESPONSE   => $gatewayResponse[Payment\Gateway::GATEWAY_RESPONSE] ??
                                                   (
                                                       empty($exception) === false ?
                                                       $exception->getMessage() :
                                                       ''
                                                   ),
            Payment\Gateway::GATEWAY_KEYS       => $gatewayResponse[Payment\Gateway::GATEWAY_KEYS] ?? []
        ];
    }

    protected function reverseOnGateway($data, $retry = false)
    {
        $reversed = false;

        //
        // For scrooge gateways, we will get a proper gateway response back
        // For non-scrooge gateways, we will not receive anything in the response (null).
        //
        $gatewayResponse = [];

        $e = null;

        try
        {
            if ($this->refund->getAmount() !== 0)
            {
                $gatewayResponse = $this->callGatewayFunction(Payment\Action::REVERSE, $data);
            }

            $gateway = $data['payment'][Payment\Entity::GATEWAY];
            $merchantId = $data['payment'][Payment\Entity::MERCHANT_ID];

            //
            // TODO: Remove for Scrooge
            // For refunds on Scrooge-enabled gateways, Scrooge makes an API call to mark it as processed, later.
            //
            // Marking refund as processed always in case of retry because for older
            // refunds of scrooge gateways, scrooge will not call API to mark processed
            // as older refunds are retried via API admin dashboard not via scrooge.
            //
            if ((Payment\Gateway::isScroogeGatewayAndMerchant($gateway, $merchantId) === false)
                or ($retry === true))
            {
                $this->refund->setStatusProcessed();
            }

            $reversed = true;
        }
        catch (Exception\BaseException $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYMENT_REFUND_FAILURE);

            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REVERSE_FAILURE);

            $this->updateRefundFailed($e);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, TraceCode::PAYMENT_REVERSE_FAILURE);
        }
        finally
        {
            if (isset($e) === true)
            {
                $this->app['segment']->trackPayment(
                    $this->payment, TraceCode::PAYMENT_REVERSE_FAILURE);

                $this->refund->setStatus(Payment\Refund\Status::FAILED);

                if ($e instanceof Exception\BaseException)
                {
                    $gatewayResponse = $e->getData();
                }
            }
        }

        return $this->prepareScroogeRefundResponse($gatewayResponse, $reversed, $e);
    }

    protected function updateRefundFailed($exception)
    {
        $error = $exception->getError();

        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $internalCode = $error->getInternalErrorCode();

        $this->refund->setError($code, $desc, $internalCode);
    }

    protected function recordTransactionForRefund()
    {
        $this->repo->transaction(function()
        {
            $payment = $this->payment;

            $this->repo->payment->lockForUpdate($payment->getKey());

            $this->createTransactionForRefund($this->refund, $payment);

            //
            // This needs to be saved here because of the association with
            // transaction which is set in the createTransactionForRefund function.
            //
            $this->repo->saveOrFail($this->refund);
        });
    }

    protected function recordTransactionAndUpdatePaymentForRefund()
    {
        $this->repo->transaction(function()
        {
            $payment = $this->payment;

            $this->repo->payment->lockForUpdate($payment->getKey());

            $this->createTransactionForRefund($this->refund, $payment);

            $this->updatePaymentRefunded();
        });
    }

    protected function buildRefundEntity(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        $this->setPayment($payment);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        $refund->setBaseAmount();

        if ($this->payment->isCaptured() === true)
        {
            $this->validateMerchantBalance($refund, 'refund');
        }

        $refund->batch()->associate($batch);

        $this->refund = $refund;

        return $refund;
    }

    protected function processRefund()
    {
        $payment = $this->refund->payment;

        $data = $this->getGatewayDataForRefund($this->refund, $payment);

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment)
        {
            $payment->reload();

            if ($payment->isFullyRefunded() === true)
            {
                throw new Exception\InvalidArgumentException(
                    'Can only refund a non-refunded payment but here ' .
                    'the status is ' . $payment->getStatus());
            }

            $this->repo->transaction(function()
            {
                $this->recordTransactionForRefund();

                // update the payment entity for refund
                $this->updatePaymentRefunded();
            });

            $gateway = $this->refund->getGateway();
            $merchantId = $this->refund->merchant->getId();

            // TODO: Remove for Scrooge
            if (Payment\Gateway::isScroogeGatewayAndMerchant($gateway, $merchantId) === true)
            {
                $this->callRefundFunctionOnScrooge($this->refund);
            }
            else
            {
                $this->callRefundFunctionOnApi($payment, $data);
            }

            // send notification to merchant/customer, this is outside transaction
            // as we don't want to reverse the actions if mail sending fails
            $this->sendRefundNotification($payment);
        }, 120);

        $this->trace->info(
            TraceCode::REFUND_PROCESSED,
            [
                'payment_id'    => $payment->getId(),
                'refund'        => $this->refund->toArray(),
            ]);

        return $this->refund;
    }

    public function callRefundFunctionOnScrooge($refund)
    {
        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment);

        $refund->incrementAttempts();

        $this->repo->saveOrFail($refund);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH,
            $data
        );

        ScroogeRefund::dispatch($data);
    }

    protected function callRefundFunctionOnApi($payment, $data)
    {
        $refunded = $this->callRefundFunction($payment, $data);

        $this->refund->setGatewayRefunded($refunded[Payment\Gateway::SUCCESS]);

        $this->refund->incrementAttempts();

        // We don't want the transaction to fail if this
        // save fails that's why keeping it outside.
        $this->repo->saveOrFail($this->refund);
    }

    protected function callRefundFunction($payment, $data, $retry = false)
    {
        // TODO: Handle FTAs, Bank Transfers in Scrooge enabled gateways

        $gateway = $payment->getGateway();

        $isScroogeGateway = Payment\Gateway::isScroogeGatewayAndMerchant($gateway, $payment->getMerchantId());

        if (($isScroogeGateway === false) and
            ($this->isFundTransferAttemptRefund($payment, $data) === true))
        {
            return $this->refundViaFundTransfer($payment, $data);
        }
        else
        {
            return $this->callGatewayRefundFunction($payment, $data, $retry);
        }
    }

    protected function callGatewayRefundFunction($payment, $data, $retry = false)
    {
        //
        // Refunds which are not refunded or reversed on gateway will be marked as processed.
        // These will be the cases of auto refunds/reversals at gateway side after 15 days or so.
        //
        $gatewayResponse = [Payment\Gateway::GATEWAY_RESPONSE => TraceCode::REFUND_AUTOREFUNDED_ON_GATEWAY];

        $refundData = $this->prepareScroogeRefundResponse($gatewayResponse, true);

        // refund/reverse on gateway
        if (($payment->getTransactionId() !== null) or
            ($payment->isGatewayCaptured() === true))
        {
            $refundData = $this->refundOnGateway($data, $retry);
        }
        else if ($this->gatewaySupportsReversal($payment) === true)
        {
            $refundData = $this->reverseOnGateway($data, $retry);
        }

        return $refundData;
    }

    /**
     * When a refund is requested to be retried.
     * i.e A failed refund on api side.
     *
     * - first verify on gateway if the refund was processed.
     * - if not processed, refund on gateway
     *
     *   if refund was attempted i.e after the verify call
     *   if a call to reverse or refund occurred
     *   increment attempts and set last_attempted_at
     *
     *   if successful
     * - update the refund status to processed.
     * - no need to update payment - marked as refunded
     *
     * @param Payment\Refund\Entity $refund
     * @param array $input Values passed in API input
     *
     * @return string
     */
    public function processRefundRetry(Payment\Refund\Entity $refund, array $input = [])
    {
        $payment = $refund->payment;

        //
        // Refunds are typically retried in groups using long-running
        // loops. This ensures that if a refund has been updated by a
        // different process, it is processed accordingly here.
        //
        $this->repo->reload($refund);

        $refund->setErrorNull();

        $this->setPaymentAndRefundInfo($refund, $payment);

        $data = $this->getGatewayDataForRefund($refund, $payment);

        $data = array_merge($data, $input);

        //
        // Adding check for scrooge gateway here. For scrooge gateways, only refunds which are in failed state will be
        // retried. This is done to provide support for older refunds which were not processed via scrooge and are in
        // failed state. As scrooge doesn't mark refund as failed, new refunds will never be retried via this flow.
        //
        if ($refund->isProcessed() === true)
        {
            return $refund->getStatus();
        }

        if ((Payment\Gateway::isScroogeGatewayAndMerchant($refund->getGateway(), $refund->getMerchantId()) === true) and
            ($refund->isCreated() === true))
        {
            $this->callRefundRetryFunctionOnScrooge($refund);
        }
        else if ($refund->isStatusFailed() === true)
        {
            $this->callRefundRetryFunctionOnApi($refund, $data);
        }

        return $refund->getStatus();
    }

    public function callRefundRetryFunctionOnScrooge($refund)
    {
        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::REFUND_RETRY_QUEUE_SCROOGE_DISPATCH,
            $data
        );

        ScroogeRefundRetry::dispatch($data);
    }

    public function callRefundRetryFunctionOnApi($refund, $data)
    {
        $payment = $refund->payment;

        $refundedOnGateway = $this->verifyRefund($refund);

        // true  if refunded
        // false if not refunded
        $refundedOnGateway = $refundedOnGateway[Payment\Gateway::SUCCESS];

        if ($refundedOnGateway === false)
        {
            $refundedOnGateway = $this->mutex->acquireAndRelease(
                $payment->getId(),
                function() use ($data, $payment)
                {
                    //
                    // Setting retry to true, will use this action later to decide weather
                    // scrooge refund should be marked as processed or not. If scrooge refunds
                    // are retried, we will mark them as processed otherwise not.
                    // For refunds attempted first time, will be marked processed by Scrooge call.
                    //
                    $refundResponse = $this->callRefundFunction($payment, $data, true);

                    return $refundResponse[Payment\Gateway::SUCCESS];
                });

            $refund->incrementAttempts();
        }
        else
        {
            $refund->setStatusProcessed();
        }

        $refund->setGatewayRefunded($refundedOnGateway);

        $this->repo->saveOrFail($refund);
    }

    protected function gatewaySupportsReversal($payment)
    {
        $gateway = $payment->getGateway();

        return Payment\Gateway::supportsReverse($gateway);
    }

    protected function updatePaymentRefunded()
    {
        //
        // Indicates inverse of buggy case where refund entity is already present
        // Need to check against false only, since it can be `null` also.
        // In case of `null` or `true` values we update payment amounts
        //
        if ($this->verifyRefundStatus !== false)
        {
            $amount = $this->refund->getAmount();

            $baseAmount = $this->refund->getBaseAmount();

            $this->payment->refundAmount($amount, $baseAmount);
        }

        $this->repo->transaction(function()
        {
            $this->repo->saveOrFail($this->payment);

            $this->repo->saveOrFail($this->refund);
        });

        $this->trace->info(
            TraceCode::PAYMENT_REFUND_SUCCESS,
            [
                'payment_id'            => $this->payment->getId(),
                'payment_status'        => $this->payment->getStatus(),
                'payment_amount'        => $this->payment->getAmount(),
                'gateway'               => $this->payment->getGateway(),
                'refund_id'             => $this->refund->getId(),
                'refund_amount'         => $this->refund->getAmount(),
                'refund_base_amount'    => $this->refund->getBaseAmount(),
            ]);

        $this->app['segment']->trackPayment($this->payment, TraceCode::PAYMENT_REFUND_SUCCESS);
    }

    /**
     * Validate merchant balance before a refund operation is processed
     *
     * @param RefundEntity $refund
     * @param string       $type
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function validateMerchantBalance(RefundEntity $refund, string $type = 'refund')
    {
        $merchant = $refund->merchant;

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        $traceData = [
            'type'              => $type,
            'message'           => 'Not enough balance',
            'merchant_balance'  => $balance->getBalance(),
            'merchant_credits'  => $balance->getRefundCredits(),
            'refund_amount'     => $refund->getBaseAmount(),
            'refund_id'         => $refund->getId(),
        ];

        if (($merchant->getRefundSource() === RefundSource::CREDITS) and
            ($balance->getRefundCredits() < $refund->getBaseAmount()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
                null,
                $traceData);
        }

        if (($merchant->getRefundSource() === RefundSource::BALANCE) and
            ($balance->getBalance() < $refund->getBaseAmount()))
        {
            if ($type === 'refund')
            {
                $this->app['segment']->trackPayment(
                    $refund->payment,
                    TraceCode::PAYMENT_REFUND_FAILURE,
                    $traceData);

                $error = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;
            }
            else if ($type === 'reversal')
            {
                $error = ErrorCode::BAD_REQUEST_TRANSFER_REVERSAL_INSUFFICIENT_BALANCE;
            }
            else
            {
                throw new Exception\LogicException(
                    'Invalid type for refund validate balance - ' . $type,
                    null,
                    $traceData
                );
            }

            throw new Exception\BadRequestException($error, null, $traceData);
        }
    }

    protected function getGatewayDataForRefund(Payment\Refund\Entity $refund, Payment\Entity $payment)
    {
        $data = [
            'payment'   => $payment->toArrayGateway(),
            'refund'    => $refund->toArrayGateway(),
            'amount'    => $refund->getAmount(),
            'currency'  => $refund->getCurrency(),
        ];

        if ($payment->getConvertCurrency())
        {
            $data['amount'] = $refund->getBaseAmount();

            $data['currency'] = Currency\Currency::INR;
        }

        if ($payment->isMethodCardOrEmi() === true)
        {
            $card = $this->repo->card->fetchForPayment($payment);

            $data['card'] = $card->toArray();
        }

        // refund/reverse on gateway
        if (($payment->getTransactionId() !== null) or
            ($payment->isGatewayCaptured() === true))
        {
            $data['refund']['reverse'] = false;
        }
        else if ($this->gatewaySupportsReversal($payment) === true)
        {
            $data['refund']['reverse'] = true;
        }

        return $data;
    }

    protected function getGatewayDataForScroogeRefund(Payment\Refund\Entity $refund, Payment\Entity $payment)
    {
        $refundData = $refund->toArrayGateway();

        $extraData = [
            'method'                    => $payment->getMethod(),
            'payment_amount'            => $payment->getAmount(),
            'payment_base_amount'       => $payment->getBaseAmount(),
            'payment_created_at'        => $payment->getCreatedAt(),
            'payment_gateway_captured'  => $payment->getGatewayCaptured()
        ];

        $scroogeData = array_merge($refundData, $extraData);

        if ($payment->isNetbanking() === true)
        {
            $scroogeData['bank'] = $payment->getBank();
        }

        //
        // These attributes are already in scrooge, need to be reset before sending scrooge request
        //
        unset($scroogeData['status']);

        return $scroogeData;
    }

    protected function findExistingRefundForBatch(Batch\Entity $batch, Payment\Entity $payment)
    {
        // This ensure that if that batch entity is already processed, we update the refund id
        $refunds = $this->repo->refund->fetchRefundsByBatchAndPayment($batch, $payment);

        $count = count($refunds);

        if ($count > 0)
        {
            $this->trace->error (
                TraceCode::BATCH_ALREADY_PROCESSED,
                [
                    'message' => 'Batch entry already processed',
                    'batch'   => $batch->getId(),
                    'refunds' => $refunds->toArrayPublic()
                ]
            );

            assert($count === 1);

            return $refunds[0];
        }

        return null;
    }

    public function refundCapturedPayment($payment, array $input = [], Batch\Entity $batch = null)
    {
        $this->validatePaymentForRefund($payment);

        // Captured payments of transfer cannot be refunded via direct API requests
        if ($payment->isTransfer() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED);
        }

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($input, $payment)
        {
            // Determine if transfer reversals should be processed along with the refund
            $processReversals = $this->shouldProcessReversals($payment, $input);

            if ($processReversals === true)
            {
                $this->processRefundWithTransfers($input);
            }
        });

        return $this->refund($payment, $input, $batch);
    }

    protected function validatePaymentForRefund(Payment\Entity $payment)
    {
        if ($payment->isFullyRefunded() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }

        if ($payment->isCaptured() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
        }

        // Some bank transfer payments cannot be refunded.
        if ($payment->isBankTransfer() === true)
        {
            $this->validateBankTransferPaymentForRefund($payment);
        }
    }

    protected function validateBankTransferPaymentForRefund(Payment\Entity $payment)
    {
        (new BankTransfer\Validator)->validatePaymentForRefund($payment);
    }

    protected function setPaymentAndRefundInfo($refund, $payment)
    {
        $this->merchant = $payment->merchant;

        $this->methods = $payment->merchant->methods;

        $this->refund = $refund;

        $this->payment = $payment;
    }

    /**
     * Sends out refund related notifications
     * To 3 places in total:
     *
     * - Dashboard (for analytics)
     * - Slack (for us to see)
     * - EMails (to both customer and merchant)
     *
     * @param  Payment\Entity $payment Payment Entity
     */
    protected function sendRefundNotification(Payment\Entity $payment)
    {
        //
        // Analytics is on dashboard side for now
        //

        $notifier = new Notify($payment);
        $notifier->addRefund($this->refund);
        $notifier->trigger(Payment\Event::REFUNDED);
    }

    protected function createRefundOnApiSeparately(
        Payment\Entity $payment,
        string $refundId,
        int $refundAmount)
    {
        if ($payment->transaction === null)
        {
            throw new Exception\LogicException(
                'Transaction expected but not present for payment',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'refund_id'     => $refundId
                ]);
        }

        $input = [
            'amount' => $refundAmount
        ];

        $refund = $this->buildRefundEntity($payment, $input);

        $this->setPaymentAndRefundInfo($refund, $payment);

        $refund->setId($refundId);

        $data = [
            'payment_id' => $payment->getId(),
            'refund_id' => $refundId,
            'refund_amount' => $refundAmount,
        ];

        $gatewayRefunded = $this->callGatewayForAlreadyRefunded($data);

        if ($gatewayRefunded === false)
        {
            throw new Exception\LogicException(
                'Should have been refunded on gateway but is not',
                ErrorCode::SERVER_ERROR_GATEWAY_NOT_REFUNDED,
                $data);
        }

        $this->refund->setGatewayRefunded(true);

        $this->recordTransactionAndUpdatePaymentForRefund();
    }

    public function validateUnknownGatewayRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        assert ($refund->getTransactionId() !== null);

        assert ($payment->getTransactionId() !== null);

        $data = [
            'payment'   => $payment->toArrayGateway(),
            'refund'    => $refund->toArrayGateway(),
            'amount'    => $refund->getAmount(),
            'currency'  => $refund->getCurrency()
        ];

        return $this->callGatewayForRefundValidation($data);
    }

    protected function refundViaFundTransfer(Payment\Entity $payment, $data = []): array
    {
        if ($this->refund->getAmount() === 0)
        {
            $this->refund->setStatus(Payment\Refund\Status::PROCESSED);

            return [Payment\Gateway::SUCCESS => true];
        }

        $refunded = false;

        try
        {
            $input = $this->getBankAccountInput($payment, $data);

            $fundTransferAttemptInput = $this->getFundTransferAttemptInput($payment);

            if (($this->refund->hasBankAccount() === false) or
                ($this->refund->bankAccount->matches($input) === false))
            {
               $this->createAndAssociateBankAccount($input);
            }

            $fta = (new FundTransferAttempt\Core)->create($this->refund, $fundTransferAttemptInput);

            $refundGateway = Settlement\Channel::getNodalGatewayFromChannel($fta->getChannel());

            $this->refund->setStatus(Payment\Refund\Status::INITIATED);

            $this->refund->setBatchFundTransferId(null);

            $this->refund->setGateway($refundGateway);

            $refunded = true;
        }
        catch (Exception\BaseException $e)
        {
            $this->app['segment']->trackPayment(
                $this->payment, TraceCode::PAYMENT_REFUND_FAILURE);

            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            $this->refund->setStatus(Payment\Refund\Status::FAILED);
        }

        return [
            Payment\Gateway::SUCCESS => $refunded
        ];
    }

    protected function isFundTransferAttemptRefund(Payment\Entity $payment, array $data = []): bool
    {
        // Refund is explicitly being attempted towards a new bank account
        if (isset($data['bank_account']) === true)
        {
            return true;
        }

        // Certain types of payments have refunds routed via bank transfers
        if (($payment->isBankTransfer() === true) or
            ($this->isPaymentEmandateAndEmandateRefundGateway($payment) === true) or
            ($this->isPaymentTpvAndBankTransferRefund($payment) === true))
        {
            return true;
        }

        return false;
    }

    protected function isPaymentEmandateAndEmandateRefundGateway(Payment\Entity $payment): bool
    {
        if (($payment->isEmandate() === true) and
            (in_array($payment->getGateway(), Payment\Gateway::BANK_TRANSFER_REFUND_GATEWAYS, true) === true))
        {
            return true;
        }

        return false;
    }

    protected function isPaymentTpvAndBankTransferRefund(Payment\Entity $payment): bool
    {
        if (($payment->hasOrder() === true) and
            ($payment->isTpvMethod() === true) and
            ($this->merchant->isTPVRequired() === true) and
            ($this->merchant->isFeatureEnabled(Feature::BANK_TRANSFER_REFUND) === true))
        {
            return true;
        }

        return false;
    }

    protected function getBankAccountInput(Payment\Entity $payment, array $data = [])
    {
        $input = [];

        if (isset($data['bank_account']) === true)
        {
            $input = $data['bank_account'];
        }
        else if ($payment->isBankTransfer() === true)
        {
            $paymentId = $payment->getId();

            $haystack = [
                'A0DbFSFMubDEAy',
                'AEYsLhL8DAQAeh',
                'AFG1ItI8zwijGP',
                'AGsXWuKUv6XiVU',
                'AMqpPrSMsxKKPc',
                'AQNG7kHM5tfk4G',
                'ATCKgAcp7cswbo'
            ];

            if (in_array($paymentId, $haystack, true) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                    $input);
            }

            $bankTransfer = $this->repo->bank_transfer->findByPaymentId($paymentId);

            $input = (new BankTransfer\Core)->getAccountForRefund($bankTransfer);
        }
        else if ($this->isPaymentTpvAndBankTransferRefund($payment) === true)
        {
            $order = $payment->order;

            $ifscCode = Bank\BankCodes::getIfscForBankCode($order->getBank());

            $beneficiaryName = $order->getPayerName();

            $input[BankAccount\Entity::IFSC_CODE]          = $ifscCode;
            $input[BankAccount\Entity::ACCOUNT_NUMBER]     = $order->getAccountNumber();
            $input[BankAccount\Entity::BENEFICIARY_NAME]   = ($beneficiaryName === null) ? '' : $beneficiaryName;
        }
        else if ($this->isPaymentEmandateAndEmandateRefundGateway($payment) === true)
        {
            $customer = $payment->customer;
            $customerName = preg_replace('/[^a-zA-Z0-9 ]+/', '', $customer->getName());
            $customerName = substr($customerName, 0, 35);

            $token = $payment->getGlobalOrLocalTokenEntity();

            $input[BankAccount\Entity::IFSC_CODE]          = $token->getIfsc();
            $input[BankAccount\Entity::ACCOUNT_NUMBER]     = $token->getAccountNumber();
            $input[BankAccount\Entity::BENEFICIARY_NAME]   = $customerName;
        }

        if ((isset($input[BankAccount\Entity::IFSC_CODE]) === true) and
            ($input[BankAccount\Entity::IFSC_CODE] === null))
        {
            throw new Exception\BadRequestValidationFailureException(
                'ifsc code is null while creating FTA entity'
            );
        }

        return $input;
    }

    protected function getFundTransferAttemptInput(Payment\Entity $payment): array
    {
        $input = [
            FundTransferAttempt\Entity::NARRATION => null,
            FundTransferAttempt\Entity::MODE      => null,
        ];

        if ($payment->isBankTransfer() === true)
        {
            $bankTransfer = $this->repo->bank_transfer->findByPayment($payment);

            $input = [
                FundTransferAttempt\Entity::NARRATION => $bankTransfer->getRefundNarration(),
                FundTransferAttempt\Entity::MODE      => strtoupper($bankTransfer->getMode()),
            ];
        }

        return $input;
    }

    protected function createAndAssociateBankAccount(array $bankAccountInput)
    {
        $bankAccount = (new BankAccount\Core)->createBankAccountForSource(
                    $bankAccountInput,
                    $this->merchant,
                    $this->refund,
                    BankAccount\Type::REFUND,
                    'addBankTransfer'
                );

        $this->refund->bankAccount()->associate($bankAccount);
    }
}
