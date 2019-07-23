<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Currency;
use RZP\Trace\TraceCode;
use RZP\Models\Vpa\Core;
use RZP\Error\ErrorCode;
use RZP\Models\Card\Type;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Jobs\ScroogeRefund;
use RZP\Models\BankTransfer;
use RZP\Models\FundTransfer;
use RZP\Models\Card\IIN\IIN;
use RZP\Jobs\ScroogeRefundRetry;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RefundSource;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Models\Payment\Refund\Validator;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Metric as RefundMetric;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

/**
 * Trait Refund
 *
 * @package RZP\Models\Payment\Processor
 *
 * @property RefundEntity    $refund
 * @property Merchant\Entity $merchant
 */
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
    public function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null)
    {
        if ($this->isInvalidInstantRefundsRequest($payment, $input) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INSTANT_REFUND_NOT_SUPPORTED);
        }

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

    protected function isInvalidInstantRefundsRequest(Payment\Entity $payment, array $input)
    {
        return ((isset($input[RefundEntity::SPEED]) === true) and
                (in_array($input[RefundEntity::SPEED], RefundSpeed::REFUND_INSTANT_SPEEDS) === true) and
                (($payment->getMethod() !== Payment\Method::CARD) or
                 ($this->payment->isCaptured() === false) or
                 ($this->merchant->isFeatureEnabled(Feature::CARD_TRANSFER_REFUND) === false)));
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

        $input[RefundConstants::IS_FTA] = (isset($input[RefundConstants::IS_FTA]) === true) ?
            (bool) $input[RefundConstants::IS_FTA] : null;

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

            $scroogeResponse = $this->callRefundFunctionForScroogeWithData($refund, $input);
        }
        catch (\Exception $ex)
        {
            $gatewayRefunded = false;

            $gatewayResponse = [];

            // Only BaseException would have `getData` function
            if ($ex instanceof Exception\BaseException)
            {
                $gatewayResponse = $ex->getData();
            }

            $scroogeResponse = $this->prepareScroogeRefundResponse($gatewayResponse, $gatewayRefunded, $ex);
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

        $data = $this->getScroogeRefundCallData($refund, $payment, $input);

        $gatewayRefundResponse = $this->mutex->acquireAndRelease(
            $payment->getId(),
            function () use ($data, $payment, $refund)
            {
                return $this->callRefundFunction($refund, $payment, $data);
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

    /**
     * This returns the data required to perform refund via fta or gateway
     *
     * @param RefundEntity $refund
     * @param $payment
     * @param array $input
     * @return array
     */
    protected function getScroogeRefundCallData(RefundEntity $refund, $payment, array $input)
    {
        $data = $this->getGatewayDataForRefund($refund, $payment);

        //
        // This is required for upi mindgate refunds. Second request on gateway with same refund id fails with duplicate.
        // Attempts will come from scrooge but still handling here to keep default value 0. Can't use API's attempts as
        // for scrooge refunds API attempts will always be 1
        //
        $input['attempts'] = $input['attempts'] ?? 0;

        $data['refund']['attempts'] = $input['attempts'];

        $data[RefundConstants::IS_FTA] = $input[RefundConstants::IS_FTA] ?? null;

        if (isset($input['fta_data']) === true)
        {
            $data = array_merge($data, $input['fta_data']);
        }

        return $data;
    }

    public function scroogeGatewayVerifyRefund(RefundEntity $refund, array $input)
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
                [Payment\Gateway::GATEWAY_VERIFY_RESPONSE => 'Refund has already been processed'],
                true);
        }

        $input[RefundConstants::IS_FTA] = (isset($input[RefundConstants::IS_FTA]) === true) ?
            (bool) $input[RefundConstants::IS_FTA] : null;

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

            $refundValidator->validateInput('scrooge_gateway_refund', $input);

            //
            // Doing +1 here, because at gateway side, we decrement attempts with -1,
            // doing this to keep backward compatibility of older refunds as well as scrooge refunds.
            // For scrooge refunds, attempts will be the exact attempt on which verify should be called,
            // and for old refunds, it will be the refund attempt, so we need to verify on previous refund
            //
            $refund->setAttempts(($input['attempts'] ?? -1) + 1) ;

            $data = $input['fta_data'] ?? [];

            $data[RefundConstants::IS_FTA] = $input[RefundConstants::IS_FTA] ?? null;

            $gatewayVerifyRefundResponse = $this->verifyRefund($refund, $data);
        }
        catch (\Exception $ex)
        {
            $gatewayResponse = [];

            // Only BaseException would have `getData` function
            if ($ex instanceof Exception\BaseException)
            {
                $gatewayResponse = $ex->getData();
            }

            $gatewayVerifyRefundResponse = $this->prepareScroogeRefundResponse($gatewayResponse, false, $ex, Payment\Action::VERIFY);
        }

        $this->traceScroogeResponse(TraceCode::REFUND_SCROOGE_VERIFY_RESPONSE,
                                    $refund,
                                    $gatewayVerifyRefundResponse);

        return $gatewayVerifyRefundResponse;
    }

    /**
     * Using to verify UPI refunds for all previous attempts to check if any of the attempt was successful.
     * bulkRefundVerify param is used for returning response in the required format for `verifyRefundsInBulk` function.
     *
     * @param $refund
     * @param int $attempts
     * @param bool $bulkRefundVerify
     * @return array (1D / 2D)
     */
    public function verifyScroogeRefundWithAttempts($refund, int $attempts, $bulkRefundVerify = false)
    {
        $fileData = [];

        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

        $successCount = $refundFailedCount = $failureCount = $totalCount = 0;

        $successAttempt = [];

        for ($attempt = 1; $attempt <= $attempts; $attempt++)
        {
            $totalCount += 1;

            try
            {
                $refund->setAttempts($attempt);

                $verifyResponse = $this->verifyRefund($refund);

                $success = $verifyResponse[Payment\Gateway::SUCCESS];

                $this->trace->info(
                    TraceCode::SCROOGE_VERIFY_REFUND_CRON_RESPONSE,
                    [
                        'refund_id'         => $refund->getId(),
                        'attempt_number'    => $attempt,
                        'success'           => $success,
                        'payment_id'        => $payment->getId(),
                        'verify_response'   => $verifyResponse,
                    ]);

                if ($bulkRefundVerify === true)
                {
                    $fileData[] = [
                        'refund_id'         => $refund->getId(),
                        'attempt_number'    => $attempt,
                        'success'           => ($success) ? 'true' : 'false',
                        'payment_id'        => $payment->getId(),
                        'verify_response'   => json_encode($verifyResponse)
                    ];
                }

                ($success === true) ? ($successCount += 1 and $successAttempt[] = $attempt) : $refundFailedCount += 1;

                if (($success === true) and ($refund->getAmount() === $payment->getAmount()))
                {
                    break;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::SCROOGE_VERIFY_REFUND_CRON_EXCEPTION,
                    [
                        'refund_id'         => $refund->getId(),
                        'attempt_number'    => $attempt,
                        'payment_id'        => $payment->getId(),
                        'exception'         => $ex->getMessage(),
                    ]);

                if ($bulkRefundVerify === true)
                {
                    $fileData[] = [
                        'refund_id'         => $refund->getId(),
                        'attempt_number'    => $attempt,
                        'success'           => "Unexpected Failure",
                        'payment_id'        => $payment->getId(),
                        'verify_response'   => $ex->getMessage()
                    ];
                }

                $failureCount += 1;
            }
        }

        if ($bulkRefundVerify === true)
        {
            return $fileData;
        }

        return [
            'refund_id'             => $refund->getId(),
            'success_count'         => $successCount,
            'success_attempt'       => $successAttempt,
            'refund_failed_count'   => $refundFailedCount,
            'failure_count'         => $failureCount,
            'total_count'           => $totalCount
        ];
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
        assertTrue ($refund->getTransactionId() !== null);

        // Just making sure that the payment also has the transaction id. Refund will not have a transaction
        // if payment does not have a transaction, anyway.
        assertTrue ($payment->getTransactionId() !== null);

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
     * @param array $ftaInput
     * @return array
     * @throws Exception\BadRequestException
     */
    public function verifyRefund(Payment\Refund\Entity $refund, array $ftaInput = [])
    {
        $payment = $refund->payment;

        if ($this->isFundTransferAttemptRefund($refund, $payment, $ftaInput) === true)
        {
            $verifyRefundResult = $this->prepareScroogeRefundResponse([],
                                                                      false,
                                                                      null,
                                                                      Payment\Action::VERIFY,
                                                                      ErrorCode::REFUND_FTA_MANUALLY_CONFIRMED_UNPROCESSED);

        }
        else
        {
            if ((isset($ftaInput[RefundConstants::IS_FTA]) === true) and ($ftaInput[RefundConstants::IS_FTA] === true))
            {
                $verifyRefundResult = $this->prepareScroogeRefundResponse(
                    [
                        Payment\Gateway::GATEWAY_VERIFY_RESPONSE =>
                        'Instant refund request failed because of insufficient data'
                    ],
                    false,
                    null,
                    Payment\Action::VERIFY,
                    ErrorCode::BAD_REQUEST_INSUFFICIENT_DATA_FOR_FTA
                );

                return $verifyRefundResult;
            }

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
        assertTrue ($refund->getTransactionId() !== null);

        // Just making sure that the payment also has the transaction id. Refund will not have a transaction
        // if payment does not have a transaction, anyway.
        assertTrue ($payment->getTransactionId() !== null);

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
        /** @var Payment\Entity $payment */
        $payment = $this->retrieve($paymentId);

        // From subscription service we will always refund authorized payments
        if ($this->ba->isSubscriptionsApp() === true)
        {
            return $this->refundAuthorizedPayment($payment, $input);
        }

        //
        // The following checks are here since only Merchant initiated refunds hit this function.
        // Downstream functions such as `refundCapturePayment` are used by other cases where we will
        // actually need to refund captured payment always: like payment pages, or virtual accounts
        //
        if ($this->merchant->isFeatureEnabled(Feature::DISABLE_REFUNDS) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_REFUND_NOT_ALLOWED);
        }

        if (($this->merchant->isFeatureEnabled(Feature::DISABLE_CARD_REFUNDS) === true) and
            ($payment->getMethod() === Payment\Method::CARD))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CARD_REFUND_NOT_ALLOWED);
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

        $txnCore = new Transaction\Core;

        list($txn, $feesSplit) = $txnCore->createFromRefund($refund);

        $this->repo->saveOrFail($txn);

        $txnCore->saveFeeDetails($txn, $feesSplit);

        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATED,
            [
                'payment_id'        => $payment->getId(),
                'refund_id'         => $refund->getId(),
                'transaction_id'    => $txn->getId(),
            ]);

        return $txn;
    }

    public function reverseRefund(Payment\Refund\Entity $refund, bool $feeOnlyReversal = false)
    {
        $this->trace->info(
            TraceCode::REFUND_REVERSAL_INITIATED,
            [
                'refund_id'  => $refund->getId(),
                'payment_id' => $refund->getPaymentId(),
                'gateway'    => $refund->getGateway()
            ]);

        //
        // To ensure that refund forward transaction has this amount / fees debited, if debit is 0, it
        // could be a Direct Settlement just an authorized transaction refund - for which we have handled before this,
        // Todo: the third case is Refund Credits - which needs to be handled soon
        //
        if (($refund->payment->hasBeenCaptured() === false) or
            (($refund->transaction->getDebit() === 0) and
             ($refund->transaction->getCreditType() === Transaction\CreditType::DEFAULT)))
        {
            return null;
        }

        if (($refund->isStatusReversed() === true) or
            (($feeOnlyReversal === true) and ($refund->getFee() === 0)))
        {
            throw new Exception\LogicException(
                'Attempted to reverse an already reversed refund amount/fee',
                null,
                [
                    'refund_id'  => $refund->getId(),
                    'status'     => $refund->getStatus(),
                    'payment_id' => $refund->getPaymentId(),
                    'gateway'    => $refund->getGateway(),
                    'fee'        => $refund->getFee(),
                ]);
        }

        try
        {
            $reversal = $this->repo->transaction(
                function () use ($refund, $feeOnlyReversal) {
                    $reversal = (new Reversal\Core)->reverseForRefund($refund, $feeOnlyReversal);

                    $fee = $refund->getFees();

                    $tax = $refund->getTax();

                    $refund->setFee(0);

                    $refund->setTax(0);

                    //
                    // [Instant Refunds] - optimum flow
                    // In case of direct settlement refunds we are creating a reversal transaction -
                    // to reverse the fees and amount, since gateway will settle the amount directly
                    //
                    if (($feeOnlyReversal === false) and
                        ($refund->isDirectSettlementRefund() === false))
                    {
                        $refund->setStatus(Payment\Refund\Status::REVERSED);
                    }

                    $this->repo->saveOrFail($refund);

                    $this->trace->info(
                        TraceCode::REFUND_FEE_AND_TAX_RESET_TO_ZERO,
                        [
                            'refund_id'    => $refund->getId(),
                            'previous_fee' => $fee,
                            'previous_tax' => $tax,
                        ]);

                    return $reversal;
                });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex,
                Trace::CRITICAL,
                TraceCode::REFUND_REVERSAL_FAILED,
                [
                    'refund_id'  => $refund->getId(),
                    'status'     => $refund->getStatus(),
                    'payment_id' => $refund->getPaymentId(),
                    'gateway'    => $refund->getGateway()
                ]);

            return null;
        }

        return $reversal;
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
            //
            // Adding refund gateway here, as this will be common to all verify responses.
            // Other attributes are being set in individual verify refund functions of each gateway.
            //
            $verifyRefundResult[Payment\Gateway::REFUND_GATEWAY] = $this->refund->getGateway();

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
            if ((Payment\Gateway::isScroogeGatewayAndMerchant($gateway) === false) or
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

    protected function prepareScroogeRefundResponse($gatewayResponse,
                                                    $gatewayRefunded,
                                                    $exception = null,
                                                    $action = Payment\Action::REFUND,
                                                    $statusCode = ErrorCode::GATEWAY_ERROR_FATAL_ERROR)
    {
        $scroogeResponse = new ScroogeResponse();

        $scroogeResponse->setSuccess($gatewayRefunded);

        $scroogeResponse->setStatusCode(($gatewayRefunded === true) ?
                                        'REFUND_SUCCESSFUL' :
                                        (
                                            (empty($exception) === false) ?
                                            (string) $exception->getCode() :
                                            $statusCode
                                        ));

        $gatewayRefundResponse = $gatewayResponse[Payment\Gateway::GATEWAY_RESPONSE] ?? '';

        $gatewayVerifyResponse = $gatewayResponse[Payment\Gateway::GATEWAY_VERIFY_RESPONSE] ?? '';

        $scroogeResponse->setGatewayResponse($gatewayRefundResponse);

        $scroogeResponse->setGatewayVerifyResponse($gatewayVerifyResponse);

        if (empty($exception) === false)
        {
            if (($action === Payment\Action::VERIFY) and (empty($gatewayVerifyResponse) === true))
            {
                $scroogeResponse->setGatewayVerifyResponse($exception->getMessage());
            }
            else if (($action !== Payment\Action::VERIFY) and (empty($gatewayRefundResponse) === true))
            {
                $scroogeResponse->setGatewayResponse($exception->getMessage());
            }
        }

        $scroogeResponse->setGatewayKeys($gatewayResponse[Payment\Gateway::GATEWAY_KEYS] ?? []);

        $scroogeResponse->setRefundGateway($gatewayResponse[Payment\Gateway::REFUND_GATEWAY] ?? $this->refund->getGateway());

        return $scroogeResponse->toArray();
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

            //
            // TODO: Remove for Scrooge
            // For refunds on Scrooge-enabled gateways, Scrooge makes an API call to mark it as processed, later.
            //
            // Marking refund as processed always in case of retry because for older
            // refunds of scrooge gateways, scrooge will not call API to mark processed
            // as older refunds are retried via API admin dashboard not via scrooge.
            //
            if ((Payment\Gateway::isScroogeGatewayAndMerchant($gateway) === false)
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

        $refund->setSpeedRequested(RefundSpeed::NORMAL);
        $refund->setSpeedDecisioned(RefundSpeed::NORMAL);

        if ($this->merchant->isFeatureEnabled(Feature::CARD_TRANSFER_REFUND) === true)
        {
            $refund->setSpeedRequested($this->merchant->getDefaultRefundSpeed());

            if (empty($input[RefundEntity::SPEED]) === false)
            {
                $refund->setSpeedRequested($input[RefundEntity::SPEED]);
            }

            if ($this->isInstantRefundsSupportedRefund($payment, $refund) === true)
            {
                $refund->setSpeedDecisioned($refund->getSpeedRequested());
            }
        }

        $refund->merchant()->associate($this->merchant);

        $refund->setBaseAmount();

        if ($refund->isRefundSpeedInstant() === true)
        {
            list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($refund);

            $refund->setFee($fee);

            $refund->setTax($tax);
        }

        $refund->balance()->associate($refund->merchant->primaryBalance);

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

        //
        // Taking mutex lock of 10 minutes here. In ideal cases, lock of 1 or 2 minutes works but
        // in cases if any alter query or any other operation is running on refunds table and
        // refund save takes lot more time that expected. For such cases, keeping mutex lock to 10 minutes.
        //
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

            // TODO: Remove for Scrooge
            if (Payment\Gateway::isScroogeGatewayAndMerchant($gateway) === true)
            {
                $this->callRefundFunctionOnScrooge($this->refund, $data);
            }
            else
            {
                $this->callRefundFunctionOnApi($this->refund, $payment, $data);
            }

            // send notification to merchant/customer, this is outside transaction
            // as we don't want to reverse the actions if mail sending fails
            $this->sendRefundNotification($payment);
        }, 600);

        $this->trace->info(
            TraceCode::REFUND_PROCESSED,
            [
                'payment_id'    => $payment->getId(),
                'refund'        => $this->refund->toArray(),
            ]);

        return $this->refund;
    }

    public function callRefundFunctionOnScrooge($refund, $data = [])
    {
        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment, $data);

        $refund->setIsScrooge(true);

        $refund->incrementAttempts();

        $this->repo->saveOrFail($refund);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH,
            $data
        );

        try
        {
            ScroogeRefund::dispatch($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH_FAILED,
                $data
            );
        }
    }

    protected function callRefundFunctionOnApi($refund, $payment, $data)
    {
        $refunded = $this->callRefundFunction($refund, $payment, $data);

        $this->refund->setGatewayRefunded($refunded[Payment\Gateway::SUCCESS]);

        $this->refund->incrementAttempts();

        $this->setRefundReference1($refunded);

        // We don't want the transaction to fail if this
        // save fails that's why keeping it outside.
        $this->repo->saveOrFail($this->refund);
    }

    protected function callRefundFunction($refund, $payment, $data, $retry = false)
    {
        if ($this->isFundTransferAttemptRefund($refund, $payment, $data) === true)
        {
            return $this->refundViaFundTransfer($refund, $payment, $data);
        }
        else
        {
            if ((isset($data[RefundConstants::IS_FTA]) === true) and ($data[RefundConstants::IS_FTA] === true))
            {
                return (new ScroogeResponse())->setSuccess(false)
                                              ->setStatusCode(ErrorCode::BAD_REQUEST_INSUFFICIENT_DATA_FOR_FTA)
                                              ->setGatewayResponse('Instant refund request failed because of insufficient data')
                                              ->toArray();
            }

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

        //
        // refund/reverse on gateway. If capture_queue feature is enabled on a merchant so payment will have txn id
        // even if gateway captured is not set, in that case, reversal request should be sent.
        // If txn id is present and capture queue is not enabled, we will call refund request as that means
        // payment would have been gateway captured.
        //
        if ((($payment->getTransactionId() !== null) and
            ($this->merchant->isFeatureEnabled(Feature::CAPTURE_QUEUE) === false)) or
            ($payment->isGatewayCaptured() === true))
        {
            $refundData = $this->refundOnGateway($data, $retry);
        }
        else if ($this->gatewaySupportsReversal($payment) === true)
        {
            $refundData = $this->reverseOnGateway($data, $retry);
        }
        else
        {
            //
            // Flow reaching here that means its an auto refund case, where transaction can not be refunded or reversed.
            // Earlier, these refunds were kept in created state forever, now marking them as processed as they are being
            // refunded by gateway automatically, we can't do anything here.
            //
            // If code reaching here, gateway refunded is set as true, hence
            // for new refunds on Scrooge-enabled gateways, Scrooge makes an API call to mark it as processed, later.
            //
            // Marking refund as processed here for all other gateways and also if refund is of the date before that gateway
            // moved to scrooge.
            //
            if ($this->refund->isScrooge() === false)
            {
                $this->refund->setStatusProcessed();
            }
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

        //
        // Enabling refund retry on created state and initiated state.
        // Refund is stuck in these state means refund is failed at some stage.
        //
        if ((Payment\Gateway::isScroogeGatewayAndMerchant($refund->getGateway()) === true) and
            (($refund->isCreated() === true) or ($refund->isInitiated() === true)))
        {
            $this->callRefundRetryFunctionOnScrooge($refund, $data);
        }
        else if ($refund->isStatusFailed() === true)
        {
            $this->callRefundRetryFunctionOnApi($refund, $data);
        }

        return $refund->getStatus();
    }

    public function callRefundRetryFunctionOnScrooge($refund, $input)
    {
        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment, $input);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::REFUND_RETRY_QUEUE_SCROOGE_DISPATCH,
            $data
        );

        try
        {
            ScroogeRefundRetry::dispatch($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REFUND_RETRY_QUEUE_SCROOGE_DISPATCH_FAILED,
                $data
            );
        }
    }

    public function callRefundRetryFunctionOnApi($refund, $data)
    {
        $payment = $refund->payment;

        $verifyResponse = $this->verifyRefund($refund);

        // true  if refunded
        // false if not refunded
        $refundedOnGateway = $verifyResponse[Payment\Gateway::SUCCESS];

        if ($refundedOnGateway === false)
        {
            $refundedOnGateway = $this->mutex->acquireAndRelease(
                $payment->getId(),
                function() use ($data, $payment, $refund)
                {
                    //
                    // Setting retry to true, will use this action later to decide weather
                    // scrooge refund should be marked as processed or not. If scrooge refunds
                    // are retried, we will mark them as processed otherwise not.
                    // For refunds attempted first time, will be marked processed by Scrooge call.
                    //
                    $refundResponse = $this->callRefundFunction($refund, $payment, $data, true);

                    return $refundResponse[Payment\Gateway::SUCCESS];
                });

            $refund->incrementAttempts();
        }
        else
        {
            $refund->setStatusProcessed();
        }

        $this->setRefundReference1($verifyResponse);

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

        $balance = $refund->balance;

        $traceData = [
            'type'              => $type,
            'message'           => 'Not enough balance',
            'merchant_balance'  => $balance->getBalance(),
            'merchant_credits'  => $balance->getRefundCredits(),
            'refund_amount'     => $refund->getBaseAmount(),
            'refund_id'         => $refund->getId(),
        ];

        if (($merchant->getRefundSource() === RefundSource::CREDITS) and
            ($balance->getRefundCredits() < $refund->getNetAmount()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
                null,
                $traceData);
        }

        if (($merchant->getRefundSource() === RefundSource::BALANCE) and
            ($balance->getBalance() < $refund->getNetAmount()))
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

    protected function getGatewayDataForScroogeRefund(Payment\Refund\Entity $refund, Payment\Entity $payment, array $input = [])
    {
        $refundData = $refund->toArray();

        $extraData = [
            'method'                    => $payment->getMethod(),
            'payment_amount'            => $payment->getAmount(),
            'payment_base_amount'       => $payment->getBaseAmount(),
            'payment_created_at'        => $payment->getCreatedAt(),
            'payment_gateway_captured'  => $payment->getGatewayCaptured(),
            'gateway_acquirer'          => $payment->terminal->getGatewayAcquirer() ?? $payment->getGateway(),
        ];

        $refundData[RefundEntity::SPEED_REQUESTED] = $refundData[RefundEntity::SPEED_DECISIONED];

        //
        // Speed decisioned is being sent as speed_requested - no need to be sent again
        //
        unset($refundData[RefundEntity::SPEED_DECISIONED]);

        $scroogeData = array_merge($refundData, $extraData);

        if ($payment->isNetbanking() === true)
        {
            $scroogeData['bank'] = $payment->getBank();
        }

        if (isset($input['vpa']) === true)
        {
            $scroogeData['fta_data']['vpa'] = $input['vpa'];
        }
        else if (($refund->isRefundSpeedInstant() === true) and
                 ($this->isPaymentCardAndCardTransferRefund($refund, $payment, true) === true))
        {
            $cardInput = $this->getCardIdInput($payment, $input);

            if (empty($cardInput) === false)
            {
                $scroogeData['fta_data']['card_transfer'] = $cardInput;
            }
        }
        else
        {
            $bankAccountInput = $this->getBankAccountInput($payment, $input);

            if (empty($bankAccountInput) === false)
            {
                $scroogeData['fta_data']['bank_account'] = $bankAccountInput;
            }
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

            assertTrue ($count === 1);

            return $refunds[0];
        }

        return null;
    }

    public function refundCapturedPayment($payment, array $input = [], Batch\Entity $batch = null)
    {
        $this->validatePaymentForRefund($payment, $input);

        // Captured payments of transfer cannot be refunded via direct API requests
        if ($payment->isTransfer() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED);
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

    protected function validatePaymentForRefund(Payment\Entity $payment, array $input = null)
    {
        if ($payment->isFullyRefunded() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }

        if ($payment->isCaptured() === false)
        {
            if ($this->merchant->isFeatureEnabled(Feature::VOID_REFUNDS) === true)
            {
                if ($this->getPaymentRefundType($input) === Payment\RefundStatus::PARTIAL)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_PARTIAL_VOID_NOT_SUPPORTED);
                }

                if ($this->gatewaySupportsReversal($payment) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_REVERSAL_NOT_SUPPORTED);
                }

            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED);
            }
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

        /** @var RefundEntity $refund */
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

        assertTrue ($refund->getTransactionId() !== null);

        assertTrue ($payment->getTransactionId() !== null);

        $data = [
            'payment'   => $payment->toArrayGateway(),
            'refund'    => $refund->toArrayGateway(),
            'amount'    => $refund->getAmount(),
            'currency'  => $refund->getCurrency()
        ];

        return $this->callGatewayForRefundValidation($data);
    }

    protected function refundViaFundTransfer(RefundEntity $refund, Payment\Entity $payment, $data = []): array
    {
        $scroogeResponse  = new ScroogeResponse();

        if ($this->refund->getAmount() === 0)
        {
            $this->refund->setStatusProcessed();

            $scroogeResponse->setSuccess(true);

            return $scroogeResponse->toArray();
        }

        $refunded = false;

        // Initializing for non scrooge refunds
        $data[RefundConstants::IS_FTA] = $data[RefundConstants::IS_FTA] ?? false;

        try
        {
            $fundTransferAttemptInput = $this->getFundTransferAttemptInput($payment);

            if (isset($data['vpa']) === true)
            {
                $fta = $this->refundViaFundTransferToVpa($data, $fundTransferAttemptInput);
            }
            else if ($this->isPaymentCardAndCardTransferRefund($refund, $payment, $data[RefundConstants::IS_FTA]))
            {
                $fta = $this->refundViaFundTransferToCard($payment, $data, $fundTransferAttemptInput);
            }
            else
            {
                $fta = $this->refundViaFundTransferToBankAccount($payment, $data, $fundTransferAttemptInput);
            }

            $refundGateway = Settlement\Channel::getNodalGatewayFromChannel($fta->getChannel());

            $this->refund->setStatus(Payment\Refund\Status::INITIATED);

            $this->refund->setBatchFundTransferId(null);

            $this->refund->setGateway($refundGateway);

            $scroogeResponse->setStatusCode(Payment\Refund\Status::INITIATED)
                            ->setRefundGateway($refundGateway)
                            ->setGatewayResponse(json_encode($fta->attributesToArray()));

            // todo : do we need to store in gateway keys also?
        }
        catch (Exception\BaseException $e)
        {
            $this->app['segment']->trackPayment($this->payment, TraceCode::PAYMENT_REFUND_FAILURE);

            $this->tracePaymentFailed($e->getError(), TraceCode::PAYMENT_REFUND_FAILURE);

            $this->refund->setStatus(Payment\Refund\Status::FAILED);
        }

        $scroogeResponse->setSuccess($refunded);

        return $scroogeResponse->toArray();
    }

    protected function refundViaFundTransferToVpa(array $data,
                                                  array $fundTransferAttemptInput): FundTransferAttempt\Entity
    {
        $input = $data['vpa'];

        return $this->repo->transaction(function () use ($input, $fundTransferAttemptInput)
        {
            if (($this->refund->hasVpa() === false) or
                ($this->refund->vpa->matches($input) === false))
            {
                $this->createAndAssociateVpa($input);
            }

            $fta = (new FundTransferAttempt\Core)->createWithVpa($this->refund,
                                                                 $this->refund->vpa,
                                                                 $fundTransferAttemptInput);

            return $fta;
        });
    }

    protected function refundViaFundTransferToBankAccount(Payment\Entity $payment,
                                                          array $data,
                                                          array $fundTransferAttemptInput): FundTransferAttempt\Entity
    {
        $bankAccountInput = $this->getBankAccountInput($payment, $data);

        if ((isset($bankAccountInput[BankAccount\Entity::TRANSFER_MODE]) === true) and
            (trim($bankAccountInput[BankAccount\Entity::TRANSFER_MODE]) !== ''))
        {
            $fundTransferAttemptInput[FundTransferAttempt\Entity::MODE] = $bankAccountInput[BankAccount\Entity::TRANSFER_MODE];
        }

        // We should delete this key regardless of its contents.
        // because this key is not required for bank account creation
        unset($bankAccountInput[BankAccount\Entity::TRANSFER_MODE]);

        return $this->repo->transaction(function () use ($bankAccountInput, $fundTransferAttemptInput)
        {
            if (($this->refund->hasBankAccount() === false) or
                ($this->refund->bankAccount->matches($bankAccountInput) === false))
            {
                $this->createAndAssociateBankAccount($bankAccountInput);
            }

            $fta = (new FundTransferAttempt\Core)->createWithBankAccount($this->refund,
                                                                         $this->refund->bankAccount,
                                                                         $fundTransferAttemptInput);

            return $fta;
        });
    }

    protected function refundViaFundTransferToCard(Payment\Entity $payment,
                                                          array $data,
                                                          array $fundTransferAttemptInput): FundTransferAttempt\Entity
    {
        $input = $this->getCardIdInput($payment, $data);

        return $this->repo->transaction(function () use ($input, $payment, $fundTransferAttemptInput)
        {
            $fta = (new FundTransferAttempt\Core)->createWithCard($this->refund,
                                                                  $payment->card,
                                                                  $fundTransferAttemptInput);

            return $fta;
        });
    }

    protected function isFundTransferAttemptRefund(RefundEntity $refund, Payment\Entity $payment, array $data = []): bool
    {
        if (isset($data[RefundConstants::IS_FTA]) === true)
        {
            if ($data[RefundConstants::IS_FTA] === false) {
                return false;
            }
        }
        else
        {
            $data[RefundConstants::IS_FTA] = false;
        }

        //
        // Refund is explicitly being attempted towards a new bank account or vpa
        // Bank account or vpa input can come from dashboard also, but card_transfer will not come from dashboard.
        // Not keeping check for card_transfer so that every time, we will evaluate if it is card_transfer refund.
        //
        if (((isset($data['bank_account']) === true) or
            (isset($data['vpa']) === true)) and ($payment->isGatewayCaptured() === true))
        {
            return true;
        }

        // Certain types of payments have refunds routed via bank transfers
        if (($payment->isBankTransfer() === true) or
            ($this->isPaymentEmandateAndEmandateRefundGateway($payment) === true) or
            ($this->isPaymentTpvAndBankTransferRefund($payment) === true) or
            ($this->isPaymentCardAndCardTransferRefund($refund, $payment, $data[RefundConstants::IS_FTA]) === true))
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

    /**
     * @param Payment\Entity $payment
     * @param RefundEntity $refund
     * @return bool
     * @throws \Exception
     */
    protected function isInstantRefundsSupportedRefund(Payment\Entity $payment, RefundEntity $refund): bool
    {
        return (($refund->isRefundRequestedSpeedInstant() === true) and
                ($payment->getMethod() === Payment\Method::CARD) and
                ($payment->hasBeenCaptured() === true) and
                ($this->isPaymentCardAndCardTransferRefund($refund, $payment) === true));
    }

    /**
     * Checking if a card payment is valid to be refunded by Card instantly.
     * If card_transfer_refund feature is present for the merchant,
     * refund will be made on card. Card should be credit card, should have vault token stored and
     * should belong to supported issuers.
     * Payment should be gateway captured, if it isn't, it should be reversed, not to be refunded directly via FTA.
     * Also checking if bin is not prepaid as fund transfers are not supported on these bins.
     *
     * @param RefundEntity $refund
     * @param Payment\Entity $payment
     * @param bool $ignoreFeatureFlag
     * @return bool
     * @throws \Exception
     */
    protected function isPaymentCardAndCardTransferRefund(
        RefundEntity $refund,
        Payment\Entity $payment,
        bool $ignoreFeatureFlag = false): bool
    {
        //
        // Check if any card FTA already exists, not allowing card fta if any previous card fta exists
        //
        foreach ($refund->fundTransferAttempts as $fundTransferAttempt)
        {
            if (empty($fundTransferAttempt->getCardId()) === false)
            {
                return false;
            }
        }

        if (($payment->hasCard() === true) and
            ($payment->card->getCardVaultToken() !== null) and ($payment->isGatewayCaptured() === true))
        {
            $iin = $payment->card->iinRelation;

            if ($iin !== null)
            {
                $cardType = strtolower($iin->getType());

                $cardIssuer = $iin->getIssuer();

                if (($cardType === Type::CREDIT) and
                    (in_array($cardIssuer, FundTransfer\Mode::getSupportedIssuers(), true) === true) and
                    (IIN::isIinPrepaid($iin->getIin()) === false))
                {
                    if (($ignoreFeatureFlag === false) and
                        ($this->merchant->isFeatureEnabled(Feature::CARD_TRANSFER_REFUND) === false))
                    {
                        return false;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * `card_transfer` will be set for scrooge refunds.
     *  Because when refund creation request is sent to scrooge, and if card refund is applicable, card_id will sent as
     * fta data.
     *
     * This is different from bank_account or vpa because card_id will never come from dashboard input.
     * It will always be read from database based on feature and issuers.
     *
     * @param Payment\Entity $payment
     * @param array $data
     * @return mixed
     */
    protected function getCardIdInput(Payment\Entity $payment, array $data = [])
    {
        if (isset($data['card_transfer']) === true)
        {
            $input = $data['card_transfer'];
        }
        else
        {
            $input['card_id'] = $payment->getCardId();
        }

        return $input;
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

            $input = (new Order\Core)->getAccountForRefund($order);
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
                // This is not used anywhere. Not sure why is this even here. Commenting out for now.
                // FundTransferAttempt\Entity::MODE      => strtoupper($bankTransfer->getMode()),
            ];
        }

        return $input;
    }

    protected function createAndAssociateVpa(array $vpaInput)
    {
        $vpa = (new Core)->createVpa($vpaInput);

        $this->refund->vpa()->associate($vpa);

        $this->refund->saveOrFail();
    }

    protected function createAndAssociateBankAccount(array $bankAccountInput)
    {
        $bankAccount = (new BankAccount\Core)->createBankAccountForSource(
                    $bankAccountInput,
                    $this->merchant,
                    $this->refund,
                    'addBankTransfer'
                );

        $this->refund->bankAccount()->associate($bankAccount);

        $this->refund->saveOrFail();
    }

    /**
     * Currently saving reference number sent by bank in refund response only for UPI and Cardless Emi refunds.
     *
     * @param array $response
     */
    protected function setRefundReference1(array $response)
    {
        if ((in_array($this->refund->payment->getMethod(), $this->getMethodsToSetRefundReference1(), true)) and
            (isset($response[Payment\Gateway::GATEWAY_KEYS][RefundEntity::RRN]) === true) and
            (empty($this->refund->getReference1()) === true))
        {
            $this->refund->setReference1($response[Payment\Gateway::GATEWAY_KEYS][RefundEntity::RRN]);
        }
    }

    protected function getMethodsToSetRefundReference1()
    {
        return [
            Payment\Method::UPI,
            Payment\Method::CARDLESS_EMI,
            Payment\Method::PAYLATER,
        ];
    }
}
