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
use RZP\Models\Transfer;
use RZP\Models\ReverseTransfer;
use RZP\Trace\TraceCode;
use RZP\Models\Feature\Constants as Feature;

trait Refund
{
    /**
     * Determine if the refund request should handled
     * Marketplace reversals
     *
     * @var boolean
     */
    protected $processReversals = false;

    /**
     * Refunds a payment
     * @param  Payment\Entity   $payment     Payment Id
     * @param  array            $input  Refund input params
     * @param  Batch\Entity     $batch
     *
     * @return Payment\Refund\Entity
     */
    protected function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null, bool $processReversals = false)
    {
        $refund = $this->buildRefundEntity($payment, $input, $batch);

        $this->processRefund($refund, $input, $processReversals);

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
            $data['card'] = $refund->payment->card->toArray();
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

        // Currently doing it for only HDFC and Billdesk. In case when other gateways start
        // getting similar issues, we will start supporting for them too.
        assert (($payment->getGateway() === Payment\Gateway::HDFC) or
                ($payment->getGateway() === Payment\Gateway::BILLDESK));

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

        $processReversals = $this->shouldRefundWithTransfers($payment, $input);

        return $this->refundCapturedPayment($payment, $input, null, $processReversals);
    }

    /**
     * Check if the refund should happen along with Marketplace transfers/reversals
     *
     * @todo CLEAN UP
     *
     * @param  Payment\Entity   $payment
     * @param  array            $input
     * @return bool
     */
    protected function shouldRefundWithTransfers(Payment\Entity $payment, array & $input) : bool
    {
        $hasTransfers = ($payment->getAmountTransferred() > 0);

        if (($hasTransfers === false) or
            ($payment->isTransfer() === true) or
            ($payment->getRefundStatus() === Payment\Refund\Status::FULL))
        {
            return false;
        }

        $transfers = null;

        list($reverseAllTransfers, $reversalsReqd) = $this->checkReversalsOnRefundType(
                                                                $payment,
                                                                $input,
                                                                $transfers);

        if (isset($input['transfers']) === false)
        {
            if ($reversalsReqd === true)
            {
                throw new Exception\BadRequestException('transfers input required');
            }

            if ($reverseAllTransfers === true)
            {
                $this->implicitAddReversalsForFullRefund($transfers, $payment, $input);
            }
        }

        return true;
    }

    protected function checkReversalsOnRefundType($payment, $input, & $transfers)
    {
        $refundType = $this->getPaymentRefundType($payment, $input);

        $reverseAllTransfers = false;

        $reversalsReqd = false;

        if ($refundType === Payment\Refund\Status::FULL)
        {
            $reverseAllTransfers = true;
        }
        else if ($refundType === Payment\Refund\Status::PARTIAL)
        {
            $transfers = $this->repo
                              ->transfer
                              ->fetchBySourcePaymentIdAndMerchant($payment->getId(), $this->merchant);

            assert (count($transfers) !== 0);

            if (count($transfers) > 1)
            {
                $reversalsReqd = true;
            }
        }

        return [$reverseAllTransfers, $reversalsReqd];
    }

    /**
     * For a full-refund, fetch and implicitly add reversals
     *
     * @param  [type] $transfers [description]
     * @param  [type] $payment   [description]
     * @param  [type] $input     [description]
     * @return [type]            [description]
     */
    protected function implicitAddReversalsForFullRefund($transfers, $payment, array & $input)
    {
        if ($transfers === null)
        {
            $transfers = $this->repo
                              ->transfer
                              ->fetchBySourcePaymentIdAndMerchant($payment->getId(), $this->merchant);
        }

        $reversals = [];

        foreach ($transfers as $transfer)
        {
            $reversals[] = [
                'transfer'  => $transfer->getPublicId(),
                'amount'    => $transfer->getAmountUnreversed()
            ];
        }

        $input['transfers'] = $reversals;
    }

    /**
     * Refund a payment with Marketplace transfers
     *
     * @param  string $paymentId
     * @param  array  $input
     * @return null
     */
    public function refundPaymentWithTransfers(Payment\Entity $payment, array $input)
    {
        (new Payment\Refund\Validator)->validateTransfersRequired($input);

        // Refund and reverse_transfer each split-payment
        foreach ($input['transfers'] as $transfer)
        {
            $this->refundAndReverseTransferPayment($payment, $transfer['transfer'], $transfer['amount']);
        }
    }

    /**
     * Refund the split payment and create a reverse_transfer for the
     * original payment transfer
     *
     * @param  Payment\Entity $payment
     * @param  string         $accountId
     * @param  int            $amount
     * @return void
     */
    protected function refundAndReverseTransferPayment(Payment\Entity $payment, string $transferId, int $amount)
    {
        $transfer = $this->repo
                         ->transfer
                         ->findByPublicIdAndMerchant($transferId, $this->merchant);

        $accountId = $transfer->getToId();

        $splitPayment = $this->repo
                             ->payment
                             ->fetchSplitPaymentByOriginPaymentId(
                                $payment->getId(), $accountId);

        // @todo: DB queried here
        assert ($this->merchant->accounts->contains($accountId));

        $refund = $this->refundTransferPayment($splitPayment, $amount);

        $reverseTrf = (new ReverseTransfer\Core)->createForMarketplaceRefund($transfer, $this->merchant, $amount);
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
                ]
            );

            return $txn;
        }

        return null;
    }

    protected function getPaymentRefundType(Payment\Entity $payment, array $input)
    {
        if (isset($input['amount']) === false)
        {
            $type = Payment\Refund\Status::FULL;
        }
        else if ((int) $input['amount'] === $payment->getAmountUnrefunded())
        {
            $type = Payment\Refund\Status::FULL;
        }
        else
        {
            $type = Payment\Refund\Status::PARTIAL;
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

    protected function refundOnGateway($data)
    {
        try
        {
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

            if ($this->payment->getGateway() !== Payment\Gateway::BILLDESK)
            {
                throw $ex;
            }

            $curlMessage = strtolower($ex->getData()['message']);

            //
            // GatewayTimeoutException is thrown for various reasons (`checkTimeout`).
            // We want to mark the refund as successful only if the error
            // message says that the operation timed out.
            //

            if (strpos($curlMessage, 'operation timed out') === false)
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
            $this->repo->transaction(
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
        }
    }

    protected function recordTransactionAndUpdatePaymentForRefund($forceRefundTransaction = false)
    {
        $this->repo->transaction(function() use ($forceRefundTransaction, $input, $processReversals)
        {
            $payment = $this->payment;

            $this->paymentRepo->lockForUpdate($payment->getKey());

            if ($processReversals === true)
            {
                $parentRefund = $this->refund;
                $parentPayment = $this->payment;

                $this->refundPaymentWithTransfers($payment, $input);

                $this->refund = $parentRefund;
                $this->payment = $parentPayment;
            }

            $this->createTransactionForRefund($this->refund, $payment, $forceRefundTransaction);

            $this->updatePaymentRefunded();
        });
    }

    protected function buildRefundEntity(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            [
                'payment_id' => $payment->getId(),
                'input' => $input
            ]);

        $this->setPayment($payment);

        $refund = (new Payment\Refund\Entity)->build($input, $payment);

        $refund->merchant()->associate($payment->merchant);

        $refund->setBaseAmount();

        if ($this->payment->isCaptured())
        {
            $this->validateMerchantBalance($refund);
        }

        $this->refund = $refund;

        $refund->batch()->associate($batch);

        return $refund;
    }

    protected function processRefund(Payment\Refund\Entity $refund, array $input, bool $processReversals = false)
    {
        $payment = $refund->payment;

        $data = $this->getGatewayDataForRefund($refund, $payment);

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $refund->payment->card->toArray();
        }

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($data, $payment, $refund, $input, $processReversals)
        {
            if ($payment->isTransfer())
            {
                ; // Marketplace: do nothing, Transfer refunds are internal
            }
            else if ($payment->getTransactionId() !== null)
            {
                $this->refundOnGateway($data);

                $refund->setGatewayRefunded(true);
            }
            else if ($this->gatewaySupportsReversal($payment) === true)
            {
                $this->reverseOnGateway($data);

                // TODO: Record this too.
            }

            //
            // NOTE: We should create the transaction before we update
            // the payment as refunded since there is different logic
            // for creating a refund transaction based on the payment status.
            //
            $this->recordTransactionForRefund();

            // Record refund since it's refunded on gateway
            $this->updatePaymentRefunded();

            $this->sendRefundNotification($payment, $refund);
        });

        return $refund;
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

        return $this->refund($payment, $input, $batch);
    }

    /**
     * Refund a marketplace payment of method = transfer
     *
     * @param  Payment\Entity $payment
     * @param  int            $amount
     */
    protected function refundTransferPayment(Payment\Entity $payment, int $amount)
    {
        if ($payment->isTransfer() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_TRANSFER);
        }

        $this->validatePaymentForRefund($payment);

        $input['amount'] = $amount;

        return $this->refund($payment, $input);
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
     * @param  Payment\Entity        $payment Payment Entity
     * @param  Payment\Refund\Entity $refund  Refund Entity
     * @return null
     */
    protected function sendRefundNotification(Payment\Entity $payment, Payment\Refund\Entity $refund)
    {
        //
        // Analytics is on dashboard side for now
        //
        $notifier = new Notify($payment);
        $notifier->addRefund($refund);
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
}
