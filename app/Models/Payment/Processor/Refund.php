<?php

namespace RZP\Models\Payment\Processor;

use BasicAuth;
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
use RZP\Models\Transaction;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

trait Refund
{
    /**
     * Refunds a payment
     *
     * @param  Payment\Entity   $payment     Payment Id
     * @param  array            $input  Refund input params
     * @param  Batch\Entity     $batch
     *
     * @return Payment\Refund\Entity
     */
    protected function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        $refund = $this->buildRefundEntity($payment, $input, $batch);

        $this->processRefund();

        return $refund;
    }

    public function createRefundOnApiFromRecon(Payment\Entity $payment, string $refundId, int $refundAmount)
    {
        $this->createRefundOnApiSeparately($payment, $refundId, $refundAmount);
    }

    public function createRefundOnApiForCancelledBilldeskRefund(
        Payment\Entity $payment,
        string $refundId,
        int $refundAmount)
    {
        $this->createRefundOnApiSeparately($payment, $refundId, $refundAmount);
    }

    public function verifyRefund(Payment\Refund\Entity $refund)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $gateway = $payment->getGateway();

        Payment\Refund\Validator::validateVerifyRefundAllowed($gateway);

        $data = $this->getGatewayDataForRefund($refund, $payment);

        if ($payment->isMethodCardOrEmi())
        {
            $card = $this->repo->card->fetchForPayment($refund->payment);
            $data['card'] = $card->toArray();
        }

        $msg = $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment, $refund)
        {
            $verify = $this->callGatewayForVerifyRefund($data);

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
        $gateway = $payment->getGateway();

        assert ($refund->getTransactionId() === null);

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
                    'Transaction expected but not present for payment: ' . $payment->getId());
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
        $type = Payment\Refund\Status::PARTIAL;

        if ((isset($input['amount']) === false) or
            ((int) $input['amount'] === $this->payment->getAmountUnrefunded()))
        {
            $type = Payment\Refund\Status::FULL;
        }

        return $type;
    }

    protected function callGatewayForVerifyRefund($data)
    {
        $verifyRefundResult = null;

        try
        {
            $verifyRefundResult = $this->callGatewayFunction(Payment\Action::VERIFY_REFUND, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_VERIFY_REFUND_FAILURE);

            throw $e;
        }

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
        $gateway = $data['payment']['gateway'];

        try
        {
            // This has already been refunded on Billdesk.
            // We'll run create record later after this is refunded.
            $paymentId = $data['payment']['id'];
            $refAmount = $data['amount'];

            if (($paymentId === '6pHu2RnPzTeI51') and ($refAmount === 784000))
            {
                return;
            }

            $this->callGatewayFunction(Payment\Action::REFUND, $data);
        }
        catch (Exception\GatewayTimeoutException $ex)
        {
            //
            // Currently, we are running this experiment only for Billdesk.
            // Billdesk gives us a way to find out how much amount has been refunded.
            // We are not aware of any other gateway which
            // provides us this feature, currently.
            //

            if (in_array($gateway, Payment\Gateway::REFUND_TIMEOUT_HANDLED_GATEWAYS, true) === false)
            {
                throw $ex;
            }

            $this->trace->traceException($ex);

            //
            // We just ignore the timeout and mark it as refunded on the api side.
            // Later we would run verify for these refunds and
            // create appropriate entries on the gateway side.
            //

            $this->trace->info(
                TraceCode::PAYMENT_REFUND_TIMEOUT_SKIP,
                ['payment_id' => $this->payment->getId()]);
        }
        catch (Exception\BaseException $e)
        {
            $this->app['segment']->trackPayment(
                $this->payment, TraceCode::PAYMENT_REFUND_FAILURE);

            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REFUND_FAILURE);

            throw $e;
        }
    }

    protected function reverseOnGateway($data)
    {
        try
        {
            $this->callGatewayFunction(Payment\Action::REVERSE, $data);
        }
        catch (Exception\BaseException $e)
        {
            $this->app['segment']->trackPayment(
                $this->payment, TraceCode::PAYMENT_REVERSE_FAILURE);

            $this->tracePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_REVERSE_FAILURE);
        }
    }

    protected function recordTransactionForRefund()
    {
        try
        {
            return $this->repo->transaction(
                function()
                {
                    $payment = $this->payment;

                    $this->paymentRepo->lockForUpdate($payment->getKey());

                    $this->createTransactionForRefund($this->refund, $payment);

                    //
                    // This needs to be saved here because of the association with
                    // transaction which is set in the createTransactionForRefund function.
                    //
                    $this->repo->saveOrFail($this->refund);

                    return true;
                });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::REFUND_TRANSACTION_FAILED,
                [
                    'payment_id'    => $this->payment->getId(),
                    'refund_id'     => $this->refund->getId(),
                    'error_message' => $ex->getMessage(),
                ]);

            return false;
        }
    }

    protected function recordTransactionAndUpdatePaymentForRefund($forceRefundTransaction = false)
    {
        $this->repo->transaction(function() use ($forceRefundTransaction)
        {
            $payment = $this->payment;

            $this->paymentRepo->lockForUpdate($payment->getKey());

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
            $this->validateMerchantBalance($refund);
        }

        $this->refund = $refund;

        $refund->batch()->associate($batch);

        return $refund;
    }

    protected function processRefund()
    {
        $payment = $this->refund->payment;

        $data = $this->getGatewayDataForRefund($this->refund, $payment);

        if ($payment->isMethodCardOrEmi() === true)
        {
            $card = $this->repo->card->fetchForPayment($this->refund->payment);

            $data['card'] = $card->toArray();
        }

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment)
        {
            if ($payment->getTransactionId() !== null)
            {
                $this->refundOnGateway($data);

                $this->refund->setGatewayRefunded(true);
            }
            else if ($this->gatewaySupportsReversal($payment) === true)
            {
                $this->reverseOnGateway($data);

                // TODO: Record this too.
            }

            $refundCopy = clone $this->refund;

            //
            // NOTE: We should create the transaction before we update
            // the payment as refunded since there is different logic
            // for creating a refund transaction based on the payment status.
            //
            $success = $this->recordTransactionForRefund();

            //
            // `recordTransactionForRefund()` may have made modifications to the refund
            // entity which we don't want to update, since recording transaction failed.
            //
            if ($success === false)
            {
                $this->refund = $refundCopy;
            }

            // Record refund since it's refunded on gateway
            $this->updatePaymentRefunded();

            $this->sendRefundNotification($payment);
        });

        return $this->refund;
    }

    protected function gatewaySupportsReversal($payment)
    {
        $gateway = $payment->getGateway();

        return Payment\Gateway::supportsReverse($gateway);
    }

    protected function updatePaymentRefunded()
    {
        // Indicates buggy case where refund entity is already present
        if ($this->verifyRefundStatus === false)
        {
            ; // No action required here.
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

    protected function validateMerchantBalance($refund)
    {
        $merchant = $refund->merchant;

        $balance = (new Merchant\Balance\Repository)->getMerchantBalance($merchant);

        if ($balance->getBalance() < $refund->getBaseAmount())
        {
            $traceMessage = [
                'message' => 'Not enough balance',
                'merchant_balance' => $balance->getBalance(),
                'refund_amount' => $refund->getBaseAmount()
            ];

            $this->trace->info(TraceCode::PAYMENT_REFUND_FAILURE, $traceMessage);

            $this->app['segment']->trackPayment($refund->payment, TraceCode::PAYMENT_REFUND_FAILURE, $traceMessage);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE);
        }
    }

    protected function getGatewayDataForRefund(Payment\Refund\Entity $refund, Payment\Entity $payment)
    {
        $data = [
            'payment'   => $payment->toArrayGateway(),
            'refund'    => $refund->toArrayGateway(),
            'amount'    => $refund->getAmount(),
            'currency'  => $refund->getCurrency()
        ];

        if ($payment->getConvertCurrency())
        {
            $data['amount'] = $refund->getBaseAmount();

            $data['currency'] = Currency\Currency::INR;
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
        $notifier->trigger(Notify::REFUNDED);

        $this->notifyDashboard('refund', $this->refund);
    }

    protected function createRefundOnApiSeparately(Payment\Entity $payment, string $refundId, int $refundAmount)
    {
        if ($payment->transaction === null)
        {
            throw new Exception\LogicException(
                'Transaction expected but not present for payment: ' . $payment->getId());
        }

        $input = ['amount' => $refundAmount];

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
}
