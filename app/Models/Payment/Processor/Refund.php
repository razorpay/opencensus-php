<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use Request;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Hdfc;
use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Card;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\BankTransfer;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

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
        if ($payment->isDisputed() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED,
                null,
                ['input' => $input, 'payment_id' => $payment->getId()]);
        }

        $refund = $this->buildRefundEntity($payment, $input, $batch);

        $this->processRefund();

        return $refund;
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

                $this->recordTransactionAndUpdatePaymentForRefund(true);

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

    public function manualGatewayRefund(Payment\Refund\Entity $refund)
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
     * @param Payment\Refund\Entity $refund
     *
     * @return bool
     * @throws Exception\BadRequestException
     */
    public function verifyRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $gateway = $payment->getGateway();

        Payment\Refund\Validator::validateVerifyRefundAllowed($gateway);

        $data = $this->getGatewayDataForRefund($refund, $payment);

        $verifyRefundResult = $this->callGatewayForVerifyRefund($data);

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

        return $this->refundCapturedPayment($payment, $input);
    }

    /**
     * Process refund on a payment that has Marketplace transfers
     *
     * @param array $input
     *
     * @throws Exception\BadRequestValidationFailureException
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

    public function refundPaymentViaBatchEntry(Payment\Entity $payment, Batch\Entity $batch, $amount)
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

        $input = ['amount' => (string) $amount];

        // No refund existed so fire a new one.
        return $this->refundCapturedPayment($payment, $input, $batch);
    }

    /**
     * @param Payment\Refund\Entity $refund
     * @param Payment\Entity $payment
     * @param bool $forceRefundTransaction This param is used for when we don't want to check for captured payment
     *                                      for authAndCapture supported gateways before creating a refund transaction
     *
     * @return null|Transaction\Entity
     * @throws Exception\LogicException
     */
    public function createTransactionForRefund(
        Payment\Refund\Entity $refund, Payment\Entity $payment, $forceRefundTransaction = false)
    {
        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_REQUEST,
            [
                'refund_id'     => $refund->getId(),
                'payment_id'    => $payment->getId(),
                'force'         => $forceRefundTransaction
            ]);

        $gateway = $payment->getGateway();

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

        // For authAndCapture supported gateways, payment transaction is created only after capture.
        // For gateways which don't support authAndCapture, payment transaction is created after authorization.
        // There are a few gateways which are partially authAndCapture gateways. This means that for
        // some networks, payment transaction is created at authorization and for some networks, payment
        // transaction is created at capture.

        // Hence, for [notAuthAndCapture] and [notAuthAndCaptureForSpecificNetworks] gateways,
        // we do not check for the capture timestamp.
        // For [authAndCapture] gateways, we check for capture timestamp.

        $networkCode = null;
        $paymentCard = $payment->card;

        // If payment method is wallet or net banking.
        if ($paymentCard !== null)
        {
            $networkCode = $paymentCard->getNetworkCode();
        }

        $supportsAuthAndCapture = Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode);

        $gatewayRefunded = $refund->isGatewayRefunded();

        if ((($supportsAuthAndCapture === true) and ($payment->getCaptureTimestamp() !== null)) or
            ($supportsAuthAndCapture === false) or ($gatewayRefunded === true) or
            ($forceRefundTransaction === true))
        {
            if ($payment->transaction === null)
            {
                throw new Exception\LogicException(
                    'Transaction expected but not present for payment',
                    null,
                    [
                        'payment_id'        => $payment->getId(),
                        'auth_capture'      => $supportsAuthAndCapture,
                        'force_refund_txn'  => $forceRefundTransaction,
                        'gateway_refunded'  => $gatewayRefunded,
                    ]);
            }

            $txn = (new Transaction\Core)->createFromRefund($refund);

            $this->repo->saveOrFail($txn);

            $this->trace->info(
                TraceCode::REFUND_TRANSACTION_CREATED,
                [
                    'payment_id'        => $payment->getId(),
                    'refund_id'         => $refund->getId(),
                    'transaction_id'    => $txn->getId(),
                    'auth_capture'      => $supportsAuthAndCapture,
                    'force_refund_txn'  => $forceRefundTransaction,
                ]);

            return $txn;
        }

        return null;
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
     * @return bool
     * @throws Exception\LogicException
     */
    protected function callGatewayForVerifyRefund($data)
    {
        $verifyRefundResult = $this->callGatewayFunction(Payment\Action::VERIFY_REFUND, $data);

        return $verifyRefundResult;
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

    protected function refundOnGateway($data)
    {
        $gatewayRefunded = false;

        try
        {
            // This has already been refunded on Billdesk.
            // We'll run create record later after this is refunded.
            $paymentId = $data['payment']['id'];
            $refAmount = $data['amount'];

            if (($paymentId === '6pHu2RnPzTeI51') and ($refAmount === 784000))
            {
                return true;
            }

            // HDFC refund which got timed out on HDFC end, but was successful.
            if (($paymentId === '7V6tmkxLdC4xyd') and ($refAmount === 18500))
            {
                return true;
            }

            $this->callGatewayFunction(Payment\Action::REFUND, $data);

            $this->refund->setStatus(Payment\Refund\Status::PROCESSED);

            $gatewayRefunded = true;
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

        return $gatewayRefunded;
    }

    protected function reverseOnGateway($data)
    {
        $reversed = false;

        try
        {
            $this->callGatewayFunction(Payment\Action::REVERSE, $data);

            $this->refund->setStatus(Payment\Refund\Status::PROCESSED);

            $reversed = true;
        }
        catch (Exception\BaseException $e)
        {
            $this->app['segment']->trackPayment(
                $this->payment, TraceCode::PAYMENT_REVERSE_FAILURE);

            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REVERSE_FAILURE);

            $this->refund->setStatus(Payment\Refund\Status::FAILED);
        }

        return $reversed;
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

    protected function recordTransactionAndUpdatePaymentForRefund($forceRefundTransaction = false)
    {
        $this->repo->transaction(function() use ($forceRefundTransaction)
        {
            $payment = $this->payment;

            $this->repo->payment->lockForUpdate($payment->getKey());

            $this->createTransactionForRefund($this->refund, $payment, $forceRefundTransaction);

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

            $refunded = $this->callRefundFunction($payment, $data);

            $this->refund->setGatewayRefunded($refunded);

            $this->refund->incrementAttempts();

            // We don't want the transaction to fail if this
            // save fails that's why keeping it outside.
            $this->repo->saveOrFail($this->refund);

            // send notification to merchant/customer, this is outside transaction
            // as we dont want to to reverse the actions if mail sending fails
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

    protected function callRefundFunction($payment, $data)
    {
        if ($this->shouldHitGateway($payment) === true)
        {
            return $this->callGatewayRefundFunction($payment, $data);
        }
        else if ($payment->isBankTransfer() === true)
        {
            return $this->refundBankTransfer($payment, $data);
        }
        else
        {
            throw new Exception\LogicException(
                'Should not have reached here',
                null,
                [
                    'payment_id'    => $payment->getId(),
                ]);
        }
    }

    protected function callGatewayRefundFunction($payment, $data)
    {
        $refunded = false;

        // refund/reverse on gateway
        if (($payment->getTransactionId() !== null) or
            ($payment->isGatewayCaptured() === true))
        {
            $refunded = $this->refundOnGateway($data);
        }
        else if ($this->gatewaySupportsReversal($payment) === true)
        {
            $refunded = $this->reverseOnGateway($data);
        }

        return $refunded;
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
     *
     * @return string
     */
    public function processRefundRetry(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $data = $this->getGatewayDataForRefund($refund, $payment);

        if ($refund->isProcessed() === true)
        {
            return Payment\Refund\Status::PROCESSED;
        }

        // true  if refunded
        // false if not refunded
        $refundedOnGateway = $this->verifyRefund($refund);

        if ($refundedOnGateway === false)
        {
            $refundedOnGateway = $this->mutex->acquireAndRelease(
                $payment->getId(),
                function() use ($data, $payment)
                {
                    return $this->callGatewayRefundFunction($payment, $data);
                });
        }
        else
        {
            $this->refund->setStatus(Payment\Refund\Status::PROCESSED);
        }

        $this->refund->setGatewayRefunded($refundedOnGateway);

        $this->refund->incrementAttempts();

        $this->repo->saveOrFail($this->refund);

        return $this->refund->getStatus();
    }

    protected function gatewaySupportsReversal($payment)
    {
        $gateway = $payment->getGateway();

        return Payment\Gateway::supportsReverse($gateway);
    }

    protected function updatePaymentRefunded()
    {
        //
        // Indicates inverse of buggy case where
        // refund entity is already present
        // Need to check against false only, since
        // it can be `null` also. In case of `null`
        // or `true`, it should go to the else block.
        //
        if ($this->verifyRefundStatus === false)
        {
            ;
        }
        else
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

        $this->tracePaymentInfo(TraceCode::PAYMENT_REFUND_SUCCESS);

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

        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

        if ($balance->getBalance() < $refund->getBaseAmount())
        {
            $traceMessage = [
                'type'              => $type,
                'message'           => 'Not enough balance',
                'merchant_balance'  => $balance->getBalance(),
                'refund_amount'     => $refund->getBaseAmount(),
                'refund_id'         => $refund->getId(),
            ];

            if ($type === 'refund')
            {
                $this->app['segment']->trackPayment(
                    $refund->payment,
                    TraceCode::PAYMENT_REFUND_FAILURE,
                    $traceMessage);

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
                    $traceMessage
                );
            }

            throw new Exception\BadRequestException($error, null, $traceMessage);
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

    protected function refundCapturedPayment($payment, array $input = [], Batch\Entity $batch = null)
    {
        $this->validatePaymentForRefund($payment);

        // Captured payments of transfer cannot be refunded via direct API requests
        if (($payment->isTransfer() === true) or 
            ($payment->isEmandate() === true))
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
     *
     * @return null
     */
    protected function sendRefundNotification(Payment\Entity $payment)
    {
        //
        // Analytics is on dashboard side for now
        //
        $notifier = new Notify($payment);
        $notifier->addRefund($this->refund);
        $notifier->trigger(Payment\Event::REFUNDED);

        $this->notifyDashboard('refund', $this->refund);
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

    protected function refundBankTransfer(Payment\Entity $payment, array $data)
    {
        $refunded = false;

        try
        {
            (new BankTransfer\Core)->refund($data);

            $this->refund->setStatus(Payment\Refund\Status::CREATED);

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

        return $refunded;
    }
}
