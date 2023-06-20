<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception as defaultException;

use RZP\Constants\Metric;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\RefundJournalEvents;
use RZP\Models\Ledger\ReverseShadow\Refunds\Core as ReverseShadowRefundsCore;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\Vpa;
use RZP\Models\Batch;
use RZP\Models\Order;
use RZP\Services\FTS;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Invoice;
use RZP\Models\Reversal;
use RZP\Models\Currency;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\QrPayment;
use RZP\Models\Card\Type;
use RZP\Models\Settlement;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Jobs\ScroogeRefund;
use RZP\Models\BankTransfer;
use RZP\Models\FundTransfer;
use RZP\Models\Card\IIN\IIN;
use RZP\Models\Bank\BankCodes;
use RZP\Models\Payment\Method;
use RZP\Jobs\ScroogeRefundRetry;
use RZP\Models\Payment\UpiMetadata;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RefundSource;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Feature\Constants as FeatureConstants;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Models\Transfer\Metric as TransferMetric;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Metric as RefundMetric;
use RZP\Models\Payment\Refund\Helpers as RefundHelpers;
use RZP\Models\Settlement\Holidays as SettlementHoliday;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Transaction\Processor\Refund as RefundTransactionProcessor;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;

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

    use ReverseShadowTrait;

    public function refund(Payment\Entity $payment, array $input, Batch\Entity $batch = null, $batchId = null, $unDisputedPayment = false)
    {
        if ($this->isInvalidInstantRefundsRequest($payment, $input) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INSTANT_REFUND_NOT_SUPPORTED, null, ['method' => $payment->getMethod()]);
        }

        if (($payment->getGateway() === Payment\Gateway::BHARAT_QR) or
            ($payment->isCoD() === true) or
            ($payment->isOffline() === true) or ($payment->getMethod() === Method::INTL_BANK_TRANSFER))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,null, ['method' => $payment->getMethod()]);
        }

        if (($payment->getMethod() === Method::APP) and (Payment\Gateway::isRefundNotSupportedByApp($payment->getWallet()) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,null, ['app' => $payment->getWallet()]);
        }

        $isPaymentDisputed = ($payment->isDisputed() === true);

        $isRefundForAuthorizedPayment = false;

        if (isset($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT]))
        {
            $isRefundForAuthorizedPayment = ($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT] === true);
            unset($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT]);
        }

        // either the field is set in the input, or if passed by arg, then it's not a disputed payment
        if ((isset($input[RefundConstants::UNDISPUTED_PAYMENT]) and $input[RefundConstants::UNDISPUTED_PAYMENT] === true) or
            $unDisputedPayment === true)
        {
            $isPaymentDisputed = false;

            unset($input[RefundConstants::UNDISPUTED_PAYMENT]);
        }

        if ($isPaymentDisputed === true)
        {
            $openNonFraudDisputes = $this->repo->dispute->getOpenNonFraudDisputes($payment);

            if (count($openNonFraudDisputes) > 0)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_UNDER_DISPUTE_CANNOT_BE_REFUNDED,
                    null,
                    ['input' => $input, 'payment_id' => $payment->getId(), 'method' => $payment->getMethod()]);
            }
        }

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_INITIATED, $payment);

        $refund = $this->buildRefundEntity($payment, $input, $batch, $batchId);

        try
        {
            $this->processRefund($input, $isRefundForAuthorizedPayment);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_SUCCESS, $payment);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_FAILED, $payment, $ex);

            throw $ex;
        }

        $this->pushMetrics();

        $this->eventRefundCreated($this->refund);

        if ($this->isRefundStatusProcessedForMerchant() === true)
        {
            $this->eventRefundProcessed($this->refund);
        }

        return $refund;
    }

    // returns true if the status of the refund is shown as processed to the merchant
    public function isRefundStatusProcessedForMerchant() :bool
    {
        if (($this->merchant->isFeatureRefundPublicStatusOrPendingStatusEnabled() === true) or
            ($this->refund->isRefundSpeedInstant() === true))
        {
            return ($this->refund->isProcessed() === true);
        }

        return true;
    }

    public function isInstantRefundSupportedOnPayment(Payment\Entity $payment)
    {
        // Adding is DCC checks since Instant Refunds is not supported for DCC Payments
        if (($this->isCapturedPaymentAndFeatureEnabled($payment) === false) or
            ($payment->isDCC() === true) or
            (in_array($payment->getMethod(), Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS, true) === false))
        {
            return false;
        }

        $data = $this->getRefundCreateData($payment);

        return ((isset($data[RefundConstants::INSTANT_REFUND_SUPPORT]) === true) and
            ($data[RefundConstants::INSTANT_REFUND_SUPPORT] === true));
    }

    public function getRefundCreationDataForDashboard(Payment\Entity $payment, &$dashboardEntity)
    {
        $dashboardEntity[RefundConstants::INSTANT_REFUND_SUPPORT] = false;
        $dashboardEntity[RefundConstants::GATEWAY_REFUND_SUPPORT] = true;

        // Calls scrooge to fetch data necessary for refund creation
        $data = $this->getRefundCreateData($payment);

        if ((isset($data[RefundConstants::GATEWAY_REFUND_SUPPORT]) === true) and
            ($data[RefundConstants::GATEWAY_REFUND_SUPPORT] === false))
        {
            $dashboardEntity[RefundConstants::GATEWAY_REFUND_SUPPORT] = false;

            $dashboardEntity[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] =
                ($data[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) ?? null;
        }

        if ((isset($data[RefundConstants::INSTANT_REFUND_SUPPORT]) === true) and
            ($data[RefundConstants::INSTANT_REFUND_SUPPORT] === true))
        {
            $dashboardEntity[RefundConstants::INSTANT_REFUND_SUPPORT] = true;
        }

        // Adding is DCC checks since Instant Refunds is not supported for DCC Payments
        if (($this->isCapturedPaymentAndFeatureEnabled($payment) === false) or
            ($payment->isDCC() === true) or
            (in_array($payment->getMethod(), Payment\Method::INSTANT_REFUND_SUPPORTED_METHODS, true) === false))
        {
            $dashboardEntity[RefundConstants::INSTANT_REFUND_SUPPORT] = false;
        }

        $dashboardEntity[RefundConstants::DIRECT_SETTLEMENT_REFUND] = $payment->isDirectSettlementRefund();

        $this->trace->info(
            TraceCode::PAYMENT_FETCH_REFUND_CREATE_DATA,
            [
                Payment\Refund\Entity::PAYMENT_ID                     => $payment->getId(),
                RefundConstants::INSTANT_REFUND_SUPPORT               => $dashboardEntity[RefundConstants::INSTANT_REFUND_SUPPORT],
                RefundConstants::GATEWAY_REFUND_SUPPORT               => $dashboardEntity[RefundConstants::GATEWAY_REFUND_SUPPORT],
                RefundConstants::DIRECT_SETTLEMENT_REFUND             => $dashboardEntity[RefundConstants::DIRECT_SETTLEMENT_REFUND],
            ]
        );
    }

    public function isCapturedPaymentAndFeatureEnabled(Payment\Entity $payment)
    {
        return (($payment->isCaptured() === true) and
                ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === false));
    }

    protected function isInvalidInstantRefundsRequest(Payment\Entity $payment, array $input)
    {
        return ((isset($input[RefundEntity::SPEED]) === true) and
                (in_array($input[RefundEntity::SPEED], RefundSpeed::REFUND_MERCHANT_ALLOWED_INSTANT_SPEEDS) === true) and
                ($this->isCapturedPaymentAndFeatureEnabled($payment) === false));
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
        if ($this->isBatchRefundRequestV1_1($payment) === true)
        {
            $input['batch_id'] = (empty($batch) === false) ? $batch->getId() : '';

            $input['admin_batch_upload'] = true;

            $this->trace->info(
                TraceCode::REFUND_FROM_BATCH_UPLOAD_SCROOGE,
                [
                    'payment_id' => $payment->getId(),
                    'input'      => $input,
                ]);

            // Route refund creation to scrooge
            return $this->newRefundV2Flow($payment, $input);
        }

        return $this->refund($payment, $input, $batch);
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

        if (isset($input[RefundEntity::MODE_REQUESTED]) === true)
        {
            $data[RefundEntity::MODE] = $input[RefundEntity::MODE_REQUESTED];
        }

        return $data;
    }

    public function scroogeGatewayVerifyRefund(RefundEntity $refund, array $input)
    {
        $payment = $refund->payment;

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

    // This route always calls the gateway - to be used in manual verify refund actions
    public function scroogeVerifyRefund(RefundEntity $refund, array $input)
    {
        $payment = $refund->payment;

        $this->setPaymentAndRefundInfo($refund, $payment);

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

    // create a virtual refund model entity based on the input params
    public function createVirtualRefundEntity(Payment\Entity $payment, array $input = [])
    {
        // build entity
        $refundEntity = (new RefundEntity);

        // remove public ids if any
        $refundInput[RefundEntity::ID]              = Payment\Entity::stripDefaultSign($input[RefundEntity::ID]);
        $refundInput[RefundEntity::BATCH_ID]        = Payment\Entity::stripDefaultSign($input[RefundEntity::BATCH_ID] ?? null);

        // set other values if present
        $refundInput[RefundEntity::AMOUNT]          = $input[RefundEntity::AMOUNT] ?? null;
        $refundInput[RefundEntity::CURRENCY]        = $input[RefundEntity::CURRENCY] ?? null;
        $refundInput[RefundEntity::SPEED_REQUESTED] = $input[RefundEntity::SPEED_REQUESTED] ?? $input[RefundEntity::SPEED] ?? null;
        $refundInput[RefundEntity::SPEED_PROCESSED] = $input[RefundEntity::SPEED_PROCESSED] ?? null;
        $refundInput[RefundEntity::STATUS]          = $input[RefundEntity::STATUS] ?? null;
        $refundInput[RefundEntity::RECEIPT]         = $input[RefundEntity::RECEIPT] ?? null;
        $refundInput[RefundEntity::NOTES]           = $input[RefundEntity::NOTES] ?? null;
        $refundInput[RefundConstants::CREATED_AT]   = $input[RefundConstants::CREATED_AT] ?? null;
        $refundInput[RefundEntity::TRANSACTION_ID]  = $input[RefundEntity::TRANSACTION_ID] ?? null;

        // set isScrooge
        $refundInput[RefundEntity::IS_SCROOGE]      = true;

        // just remove what is not required,
        // will be imp when we dont need to create virtual entity
        unset($input[RefundEntity::TRANSACTION_ID]);

        // assign values to entity
        foreach ($refundInput as $key => $value)
        {
            $refundEntity[$key] = $value;
        }

        $merchant = $payment->merchant;

        // add necessary associations
        $refundEntity->payment()->associate($payment);
        $refundEntity->merchant()->associate($merchant);

        return $refundEntity;
    }

    public function refundAuthorizedPayment(Payment\Entity $payment, array $input = [])
    {
        $this->trace->info(
            TraceCode::REFUND_FROM_AUTHORIZED_REQUEST,
            [
                'payment_id'    => $payment->getId(),
                'input'         => $input,
            ]);

        // this param will help identify the authorized refund flow
        $input[RefundConstants::REFUND_AUTHORIZED_PAYMENT] = true;

         if ($this->isNonMerchantRefundRequestV1_1($payment) === true)
         {
             $this->trace->info(
             TraceCode::REFUND_FROM_AUTHORIZED_REQUEST_SCROOGE,
             [
                 'payment_id' => $payment->getId(),
                 'input'      => $input,
             ]);

             // Route refund creation to scrooge
             return $this->newRefundV2Flow($payment, $input);
         }

        // Some bank transfer payments cannot be refunded.
        if ($payment->isBankTransfer() === true)
        {
            $this->validateBankTransferPaymentForRefund($payment);
        }

        $this->setPayment($payment);

        if ($this->payment->isAuthorized() === false || $this->payment->hasBeenCaptured())
        {
            throw new Exception\InvalidArgumentException(
                'Can only refund authorized payments here but ' .
                'the status is ' . $payment->getStatus() . ' and captured_at is ' . $payment->getCapturedAt());
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

    private function newRefundV2Flow(Payment\Entity $payment, array $input = [])
    {
        // Special case - payment pages calls refund via public auth. In Scrooge, passport will have authenticated=false.
        // Short term workaround to allow payment pages business flow. Long term, service mesh would help Scrooge identify and authenticate internal services with respective permissions.
        //
        // pls note, in API codebase, payment link is treated as payment page.
        // Check - ApiEventSubscriber::onPaymentCaptured
        if ($payment->hasPaymentLink() === true)
        {
            $input['payment_page'] = true;
        }

        // Route refund creation to scrooge
        $response = (new Payment\Refund\Service())->scroogeRefundCreate($payment->getPublicId(), $input);

        $virtualRefundEntity = $this->createVirtualRefundEntity($payment, $response);

        return $virtualRefundEntity;
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

        if ($this->isRefundRequestV1_1($this->merchant->getId(), $payment) === true)
        {
            $this->trace->info(
                TraceCode::REFUND_FROM_CAPTURED_REQUEST_SCROOGE,
                [
                    'payment_id' => $payment->getId(),
                    'input' => $input,
                ]);

            // For captured payments, refund amount either needs to be defined in $input params, or
            // by default refund amount will be full payment amount.
            // No need to override refund amount here.

            // Route refund creation to scrooge
            return $this->newRefundV2Flow($payment, $input);
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

        return $this->refundCapturedPayment($payment, $input, null, null, 'off');
    }

    /**
     * Process refund on a payment that has Marketplace transfers
     *
     * @param array $input
     *
     * @throws \Exception
     */
    public function processRefundWithTransfers(array $input)
    {
        if (isset($input['reversals']) === false)
        {
            return;

            // throw new Exception\BadRequestValidationFailureException(
            //         'The reversals parameter is required for this refund request');
        }

        try
        {
            $refunds = $this->repo->transaction(function() use ($input)
            {
                $refunds = $this->processReversals($input['reversals']);

                unset($input['reversals']);

                return $refunds;
            });

            (new TransferMetric)->pushReversalSuccessMetrics();
        }
        catch (\Exception $e)
        {
            (new TransferMetric)->pushReversalFailedMetrics($e);

            throw $e;
        }

        try
        {
            // Dispatch refunds to scrooge
            foreach ($refunds as $refund)
            {
                $this->callRefundFunctionOnScrooge($refund);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH_FAILED
            );
        }
    }

    public function refundPaymentViaBatchEntry(Payment\Entity $payment, array $input, Batch\Entity $batch = null, $batchId = null)
    {
        //
        // Check if a refund already exists.
        // If one exists, then we should not fire a new one else two refunds will happen.
        //

        $refund = null;

        if ((empty($batch) === false) or (empty($batchId) === false))
        {
            if ($this->isBatchRefundRequestV1_1($payment) === true)
            {
                $input['batch_id'] = (empty($batch) === false) ? $batch->getId() : $batchId;

                $this->trace->info(
                    TraceCode::REFUND_FROM_MERCHANT_BATCH_SCROOGE,
                    [
                        'payment_id' => $payment->getId(),
                        'input'      => $input,
                    ]);

                // Route refund creation to scrooge
                return $this->newRefundV2Flow($payment, $input);
            }

            if (empty($batch) === false)
            {
                $refund = $this->findExistingRefundForBatch($batch, $payment);
            }
        }

        if ($refund !== null)
        {
            return $refund;
        }

        // No refund existed so fire a new one.
        return $this->refundCapturedPayment($payment, $input, $batch, $batchId);
    }

    /**
     * @param Payment\Refund\Entity $refund
     * @param Payment\Entity $payment
     *
     * @return null|Transaction\Entity
     * @throws Exception\LogicException
     * @throws Exception\ServerErrorException
     */
    public function createTransactionForRefund(
        Payment\Refund\Entity $refund, Payment\Entity $payment, $txnId = null)
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

        //NOTE:
        //if txn_id is not set and feature is enabled then cls is called
        //if txn_id is set then a transaction is created (CLS flow is not called irrespective of feature flag).

        // For cases such as ds with refund (normal speed), we do not create CLS entries but transaction might be created
        // We send txn_id as "" in such cases as the feature is enabled for the merchant (if we do not send, it'll end up calling CLS again)

        // feature is not enabled ---> flow is v1  ----> transaction created (no txn id is passed)
        // feature is not enabled ---> flow is v2  ----> transaction created (no txn id is passed)
        // feature is enabled ---> flow is v1 ---> journal is created ---> transaction created (with journal id)
        // feature is enabled ---> flow is v2 ---> journal id is sent ---> transaction created (with journal id)
        // feature is enabled ---> flow is v2 ---> journal id is not sent ---> transaction created (without journal id) (send journal id as empty string)
        if ($this->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === true and $txnId === null)
        {
            $journalResponse = (new ReverseShadowRefundsCore())->createLedgerEntriesForRefundReverseShadow($refund);
            if (isset($journalResponse['id']) === true)
            {
                $txnId = $journalResponse['id'];
            }
            else
            {
                $this->trace->debug(TraceCode::JOURNAL_ID_NOT_PRESENT, [
                   "response"   => $journalResponse
                ]);
            }
        }

        //
        // We should create a refund transaction ONLY after payment transaction is created
        // to ensure the ledger flow is correct. If it's a non-captured payment and does not
        // have transaction, then we will skip refund transaction creation. Captured payments
        // should ideally have transactions so we will block refund creation in such cases.
        //
        if ($payment->getTransactionId() === null and $this->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            if ($payment->hasBeenCaptured() === false)
            {
                return null;
            }
            else
            {
                throw new Exception\LogicException(
                    'Payment transaction should have been present',
                    null,
                    [
                        'payment_id'    => $payment->getId(),
                        'refund_id'     => $refund->getId(),
                    ]);
            }
        }

        $txnCore = new Transaction\Core;

        // if $txnId is an empty string then we want to auto generate id, hence passing it as null
        if ($txnId === "")
        {
            $txnId = null;
        }
        list($txn, $feesSplit) = $txnCore->createFromRefund($refund, $txnId);

        $this->repo->saveOrFail($txn);

        $txnCore->saveFeeDetails($txn, $feesSplit);

        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATED,
            [
                'payment_id'        => $payment->getId(),
                'refund_id'         => $refund->getId(),
                'transaction_id'    => $txn->getId(),
            ]);

        if($this->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_JOURNAL_WRITES) === true)
        {
            \Event::dispatch(new TransactionalClosureEvent(function () use ($txn, $refund)
            {
                RefundJournalEvents::createLedgerEntriesForRefunds($this->mode, $refund, $txn);
            }));
        }

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

        if (($refund->payment->hasBeenCaptured() === false) or (($feeOnlyReversal === true) and ($refund->getFee() === 0)))
        {
            return null;
        }

        // If PG_LEDGER_REVERSE_SHADOW flag is enabled, check if refund transaction exists, else return null
        // To ensure that refund forward transaction has this amount / fees debited, if debit is 0, it
        // could be a Direct Settlement just an authorized transaction refund - for which we have handled before this,

        if ($this->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            if (($refund->transaction->getDebit() === 0) and
                ($refund->transaction->getCreditType() === Transaction\CreditType::DEFAULT))
            {
                return null;
            }
        }

        if (($refund->isStatusReversed() === true)) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                [
                    'refund_id'  => $refund->getId(),
                    'status'     => $refund->getStatus(),
                    'payment_id' => $refund->getPaymentId(),
                    'gateway'    => $refund->getGateway(),
                    'fee'        => $refund->getFee(),
                ],
            'Attempted to reverse an already reversed refund amount/fee');
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
            if($ex->getCode() === ErrorCode::BAD_REQUEST_REFUND_REVERSAL_NOT_APPLICABLE)
            {
                return null;
            }

            $this->trace->traceException($ex,
                Trace::CRITICAL,
                TraceCode::REFUND_REVERSAL_FAILED,
                [
                    'refund_id'  => $refund->getId(),
                    'status'     => $refund->getStatus(),
                    'payment_id' => $refund->getPaymentId(),
                    'gateway'    => $refund->getGateway()
                ]);

            throw $ex;
        }

        return $reversal;
    }

    /**
     * Get the type of refund being processed - FULL / PARTIAL,
     * based on the refund amount and amount already refunded
     *
     * @param array $input
     *
     * @param Payment\Entity $payment
     * @return string
     */
    protected function getPaymentRefundType(array $input, Payment\Entity $payment)
    {
        $type = Payment\RefundStatus::PARTIAL;

        if ((isset($input['amount']) === false) or
            ((int) $input['amount'] === $payment->getAmountUnrefunded()))
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
            if (($this->refund->isScrooge() === false) or
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

            //
            // TODO: Remove for Scrooge
            // For refunds on Scrooge-enabled gateways, Scrooge makes an API call to mark it as processed, later.
            //
            // Marking refund as processed always in case of retry because for older
            // refunds of scrooge gateways, scrooge will not call API to mark processed
            // as older refunds are retried via API admin dashboard not via scrooge.
            //
            if (($this->refund->isScrooge() === false)
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

            if ($payment->isExternal() == false)
            {
                $this->repo->payment->lockForUpdate($payment->getKey());
            }

            $this->createTransactionForRefund($this->refund, $payment);

            //
            // Unsetting refund -> mode_requested because the mode_requested is a virtual attribute not being saved in
            // the database. Scrooge is storing the mode_requested attribute - which is being sent as part of refund data
            //
            unset($this->refund->mode_requested);

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

    protected function buildRefundEntity(Payment\Entity $payment, array &$input, Batch\Entity $batch = null, $batchId = null)
    {
        $this->setPayment($payment);

        $refund = (new RefundEntity)->build($input, $payment);

        $refund->merchant()->associate($this->merchant);

        $refund->setBaseAmount();

        $refund->setGatewayAmountCurrency();

        $refund->setSpeedRequested(RefundSpeed::NORMAL);

        $refund->setSpeedDecisioned(RefundSpeed::NORMAL);

        $this->calculateRefundSpeed($payment, $refund, $input);

        // Speed could have changed to normal if mode is not supported
        if ($refund->isRefundSpeedInstant() === true)
        {
            list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($refund);

            $refund->setFee($fee);

            $refund->setTax($tax);

            // instant refund will be settled by Razorpay
            $refund->setAttribute(RefundEntity::SETTLED_BY, 'Razorpay');
        }
        else
        {
            $refund->setSpeedProcessed(RefundSpeed::NORMAL);
        }

        $this->refundBalanceChecks($refund);

        //
        // If the batch is created in batch service, api db will not have batch entity corresponding to batch_id.
        // As a result we cant associate the refund to batch entity. So, only setting the batch_id here.
        //
        (empty($batchId) === false) ? $refund->setBatchId($batchId) : $refund->batch()->associate($batch);

        $this->refund = $refund;

        return $refund;
    }

    protected function calculateRefundSpeed(Payment\Entity $payment, RefundEntity &$refund, array &$input)
    {
        $supportData = $this->getRefundCreateData($payment, $refund);

        if ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === false)
        {
            $refund->setSpeedRequested($this->merchant->getDefaultRefundSpeed());

            // Override default refund speed if set by merchant
            if (empty($input[RefundEntity::SPEED]) === false)
            {
                $refund->setSpeedRequested($input[RefundEntity::SPEED]);
            }

            //For Late auth payments which has not been captured and in authorized state, fetching the refund speed
            //from config and checking if void refund is supported. If yes set speed requested from config else set
            //normal speed.
            //$lateAuthVoidRefundInstantSpeed flag has been used to check isInstantRefundsSupportedRefund method for
            //not captured payments.
            $lateAuthVoidRefundInstantSpeed = false;

            if ($payment->isLateAuthorized() === true)
            {
                $lateAuthRefundSpeed = $this->getLateAuthPaymentRefundSpeedIfApplicable($payment);

                if (isset($lateAuthRefundSpeed) === true)
                {
                    $refund->setSpeedRequested($lateAuthRefundSpeed);

                    if (($lateAuthRefundSpeed === RefundSpeed::OPTIMUM) and
                        ($payment->hasBeenCaptured() === false) and
                        ($this->merchant->isFeatureEnabled(Feature::VOID_REFUNDS) === true))
                    {
                        $refundAmount = $refund->getAttribute(RefundEntity::AMOUNT);

                        if ((isset($refundAmount) === true) and ($refundAmount !== $payment->getAmountUnrefunded()) and
                            $this->gatewaySupportsReversal($payment) === false)
                        {
                            $refund->setSpeedRequested(RefundSpeed::NORMAL);
                        }
                        else
                        {
                            $lateAuthVoidRefundInstantSpeed = true;
                        }
                    }
                }
            }

            if ($this->isInstantRefundsSupportedRefund($payment, $refund, $lateAuthVoidRefundInstantSpeed) === true)
            {
                $refund->setSpeedDecisioned($refund->getSpeedRequested());
            }
        }
        else
        {
            // For disable_instant_refunds feature enabled merchants,
            // Set actual speed requested just for tracking purposes
            // refund will still be decisioned and processed at normal speed
            if (empty($input[RefundEntity::SPEED]) === false)
            {
                $refund->setSpeedRequested($input[RefundEntity::SPEED]);
            }
        }

        // Calculate final speed decisioned
        switch ($refund->getSpeedDecisioned())
        {
            // This will be the case when we allow merchants to send speed as instant
            // not a present case. We will throw exception if instant refund is not supported
            case RefundSpeed::INSTANT:
                if ((isset($supportData[RefundConstants::INSTANT_REFUND_SUPPORT]) === true) and
                    ($supportData[RefundConstants::INSTANT_REFUND_SUPPORT] === true))
                {
                    $refund->setModeRequested($supportData[RefundConstants::MODE]);
                }
                else
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_NOT_ALLOWED,
                        null,
                        null,
                        'Payment cannot be refunded in instant speed');
                }

                break;

            // if instant refund and gateway refund is both supported only then speed decisioned can remain optimum
            // if only instant refund is supported, we will decision it to instant
            // if only gateway refund is supported, we will decision it to normal
            // else we just throw and exception stating refund creation is not supported
            case RefundSpeed::OPTIMUM:
                // If instant refund is supported
                if ((isset($supportData[RefundConstants::INSTANT_REFUND_SUPPORT]) === true) and
                    ($supportData[RefundConstants::INSTANT_REFUND_SUPPORT] === true))
                {
                    $refund->setModeRequested($supportData[RefundConstants::MODE]);

                    if ((isset($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT]) === true) and
                        ($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT] === false))
                    {
                        $refund->setSpeedDecisioned(RefundSpeed::INSTANT);

                        $input[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] = $supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] ?? 180;
                    }
                }
                // If gateway refund is supported
                else if ((isset($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT]) === true) and
                         ($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT] === true))
                {
                    $refund->setSpeedDecisioned(RefundSpeed::NORMAL);
                }
                // If neither instant/gateway refund is supported
                else
                {
                    // calculating dynamic error description
                    $desc = ((isset($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === true) and
                             (is_int($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === false)) ?
                        RefundHelpers::getBlockRefundsMessage(0):
                        RefundHelpers::getBlockRefundsMessage(0, $supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK,
                        null,
                        null,
                        $desc);
                }

                break;

            // If gateway refund is not supported, we throw an exception
            // But error code depends on the instant refund support. If its supported we state that in our error description
            case RefundSpeed::NORMAL:
                if ((isset($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT]) === true) and
                    ($supportData[RefundConstants::GATEWAY_REFUND_SUPPORT] === false))
                {
                    if ((isset($supportData[RefundConstants::INSTANT_REFUND_SUPPORT]) === true) and
                        ($supportData[RefundConstants::INSTANT_REFUND_SUPPORT] === true))
                    {
                        // calculating dynamic error description
                        $errorMsg = ((isset($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === true) and
                                     (is_int($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === false)) ?
                            RefundHelpers::getBlockRefundsMessage(1):
                            RefundHelpers::getBlockRefundsMessage(1, $supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]);

                        $errorCode = ErrorCode::BAD_REQUEST_ONLY_INSTANT_REFUND_SUPPORTED;
                    }
                    else
                    {
                        // calculating dynamic error description
                        $errorMsg = ((isset($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === true) and
                                     (is_int($supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === false)) ?
                            RefundHelpers::getBlockRefundsMessage(0):
                            RefundHelpers::getBlockRefundsMessage(0, $supportData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]);

                        $errorCode = ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK;
                    }

                    throw new Exception\BadRequestException($errorCode, null, null, $errorMsg);
                }

                break;
        }
    }

    protected function refundBalanceChecks(RefundEntity &$refund)
    {
        $refund->balance()->associate($refund->merchant->primaryBalance);

        if ($refund->payment->hasBeenCaptured() === true)
        {
            //
            // Merchant balance / refund credits checks are not applicable in case of a normal refund on a
            // Direct Settlement with Refund terminal - since the payment / refund will be settled
            // by the gateway to the merchant directly and
            // balance checks and deductions are made at the gateway itself
            //
            // In case of Instant Refunds - we process the refund and deduct merchant balance directly,
            // hence balance checks are necessary
            //
            $validBalanceCheckNotApplicable = (($refund->isRefundSpeedInstant() === false) and
                ($refund->isDirectSettlementRefund() === true));

            if ($validBalanceCheckNotApplicable === false)
            {
                $this->validateMerchantBalance($refund, 'refund');
            }
        }
    }

    public function refundPaymentUpdate($payment, array $refundInput)
    {
        $this->setPayment($payment);

        $refundId = $refundInput[RefundEntity::ID];

        $amount = intval($refundInput[RefundEntity::AMOUNT]);

        $baseAmount = intval($refundInput[RefundEntity::BASE_AMOUNT]);

        // Updates payment attributes. Throws exception on failure
        $this->handlePaymentUpdate($payment, $refundId, $amount, $baseAmount);
    }

    protected function handlePaymentUpdate($payment, string $refundId, int $refundAmount, int $refundBaseAmount)
    {
        // setting strict attribute to true on mutex acquire so that updates dont happen on redis exceptions
        $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $refundId, $refundAmount, $refundBaseAmount)
            {
                if ($payment->isExternal() == false)
                {
                    $payment->reload();
                }

                $this->trace->info(
                    TraceCode::REFUND_PAYMENT_UPDATE_INITIATED,
                    [
                        'refund_id'                    => $refundId,
                        'payment_id'                   => $payment->getId(),
                        'payment_status'               => $payment->getStatus(),
                        'payment_refund_status'        => $payment->getRefundStatus(),
                        'payment_amount_refunded'      => $payment->getAmountRefunded(),
                        'payment_base_amount_refunded' => $payment->getBaseAmountRefunded(),
                    ]);

                if ($refundBaseAmount >= 0)
                {
                    if ($payment->isFullyRefunded() === true)
                    {
                        throw new Exception\InvalidArgumentException(
                            'Can only refund a non-refunded payment but here ' .
                            'the status is ' . $payment->getStatus());
                    }

                    // update the payment entity for refund
                    $this->payment->refundAmount($refundAmount, $refundBaseAmount);
                }
                // amount will be negative for compensatory actions
                else
                {
                    $amountRefunded = $payment->getAmountRefunded();

                    $baseAmountRefunded = $payment->getBaseAmountRefunded();

                    $amountRefunded = $amountRefunded + $refundAmount;
                    $baseAmountRefunded = $baseAmountRefunded + $refundBaseAmount;

                    $payment->setAmountRefunded($amountRefunded);
                    $payment->setBaseAmountRefunded($baseAmountRefunded);

                    $this->resetPaymentStatusAndRefundStatus($payment);
                }

                $this->repo->saveOrFail($payment);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            0,
            100,
            200,
            true);

        $this->trace->info(
            TraceCode::REFUND_PAYMENT_UPDATE_COMPLETE,
            [
                'refund_id'                    => $refundId,
                'payment_id'                   => $payment->getId(),
                'payment_status'               => $payment->getStatus(),
                'payment_refund_status'        => $payment->getRefundStatus(),
                'payment_amount_refunded'      => $payment->getAmountRefunded(),
                'payment_base_amount_refunded' => $payment->getBaseAmountRefunded(),
            ]);
    }

    public function scroogeRefundTransactionCreate($payment, array $refundInput)
    {
        $this->setPayment($payment);

        $refundId = $refundInput[RefundEntity::ID];

        $journalId = (isset($refundInput[RefundConstants::JOURNAL_ID]) === true) ? $refundInput[RefundConstants::JOURNAL_ID] : null;

        $amount = intval($refundInput[RefundEntity::AMOUNT]);

        $baseAmount = intval($refundInput[RefundEntity::BASE_AMOUNT]);

        // For emandate Rs0 registration refunds
        if (($payment->isEmandate() === true) && (empty($refundInput[RefundEntity::AMOUNT]) === true))
        {
            // unsetting it since validator on amount would execute and build will fail, if input has amount.
            // also, emandate raises refund requests by NOT supplying the amount. keeping it uniform.
            unset($refundInput[RefundEntity::AMOUNT]);
        }

        $refund = (new Payment\Refund\Service())->buildVirtualRefundEntity($payment, $refundInput);

        $refund->balance()->associate($this->merchant->primaryBalance);

        if (empty($refundInput[RefundConstants::MODE]) === false)
        {
            $refund->setModeRequested($refundInput[RefundConstants::MODE]);
        }

        // Validates and throws exception in case of insufficient balance for applicable refunds
        $this->refundBalanceChecks($refund);

        $isPGLedgerEnabled = $this->merchant->isFeatureEnabled(RefundConstants::PG_LEDGER_REVERSE_SHADOW);

        // Handle payment update only if PG Ledger reverse shadow feature flag is not enabled, as the payment update will happen in v2 flow itself.
        // for few cases where we do not update payment but just send a kafka message to create transaction, we need to ensure payment update
        // successfully happens. If journalId is present in the request but is an empty string then payment update did not happen on scrooge
        if($isPGLedgerEnabled === false or $journalId === "") {
            // Updates payment attributes. Throws exception on failure
            $this->handlePaymentUpdate($payment, $refundId, $amount, $baseAmount);
        }

        try
        {
            $transaction = $this->repo->transaction(function() use ($refund, $payment, $journalId)
            {
                return $this->createTransactionForRefund($refund, $payment, $journalId);
            });

            $transactionId = null;

            // transaction will not be created for cases like non captured payment with no transaction
            // if there is an actual error in transaction create, an exception will be thrown
            if (empty($transaction) === false)
            {
                $transactionId = $transaction->getId();
            }

            return RefundHelpers::getScroogeRefundTransactionCreateResponse(null, false, $transactionId);
        }
        catch (\Exception $ex)
        {
            // catching exception here specifically
            // because payment is already updated by now but transaction create failed
            // so we set compensate flag true and scrooge will handle compensating the payment
            return RefundHelpers::getScroogeRefundTransactionCreateResponse($ex, true);
        }
    }

    private function  getLateAuthPaymentRefundSpeedIfApplicable($payment)
    {
        $processor = new Processor($this->merchant);

        $lateAuthConfig = $processor->getLateAuthPaymentConfig($payment);

        if (isset($lateAuthConfig) === false)
        {
            return null;
        }

        $autoTimeoutDuration = $lateAuthConfig['capture_options']['automatic_expiry_period'];

        if (isset($lateAuthConfig['capture_options']['manual_expiry_period']) === false)
        {
            $manualTimeoutDuration = $autoTimeoutDuration;
        }
        else
        {
            $manualTimeoutDuration = $lateAuthConfig['capture_options']['manual_expiry_period'];
        }

        $difference = $processor->getTimeDifferenceInAuthorizeAndCreated($payment);

        if ($difference > $manualTimeoutDuration)
        {
            return $lateAuthConfig['capture_options']['refund_speed'];
        }

        return null;
    }

    public function fetchFeeForRefundAmount($payment, $input)
    {
        try
        {
            $this->setPayment($payment);

            $refundCreationInput = $input;

            unset($refundCreationInput[RefundConstants::MODE]);

            $refund = (new Payment\Refund\Entity)->build($refundCreationInput, $payment);

            $refund->merchant()->associate($this->merchant);

            $refund->setBaseAmount();
            $refund->setGatewayAmountCurrency();
            $refund->setSpeedRequested(RefundSpeed::OPTIMUM);
            $refund->setSpeedDecisioned(RefundSpeed::OPTIMUM);


            if (empty($input[RefundConstants::MODE]) === true)
            {
                $data = $this->getRefundCreateData($payment, $refund);

                if (empty($data[RefundConstants::MODE]) === false)
                {
                    $refund->setModeRequested($data[RefundConstants::MODE]);
                }

            }
            else
            {
                $refund->setModeRequested($input[RefundConstants::MODE]);
            }

            list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($refund);

            return [
                RefundEntity::FEE => $fee,
                RefundEntity::TAX => $tax,
            ];
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::REFUND_PRICING_FETCH_FAILURE_EXCEPTION,
                [
                    'payment_id'     => $payment->getId(),
                    'payment_method' => $payment->getMethod(),
                    'refund_amount'  => $input[RefundEntity::AMOUNT],
                ]);
        }

        return [
            RefundEntity::FEE => null,
            RefundEntity::TAX => null,
        ];
    }

    // Fetches refund creation related data of the payment for FE apps
    //
    // Reference : https://docs.google.com/document/d/134CGvpRknoACraReuAVB7EAtUCmYRA4GYfu_cLhnm5w/edit?usp=sharing
    public function fetchRefundCreationData(Payment\Entity $payment, $input)
    {
        // Set defaults
        $data = [
            RefundEntity::AMOUNT => [
                RefundConstants::VALUE => strval($input[RefundEntity::AMOUNT]),
                RefundEntity::CURRENCY => $payment->getCurrency()
            ],
            RefundConstants::INSTANT_REFUND => [
                RefundConstants::IR_OPTION => RefundConstants::IR_OPTION_DISABLED,
                RefundConstants::FEES => [
                    RefundEntity::FEE => null,
                    RefundEntity::TAX => null,
                ]
            ],
            RefundConstants::IS_REFUND_ALLOWED => true,
            RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED => false,
            RefundConstants::MESSAGES => []
        ];

        $buildInput = [
            RefundConstants::AMOUNT => $input[RefundConstants::AMOUNT],
            RefundConstants::SPEED => RefundSpeed::OPTIMUM
        ];

        // Errorcodes thrown on balance validation failure
        $balanceErrorCodes = [
            ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED,
            ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE,
            ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE_FALLBACK,
            ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
        ];

        $lowBalance = false;

        try
        {
            $refund = $this->buildRefundEntity($payment, $buildInput);

            if ($refund->isRefundSpeedInstant() === true)
            {
                $data[RefundConstants::INSTANT_REFUND][RefundConstants::IR_OPTION] = RefundConstants::IR_OPTION_ENABLED;

                $data[RefundConstants::INSTANT_REFUND][RefundConstants::FEES][RefundEntity::FEE] = $refund->getFee();

                $data[RefundConstants::INSTANT_REFUND][RefundConstants::FEES][RefundEntity::TAX] = $refund->getTax();

                // refunds on aged payments, only instant refund is supported. use onlyOptimum option
                if ($refund->getSpeedDecisioned() === RefundSpeed::INSTANT)
                {
                    $data[RefundConstants::INSTANT_REFUND][RefundConstants::IR_OPTION] = RefundConstants::IR_OPTION_ONLY_OPTIMUM;

                    // Message : only instant refund supported
                    $data[RefundConstants::MESSAGES][RefundConstants::MESSAGE_KEY_REFUNDS_ON_AGED_PAYMENTS][RefundConstants::MESSAGE_REASON] = RefundHelpers::getBlockRefundsMessage(1);
                }
                // If merchants default refund is optimum, use defaultOptimum option
                else if ($payment->merchant->getDefaultRefundSpeed() === RefundSpeed::OPTIMUM)
                {
                    $data[RefundConstants::INSTANT_REFUND][RefundConstants::IR_OPTION] = RefundConstants::IR_OPTION_DEFAULT_OPTIMUM;
                }
            }
            else
            {
                $data[RefundConstants::MESSAGES][RefundConstants::MESSAGE_KEY_IR_SUPPORTED_INSTRUMENTS][RefundConstants::MESSAGE_REASON] = RefundConstants::MESSAGE_REASON_IR_SUPPORTED_INSTRUMENTS;
            }
        }
        catch (\Throwable $ex)
        {
            if (in_array($ex->getCode(), $balanceErrorCodes) === true)
            {
                $lowBalance = true;

                $data[RefundConstants::MESSAGES][RefundConstants::MESSAGE_KEY_INSUFFICIENT_FUNDS][RefundConstants::MESSAGE_REASON] = RefundConstants::MESSAGE_REASON_IR_INSUFFICIENT_FUNDS;
            }

            if ($ex->getCode() === ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK)
            {
                $data[RefundConstants::IS_REFUND_ALLOWED] = false;

                $data[RefundConstants::MESSAGES][RefundConstants::MESSAGE_KEY_REFUNDS_ON_AGED_PAYMENTS][RefundConstants::MESSAGE_REASON] = $ex->getMessage();
            }
        }

        // validating Balance for normal refund if there is not enough balance for instant refund
        if (($data[RefundConstants::IS_REFUND_ALLOWED] === true) and ($lowBalance === true))
        {
            $buildInput[RefundConstants::SPEED] = RefundSpeed::NORMAL;

            try
            {
                $this->setPayment($payment);

                $refund = (new Payment\Refund\Entity)->build($buildInput, $payment);

                $refund->merchant()->associate($this->merchant);

                $refund->setBaseAmount();

                $refund->setGatewayAmountCurrency();

                $refund->setSpeedRequested(RefundSpeed::NORMAL);

                $refund->setSpeedDecisioned(RefundSpeed::NORMAL);

                $this->refundBalanceChecks($refund);
            }
            catch (\Throwable $ex)
            {
                $data[RefundConstants::IS_REFUND_ALLOWED] = false;

                $data[RefundConstants::MESSAGES][RefundConstants::MESSAGE_KEY_INSUFFICIENT_FUNDS][RefundConstants::MESSAGE_REASON] = RefundConstants::MESSAGE_REASON_INSUFFICIENT_FUNDS;
            }
        }

        if ($data[RefundConstants::IS_REFUND_ALLOWED] === true)
        {
            try
            {
                $transferInput[RefundEntity::AMOUNT] = $input[RefundEntity::AMOUNT];

                $transferInput[RefundConstants::REVERSE_ALL] = true;

                $data[RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED] = $this->shouldProcessReversals($payment, $transferInput);
            }
            catch (\Throwable $ex)
            {
                // Do nothing in case of exceptions, default value for transfers reversal option is false
                $data[RefundConstants::IS_TRANSFERS_REVERSAL_ALLOWED] = false;
            }
        }

        if (empty($data[RefundConstants::MESSAGES]) === true)
        {
            // By casting the array into an object, json_encode will always use braces instead of brackets for the value (even when empty).
            $data[RefundConstants::MESSAGES] = (object) array();
        }

        return $data;
    }

    protected function processRefund($input = [], $isRefundForAuthorizedPayment = false)
    {
        $payment = $this->refund->payment;

        $data = $this->getGatewayDataForRefund($this->refund, $payment);

        // Load necessary info from input to data
        $this->getAdditionalDataFromInput($data, $input);

        //
        // Taking mutex lock of 10 minutes here. In ideal cases, lock of 1 or 2 minutes works but
        // in cases if any alter query or any other operation is running on refunds table and
        // refund save takes lot more time that expected. For such cases, keeping mutex lock to 10 minutes.
        //
        $this->mutex->acquireAndRelease($payment->getId(), function() use ($isRefundForAuthorizedPayment, $data, $payment)
        {
            if ($payment->isExternal() == false)
            {
                $this->payment->reload();

                $payment = $this->payment;
            }

            if ($payment->isFullyRefunded() === true)
            {
                throw new Exception\InvalidArgumentException(
                    'Can only refund a non-refunded payment but here ' .
                    'the status is ' . $payment->getStatus());
            }

            if ($this->refundAmountValid($this->refund, $payment) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT);
            }

            $this->repo->transaction(function() use ($isRefundForAuthorizedPayment) {
                $this->recordTransactionForRefund();

                // update the payment entity for refund
                $this->updatePaymentRefunded($isRefundForAuthorizedPayment);
            });

            $this->callRefundFunctionOnScrooge($this->refund, $data);

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

    public function refundAmountValid(Payment\Refund\Entity $refundEntity, Payment\Entity $payment) : bool
    {
        $sumOfRefundAmount = $payment->refunds()
            ->whereIn(Payment\Refund\Entity::STATUS, Payment\Refund\Status::REFUND_NON_FAILURE_STATUS)
            ->sum("amount");
        $amountUnrefunded = $payment->getAmount() - $sumOfRefundAmount;

        if ($amountUnrefunded < $refundEntity->getAmount())
        {
            $this->trace->info(
                TraceCode::REFUND_AMOUNT_NOT_VALID,
                [
                    'refund_id'             => $refundEntity->getId(),
                    'payment_id'            => $refundEntity->getPaymentId(),
                    'refund_amount'         => $refundEntity->getAmount(),
                    'sum_of_refund_amount'  => $sumOfRefundAmount
                ]);
            return false;
        }

        return true;
    }

    public function callRefundFunctionOnScrooge($refund, $data = [])
    {
        $this->setRefundReference3IfApplicable($refund->payment);

        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment, $data);

        $refund->setIsScrooge(true);

        $refund->incrementAttempts();

        $this->repo->saveOrFail($refund);

        $data['mode'] = $this->mode;

        try
        {
            $this->app['scrooge']->initiateRefund($data, true);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::REFUND_SYNC_SCROOGE_CALL_FAILED,
                $data
            );

            $this->trace->info(
                TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH,
                $data
            );

            try
            {
                ScroogeRefund::dispatch($data);
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::REFUND_QUEUE_SCROOGE_DISPATCH_FAILED,
                    $data
                );
            }
        }
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
        // TODO: handle for capture queue later
        // refund/reverse on gateway. If capture_queue feature is enabled on a merchant so payment will have txn id
        // even if gateway captured is not set, in that case, reversal request should be sent.
        // If txn id is present and capture queue is not enabled, we will call refund request as that means
        // payment would have been gateway captured.
        //
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

        //
        // Enabling refund retry on created state and initiated state.
        // Refund is stuck in these state means refund is failed at some stage.
        //
        if (($refund->isScrooge() === true) and
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
        $dispatchDelayTime = $input[RefundConstants::DISPATCH_DELAY_TIME] ?? 0;

        // Max. delay supported by SQS job is 900 seconds
        $dispatchDelayTime = min(900, $dispatchDelayTime);

        $data = $this->getGatewayDataForScroogeRefund($refund, $refund->payment, $input);

        $data['mode'] = $this->mode;

        $this->trace->info(
            TraceCode::REFUND_RETRY_QUEUE_SCROOGE_DISPATCH,
            $data
        );

        try
        {
            ScroogeRefundRetry::dispatch($data)->delay($dispatchDelayTime);
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

        $refundedOnGateway = $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($data, $payment, $refund)
            {
                $skipVerify = $data[RefundConstants::SKIP_REFUND_VERIFY] ?? false;

                $refundedOnGateway = $verifyResponse[Payment\Gateway::SUCCESS] = false;

                if ($skipVerify === false)
                {
                    $verifyResponse = $this->verifyRefund($refund, $data);

                    // true  if refunded
                    // false if not refunded
                    $refundedOnGateway = $verifyResponse[Payment\Gateway::SUCCESS];
                }

                if ($refundedOnGateway === true)
                {
                    $refund->setStatusProcessed();

                    $this->setRefundReference1($verifyResponse);
                }
                else if ($refund->isStatusFailed() === true)
                {
                    //
                    // Setting retry to true, will use this action later to decide weather
                    // scrooge refund should be marked as processed or not. If scrooge refunds
                    // are retried, we will mark them as processed otherwise not.
                    // For refunds attempted first time, will be marked processed by Scrooge call.
                    //
                    $refundResponse = $this->callRefundFunction($refund, $payment, $data, true);

                    if ($this->refund->isProcessed() === true)
                    {
                        $this->setRefundReference1($refundResponse);
                    }

                    $refund->incrementAttempts();

                    return $refundResponse[Payment\Gateway::SUCCESS];
                }

                return $verifyResponse[Payment\Gateway::SUCCESS];
            });

        $refund->setGatewayRefunded($refundedOnGateway);

        $this->repo->saveOrFail($refund);
    }

    protected function getAdditionalDataFromInput(&$data, $input)
    {
        // Setting flag for scrooge meta data. When refund is being created but gateway refund is not supported
        $data[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] = $input[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] ?? null;
    }

    protected function gatewaySupportsReversal($payment)
    {
        /**
         * @var $payment Payment\Entity
         */

        // Upi otm payments are allowed reversal when we refund authorized payment
        // i.e. that is a mandate revoke call.
        if ($payment->isUpiOtm() === true)
        {
            return true;
        }

        $gateway = $payment->getGateway();

        $gatewayAcquirer = (is_null($payment->terminal) === false) ? $payment->terminal->getGatewayAcquirer() : null;

        return Payment\Gateway::supportsReverse($gateway, $gatewayAcquirer);
    }

    protected function updatePaymentRefunded($isRefundForAuthorizedPayment = false)
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

        $this->repo->transaction(function() use ($isRefundForAuthorizedPayment) {
            if ($this->payment->isExternal() === true)
            {
                $this->payment->setAttribute(RefundConstants::REFUND_AUTHORIZED_PAYMENT, $isRefundForAuthorizedPayment);
            }
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
     * @param string $type
     *
     * @return bool
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

        $negativeBalanceEnabled = (new BalanceConfig\Core)->isNegativeBalanceEnabledForTxnAndMerchant(Transaction\Type::REFUND);

        if ($merchant->getRefundSource() === RefundSource::CREDITS)
        {
            // Not allowing negative balance in refund credits
            // Ref slack thread: https://razorpay.slack.com/archives/C6XG1F99N/p1651128045835069?thread_ts=1642673759.195000&cid=C6XG1F99N
            return (new Merchant\Balance\Core)->checkMerchantRefundCredits($merchant, -1 * $refund->getNetAmount(),
                                                            Transaction\Type::REFUND, false);
        }

        if ($merchant->getRefundSource() === RefundSource::BALANCE)
        {
            if ((new RefundTransactionProcessor($refund))->isMerchantRefundFallbackEnabled($merchant->getId()) === true)
            {
                //We will check if refund credits are enough and in case they are not we will check for balance
                try
                {
                    $creditsCheck = (new Merchant\Balance\Core)->checkMerchantRefundCredits($merchant, -1 * $refund->getNetAmount(),
                        Transaction\Type::REFUND, false);
                }
                catch (\Throwable $exception)
                {
                    return $this->checkMerchantBalance($merchant, $refund, $type, $traceData, $negativeBalanceEnabled);
                }

                return $creditsCheck;

            }
            else
            {
                return $this->checkMerchantBalance($merchant, $refund, $type, $traceData, $negativeBalanceEnabled);
            }
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

        if (Payment\Gateway::isSequenceNoBasedRefund($payment) === true)
        {
            $data['refund']['reference3'] = payment\Refund\Core::getNewRefundSequenceNumberForPayment($payment);
        }

        return $data;
    }

    public function loadFTADataForScroogeRefund(
        array &$scroogeData, Payment\Refund\Entity $refund, Payment\Entity $payment, array $input = [])
    {
        // Shouldn't enter this flow once instant refund fails and load fta data from input
        if (($refund->isRefundSpeedInstant() === true) and ($refund->getSpeedProcessed() !== RefundSpeed::NORMAL))
        {
            if ($this->isPaymentCardAndCardTransferRefund($refund, $payment, true) === true)
            {
                $cardInput = $this->getCardIdInput($payment, $input);

                if (empty($cardInput) === false)
                {
                    $scroogeData['fta_data']['card_transfer'] = $cardInput;

                    return;
                }
            }

            if ($this->isPaymentUpiAndCardTransferRefund($refund, $payment, true) === true)
            {
                $scroogeData['fta_data']['vpa']['address'] = $payment->getVpa();

                return;
            }

            if ($this->isPaymentNetbankingAndCardTransferRefund($refund, $payment, true) === true)
            {
                $bankAccountInput = $this->fetchBankAccountDetailsForInstantRefund($payment);

                if (empty($bankAccountInput) === false)
                {
                    $scroogeData['fta_data']['bank_account'] = $bankAccountInput;
                }

                return;
            }
        }

        $bankAccountInput = $this->getBankAccountInput($payment, $input);

        if (empty($bankAccountInput) === false)
        {
            $scroogeData['fta_data']['bank_account'] = $bankAccountInput;

            return;
        }

        $vpaInput = $this->getVpaInput($refund, $payment, $input);

        if (empty($vpaInput) === false)
        {
            $scroogeData['fta_data']['vpa'] = $vpaInput;
        }
    }

    public function loadFTADataWithoutRefundEntity(
        array &$scroogeData, Payment\Entity $payment)
    {

        $bankAccountInput = $this->getBankAccountInput($payment);

        if (empty($bankAccountInput) === false)
        {
            $scroogeData[RefundConstants::BANK_ACCOUNT] = $bankAccountInput;

            return;
        }

        if ($this->refundToUpiViaFta($payment) === true)
        {
            $vpaInput = $payment->getVpa();

            if (empty($vpaInput) === false)
            {
                $scroogeData[RefundConstants::VPA] = $vpaInput;

                return;
            }
        }
    }

    protected function getGatewayDataForScroogeRefund(Payment\Refund\Entity $refund, Payment\Entity $payment, array $input = [])
    {
        $refundData = $refund->toArray();

        $gatewayAcquirer = $payment->getGateway();

        //
        //
        // This terminal was deleted due to Yesbank moratorium
        // This particular terminal is not a direct settlement terminal
        // Will be removing this check once the terminal is fixed.
        //
        // Slack thread for reference:
        // https://razorpay.slack.com/archives/CA66F3ACS/p1584100168218900?thread_ts=1584090894.210900&cid=CA66F3ACS
        //
        if (($payment->getTerminalId() !== 'B2K2t8JD9z98vh') and (is_null($payment->terminal) === false))
        {
            $gatewayAcquirer = $payment->terminal->getGatewayAcquirer() ?? $payment->getGateway();
        }

        $extraData = [
            'bank'                      => $payment->getBank(),
            'method'                    => $payment->getMethod(),
            'terminal_id'               => $payment->getTerminalId(),
            'payment_amount'            => $payment->getAmount(),
            'gateway_acquirer'          => $gatewayAcquirer,
            'payment_created_at'        => $payment->getCreatedAt(),
            'payment_base_amount'       => $payment->getBaseAmount(),
            'payment_authorized_at'     => $payment->getAuthorizeTimestamp(),
            'payment_captured_at'       => $payment->getCaptureTimestamp(),
            'payment_service_route'     => $payment->getCpsRoute(),
            'sequence_no'               => $refund->getReference3(),
            'payment_gateway_captured'  => $payment->getGatewayCaptured(),
            'reversal_id'               => $refund->getReversalId(),
        ];

        //
        // Sending payment_gateway_amount in case of DCC as long as its greater than 0
        //
        if (($payment->isDCC() === true) and
            ($payment->paymentMeta !== null) and
            ($payment->paymentMeta->getGatewayAmount() > 0))
        {
            $extraData['payment_gateway_amount'] = $payment->paymentMeta->getGatewayAmount();
        }

        if ($payment->isUpiAndAmountMismatched() === true )
        {
            $this->trace->info(
                TraceCode::RECON_AMOUNT_MISMATCH_PAYMENT,
                [
                    'payment_id'        => $payment->getId(),
                    'payment_amount'    => $payment->getAmount(),
                    'gateway_amount'    => $payment->paymentMeta->getGatewayAmount(),
                    'mismatch_amount'   => $payment->paymentMeta->getMismatchAmount(),
                    'mismatch_reason'   => $payment->paymentMeta->getMismatchAmountReason(),
                    'gateway'           => $payment->getGateway()
                ]);

            $extraData['payment_gateway_amount'] = $payment->paymentMeta->getGatewayAmount();
        }

        if ($payment->isAppCred() === true)
        {
            $extraData['payment_gateway_amount'] = $payment->getDiscountedAmountIfApplicable();
        }

        if ($payment->isHdfcVasDSCustomerFeeBearerSurcharge() === true)
        {
            $extraData['payment_gateway_amount'] = $payment->paymentMeta->getGatewayAmount();
        }

        // Speed decisioned is being sent as speed_requested.
        // Once switchover happens on scrooge, we will send right value in speed requested
        //
        // UPDATE - SPEED_REQUESTED and SPEED_DECISIONED are being sent against respective labels.
        // Data coming from refund entity

        // Flag for skipping verify call on scrooge when retrying refund
        $refundData[RefundConstants::SKIP_REFUND_VERIFY] = $input[RefundConstants::SKIP_REFUND_VERIFY] ?? false;

        $scroogeData = array_merge($refundData, $extraData);

        if (isset($input['refund'][RefundEntity::MODE_REQUESTED]) === true)
        {
            $scroogeData[RefundEntity::MODE_REQUESTED] = $input['refund'][RefundEntity::MODE_REQUESTED];
        }

        $this->loadFTADataForScroogeRefund($scroogeData, $refund, $payment, $input);

        $metaData = $this->getMetaDataOfRefund($payment, $input);

        //Setting the direct settlement refund key in the meta
        $metaData[Constants::DIRECT_SETTLEMENT_WITH_REFUND] = $refund->isDirectSettlementRefund();

        $scroogeData[Constants::META_DATA] = $metaData;

        //
        // These attributes are already in scrooge, need to be reset before sending scrooge request
        //
        unset($scroogeData['status']);

        return $scroogeData;
    }

    protected function getMetaDataOfRefund(Payment\Entity $payment, $input = [])
    {
        $headers = $this->ba->getDashboardHeaders();

        $initiatorEmailId = null;

        if (isset($headers[Constants::ADMIN_EMAIL]) === true)
        {
            $initiatorEmailId = $headers[Constants::ADMIN_EMAIL];
        }
        else if (isset($headers[Constants::USER_EMAIL]) === true)
        {
            $initiatorEmailId = $headers[Constants::USER_EMAIL];
        }

        $isBatch = (($this->ba->isBatchFlow() === true) or ($this->ba->isBatchApp() === true));

        $metaData = [
            Constants::INITIATOR_EMAIL_ID           => $initiatorEmailId,
            Constants::IS_CRON                      => $this->ba->isCron(),
            Constants::IS_DASHBOARD_APP             => $this->ba->isDashboardApp(),
            Constants::ROUTE_NAME                   => $this->route->getCurrentRouteName(),
            Constants::IS_BATCH                     => $isBatch,
            Constants::CREATOR_ID                   => $this->request->header(RequestHeader::X_Creator_Id) ?? null,
            Constants::CREATOR_TYPE                 => $this->request->header(RequestHeader::X_Creator_Type) ?? null,
            Constants::IS_PAYMENT_CAPTURED          => (empty($payment->getCapturedAt()) === false),
            Constants::IS_ADMIN_AUTH                => $this->ba->isAdminAuth(),
            Constants::IS_PAYMENT_AMOUNT_MISMATCH   => $payment->isUpiAndAmountMismatched(),
            Constants::PAYMENT_SETTLED_BY           => $payment->getSettledBy()
        ];

        if ($payment->getGateway() === Payment\Gateway::NETBANKING_ICICI)
        {
            $metaData[Constants::PAYMENT_REFERENCE1] = $payment->getReference1();
        }

        if (empty($input[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND]) === false)
        {
            $metaData[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND] = $input[RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND];
        }

        return $metaData;
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

    public function refundCapturedPayment($payment, array $input = [], Batch\Entity $batch = null, $batchID = null, $variant = null)
    {
        if (($this->isNonMerchantRefundRequestV1_1($payment) === true) and
            ($batch === null) and ($batchID === null) and ($variant !== 'off'))
        {
            $this->trace->info(
                TraceCode::REFUND_FROM_CAPTURED_REQUEST_SCROOGE,
                [
                    'payment_id' => $payment->getId(),
                    'input'      => $input,
                ]);
            // For captured payments, refund amount either needs to be defined in $input params, or
            // by default refund amount will be full payment amount.
            // No need to override refund amount here.
            // Route refund creation to scrooge
            return $this->newRefundV2Flow($payment, $input);
        }

        $variant = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                Merchant\RazorxTreatment::DUPLICATE_RECEIPT_CHECK,
                $this->mode
        );

        if (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON)
        {
            $this->checkForDuplicateReceipt($payment, $input);
        }


        $this->validatePaymentForRefund($payment, $input);

        // Captured payments of transfer cannot be refunded via direct API requests
        if ($payment->isTransfer() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED);
        }

        // unsetting to avoid validation failures during transfer reversal creation
        if (isset($input[RefundConstants::UNDISPUTED_PAYMENT]))
        {
            $unDisputedPayment = $input[RefundConstants::UNDISPUTED_PAYMENT];
            unset($input[RefundConstants::UNDISPUTED_PAYMENT]);
        }
        else
        {
            $unDisputedPayment = false;
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

        return $this->refund($payment, $input, $batch, $batchID, $unDisputedPayment);
    }

    protected function checkForDuplicateReceipt(Payment\Entity $payment, array $input = [])
    {
        $receiptFromInput = $input[RefundEntity::RECEIPT] ?? null;
        $receiptFromTable = (empty($receiptFromInput) === false) ? (Payment\Refund\Entity::whereRaw('receipt = ? and merchant_id = ?',
                                            [$receiptFromInput, $this->merchant->getId()])->exists()) : false;

        if ((empty($receiptFromInput) === false) and ($receiptFromTable === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REFUND_RECEIPT_ALREADY_PRESENT, null, ['method' => $payment->getMethod()]);
        }
    }

    protected function validatePaymentForRefund(Payment\Entity $payment, array $input = null)
    {
        if ($payment->isFullyRefunded() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_REFUNDED);
        }

        if ($payment->isPos() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED);
        }

        if (($this->getPaymentRefundType($input, $payment) === Payment\RefundStatus::PARTIAL) and
            (in_array($payment->getGateway(), Payment\Gateway::$partialRefundDisabledGateways) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PARTIAL_REFUND_NOT_SUPPORTED,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'gateway'    => $payment->getGateway(),
                ]
            );
        }

        if ($payment->isCaptured() === false)
        {
            if ($this->merchant->isFeatureEnabled(Feature::VOID_REFUNDS) === true)
            {
                if ($this->getPaymentRefundType($input, $payment) === Payment\RefundStatus::PARTIAL)
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
            else if ($payment->isUpiOtm() === true and $payment->isAuthorized() === true)
            {
                // In case of UPI OTM Payments, For revoking the mandate, we call the refund api on authorized payment
                // We also validate that, to not support partial refund on the revoke call.
                if ($this->getPaymentRefundType($input, $payment) === Payment\RefundStatus::PARTIAL)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_REFUND_PARTIAL_VOID_NOT_SUPPORTED);
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

    public function eventRefundProcessed(RefundEntity $refund)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $refund,
        ];

        $this->app['events']->dispatch('api.refund.processed', $eventPayload);
    }

    public function eventRefundCreated(RefundEntity $refund)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $refund,
        ];

        $this->app['events']->dispatch('api.refund.created', $eventPayload);
    }

    public function eventRefundFailed(RefundEntity $refund)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $refund,
        ];

        $this->app['events']->dispatch('api.refund.failed', $eventPayload);
    }

    public function eventRefundSpeedChanged(RefundEntity $refund)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $refund,
        ];

        $this->app['events']->dispatch('api.refund.speed_changed', $eventPayload);
    }

    public function eventRefundArnUpdated(RefundEntity $refund)
    {
        // Can add merchant webhooks/customer notifications here
        // which are supposed to be triggered when refund ARN gets updated

        // Triggering email notifications on refund arn update
        $notifier = new Notify($refund->payment);

        $notifier->addRefund($refund);

        $notifier->trigger(Payment\Event::REFUND_RRN_UPDATED);

        // Triggering webhook
        $eventPayload = [
            ApiEventSubscriber::MAIN => $refund,
        ];

        $this->app['events']->dispatch('api.refund.arn_updated', $eventPayload);
    }

    protected function refundViaFundTransfer(RefundEntity $refund, Payment\Entity $payment, $data = []): array
    {
        $scroogeResponse  = new ScroogeResponse();

        if ($this->refund->getAmount() === 0)
        {
            $scroogeResponse->setSuccess(true);

            return $scroogeResponse->toArray();
        }

        $refunded = false;

        // Initializing for non scrooge refunds
        $data[RefundConstants::IS_FTA] = $data[RefundConstants::IS_FTA] ?? false;

        try
        {
            $fundTransferAttemptInput = $this->getFundTransferAttemptInput($payment, $data);

            if (isset($data[RefundConstants::VPA]) === true)
            {
                $fta = $this->refundViaFundTransferToVpa($data, $fundTransferAttemptInput);
            }
            // If bank account details are given - we need to refund to bank account instead of card transfer
            else if (isset($data[RefundConstants::CARD_TRANSFER]) === true)
            {
                $fta = $this->refundViaFundTransferToCard($payment, $data, $fundTransferAttemptInput);
            }
            else
            {
                $fta = $this->refundViaFundTransferToBankAccount($payment, $refund, $data, $fundTransferAttemptInput);
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
            $this->createAndAssociateVpa($input);

            $fta = (new FundTransferAttempt\Core)->createWithVpa($this->refund,
                                                                 $this->refund->vpa,
                                                                 $fundTransferAttemptInput,
                                                                 true);


            return $fta;
        });
    }

    protected function refundViaFundTransferToBankAccount(Payment\Entity $payment,
                                                          RefundEntity $refund,
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

            $this->createAndAssociateBankAccount($bankAccountInput);

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
            $card = $payment->card;

            $token = $payment->getGlobalOrLocalTokenEntity();

            if ((is_null($token) === false) and ($token->hasCard() === true))
            {
                // ToDo : Add trivia and token status checks when params are properly populated
                // Enabling tokenisation flow only for visa network, debit cards and CT mode in this phase
                if (($token->card->getVault() === 'visa') and
                    ($token->card->getType() === Type::DEBIT) and
                    ($fundTransferAttemptInput[FundTransferAttempt\Entity::MODE] === FundTransfer\Mode::CT))
                {
                    $card = $token->card;

                    $this->trace->info(
                        TraceCode::REFUND_FTA_VIA_TOKENISED_FLOW,
                        [
                            Payment\Entity::CARD_ID            => $card->getId(),
                            Payment\Entity::TOKEN_ID           => $token->getId(),
                            Payment\Refund\Entity::PAYMENT_ID  => $payment->getId(),
                            Payment\Refund\Entity::MERCHANT_ID => $payment->getMerchantId(),
                        ]
                    );
                }
            }

            return (new FundTransferAttempt\Core)->createWithCard($this->refund,
                                                                  $card,
                                                                  $fundTransferAttemptInput,
                                                                  true);
        });
    }

    protected function isFundTransferAttemptRefund(RefundEntity $refund, Payment\Entity $payment, array $data = []): bool
    {
        if (isset($data[RefundConstants::IS_FTA]) === true)
        {
            if ($data[RefundConstants::IS_FTA] === false)
            {
                return false;
            }
        }
        else
        {
            $data[RefundConstants::IS_FTA] = false;
        }

        // For API refunds we are handling checks on processing refund via FTA
        // For scrooge refunds we believe the is_fta flag sent to API
        if ($refund->isScrooge() === false)
        {
            if ($refund->isDirectSettlementRefund() === true)
            {
                return false;
            }
        }

        //
        // Refund is explicitly being attempted towards a new bank account or vpa
        // Bank account or vpa input can come from dashboard also, but card_transfer will not come from dashboard.
        // Not keeping check for card_transfer so that every time, we will evaluate if it is card_transfer refund.
        //
        if (((isset($data[RefundConstants::BANK_ACCOUNT]) === true) or
             (isset($data[RefundConstants::VPA]) === true) or
             (isset($data[RefundConstants::CARD_TRANSFER]) === true)) and
            ($payment->isGatewayCaptured() === true))
        {
            return true;
        }

        // Certain types of payments have refunds routed via fund transfers
        return $this->refundToBankAccountViaFta($payment);
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

    protected function isPaymentNachAndNachRefundGateway(Payment\Entity $payment): bool
    {
        if (($payment->isNach() === true) and
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

    protected function isPaymentNetbankingOrderAccountDetailsAvailableAndNonTpvBankTransferRefund(Payment\Entity $payment): bool
    {
        if (($payment->isNetbanking() === true) and
            ($payment->isGatewayCaptured() === true) and
            ($payment->hasOrder() === true) and
            (empty($payment->order->getAccountNumber()) === false) and
            (empty($payment->order->getBank()) === false) and
            ($this->merchant->isFeatureEnabled(Feature::NON_TPV_BT_REFUND) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * @param Payment\Entity $payment
     * @return bool
     */
    protected function isPaymentUpiTransferAndUpiTransferRefund(Payment\Entity $payment): bool
    {
        if (($payment->isUpiTransfer() === true) and
            (empty($payment->hasReceiver()) === false) and
            (empty($payment->upiTransfer) === false) and
            ($this->merchant->isFeatureEnabled(Feature::VIRTUAL_ACCOUNTS) === true) and
            (Payment\Gateway::isValidUpiTransferGateway($payment->getGateway())) and
            (in_array($payment->getGateway(), Payment\Gateway::UPI_TRANSFER_REFUND_GATEWAYS, true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * @param Payment\Entity $payment
     * @param RefundEntity $refund
     * @param bool $lateAuthVoidRefundInstantSpeed
     * @return bool
     * @throws \Exception
     */
    protected function isInstantRefundsSupportedRefund(Payment\Entity $payment, RefundEntity $refund, bool $lateAuthVoidRefundInstantSpeed): bool
    {
        return (($refund->isRefundRequestedSpeedInstant() === true) and
                ($payment->hasBeenCaptured() === true) and
                ($payment->isDCC() === false) and
                ((in_array($payment->getMethod(), [
                    Payment\Method::CARD,
                    Payment\Method::UPI,
                    Payment\Method::NETBANKING], true) === true) and
                 (($this->isPaymentCardAndCardTransferRefund($refund, $payment) === true) or
                  ($this->isPaymentNetbankingAndCardTransferRefund($refund, $payment) === true) or
                  ($this->isPaymentUpiAndCardTransferRefund($refund, $payment) === true))));
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
        // proceeding only if decisioned speed is OPTIMUM
        if ($refund->getSpeedRequested() !== Payment\Refund\Speed::OPTIMUM)
        {
            return false;
        }
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
            $cardEntityArray = $payment->card->toArrayRefund();

            if (isset($cardEntityArray[RefundConstants::IIN]) === true)
            {
                $cardType = strtolower($payment->card->getType());

                $cardIssuer = $payment->card->getIssuer();

                if (($cardType === Type::CREDIT) and
                    (in_array($cardIssuer, FundTransfer\Mode::getSupportedIssuers(), true) === true) and
                    (IIN::isIinPrepaid($cardEntityArray[RefundConstants::IIN]) === false))
                {
                    if (($ignoreFeatureFlag === false) and
                        ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === true))
                    {
                        return false;
                    }

                    return true;
                }
                else if (($cardType === Type::DEBIT) and
                         ($refund->isRefundRequestedSpeedInstant() === true))
                {
                    //
                    // Not ignoring feature flag
                    //
                    if ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === true)
                    {
                        return false;
                    }

                    //
                    // Debit card mode decisioning will be done at Scrooge,
                    // after fetching supported modes from FTS
                    //

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checking if a upi payment is valid to be refunded by VPA instantly.
     * If card_transfer_refund feature is present for the merchant,
     * refund will be made on VPA.
     * Payment should be gateway captured, if it isn't, it should not be refunded directly via FTA.
     *
     * @param RefundEntity $refund
     * @param Payment\Entity $payment
     * @param bool $ignoreFeatureFlag
     * @return bool
     * @throws \Exception
     */
    protected function isPaymentUpiAndCardTransferRefund(
        RefundEntity $refund,
        Payment\Entity $payment,
        bool $ignoreFeatureFlag = false): bool
    {
        //
        // Check if any upi FTA already exists, not allowing upi fta if any previous upi fta exists
        //
        foreach ($refund->fundTransferAttempts as $fundTransferAttempt)
        {
            if (empty($fundTransferAttempt->getVpaId()) === false)
            {
                return false;
            }
        }

        if (($payment->getMethod() === Payment\Method::UPI) and
            (empty($payment->getVpa()) === false) and
            ($payment->isGatewayCaptured() === true))
        {
            if (($ignoreFeatureFlag === false) and
                ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === true))
            {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Checking if payment is of method netbanking. If card_transfer_refund feature is present for the merchant,
     * refund will be made on bank account details which may come next day in recon (Gateway specific).
     * Payment should be gateway captured, if it isn't, it should not be refunded directly via FTA.
     *
     * @param RefundEntity $refund
     * @param Payment\Entity $payment
     * @param bool $ignoreFeatureFlag
     * @return bool
     * @throws \Exception
     */
    protected function isPaymentNetbankingAndCardTransferRefund(
        RefundEntity $refund,
        Payment\Entity $payment,
        bool $ignoreFeatureFlag = false): bool
    {
        //
        // Check if any FTA to bank account already exists, not allowing fta to
        // bank account if any previous bank account fta exists. This is just a sanity check
        // and should not affect the flow for manual retry via bank account
        //
        foreach ($refund->fundTransferAttempts as $fundTransferAttempt)
        {
            if (empty($fundTransferAttempt->getBankAccountId()) === false)
            {
                return false;
            }
        }

        if (($payment->getMethod() === Payment\Method::NETBANKING) and
            ($payment->isGatewayCaptured() === true))
        {
            if (($ignoreFeatureFlag === false) and
                ($this->merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === true))
            {
                return false;
            }

            return true;
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

        $input['vault_token'] = $payment->card->getVaultToken();

        return $input;
    }

    protected function getBankAccountInput(Payment\Entity $payment, array $data = [])
    {
        $input = [];

        if ($payment->isDirectSettlementRefund() === true)
        {
            // Not allowing Fund Transfers on direct settlement with refund terminals
            return [];
        }

        if (isset($data['bank_account']) === true)
        {
            $input = $data['bank_account'];
        }
        else if ($this->refundToBankAccountViaFta($payment) === true)
        {
            if ($payment->isBankTransfer() === true)
            {
                $paymentId = $payment->getId();

                // https://github.com/razorpay/api/pull/9612/files#diff-45d61a7b834fae07d62a86dd461e5940R1697
                if ($paymentId === 'AQNG7kHM5tfk4G')
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                        $input);
                }

                if ($payment->qrPayment !== null)
                {
                    $qrPayment = $payment->qrPayment;

                    $input = (new QrPayment\Core())->getAccountForRefund($qrPayment);
                }
                else
                {
                    $bankTransfer = $this->repo->bank_transfer->findByPaymentId($paymentId);

                    $input = (new BankTransfer\Core)->getAccountForRefund($bankTransfer);
                }
            }
            else if (($this->isPaymentTpvAndBankTransferRefund($payment) === true) or
                     ($this->isPaymentNetbankingOrderAccountDetailsAvailableAndNonTpvBankTransferRefund($payment) === true))
            {
                $order = $payment->order;

                $input = (new Order\Core)->getAccountForRefund($order);
            }
            else if (($this->isPaymentEmandateAndEmandateRefundGateway($payment) === true) or
                ($this->isPaymentNachAndNachRefundGateway($payment) === true))
            {
                $customerName = $this->getFormattedCustomerNameFromPayment($payment);

                $token = $payment->getGlobalOrLocalTokenEntity();

                $input[BankAccount\Entity::IFSC_CODE]          = $token->getIfsc();
                $input[BankAccount\Entity::ACCOUNT_NUMBER]     = $token->getAccountNumber();
                $input[BankAccount\Entity::BENEFICIARY_NAME]   = $customerName;
            }
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

    protected function refundToBankAccountViaFta(Payment\Entity $payment) : bool
    {
        return (($payment->isBankTransfer() === true) or
            ($this->isPaymentTpvAndBankTransferRefund($payment) === true) or
            ($this->isPaymentNetbankingOrderAccountDetailsAvailableAndNonTpvBankTransferRefund($payment) === true) or
            ($this->isPaymentEmandateAndEmandateRefundGateway($payment) === true) or
            ($this->isPaymentNachAndNachRefundGateway($payment) === true));
    }

    protected function getFormattedCustomerNameFromPayment(Payment\Entity $payment)
    {
        $customer = $payment->customer;

        if ($customer === null)
        {
            return null;
        }

        $customerName = preg_replace('/[^a-zA-Z0-9 ]+/', '', $customer->getName());

        return substr($customerName, 0, 35);
    }

    protected function getVpaInput(Payment\Refund\Entity $refund, Payment\Entity $payment, array $data = [])
    {
        $input = null;

        if ($refund->isDirectSettlementRefund() === true)
        {
            // Not allowing Fund Transfers on direct settlement with refund terminals
            return $input;
        }

        if (isset($data[RefundConstants::VPA]) === true)
        {
            $input = $data[RefundConstants::VPA];

            return $input;
        }

        if ($this->refundToUpiViaFta($payment) === true)
        {
            $input[RefundConstants::VPA_ADDRESS] = $payment->getVpa();

            return $input;
        }

        // If gateway is not functioning or for any other reason product takes a call to route traffic
        // of a UPI gateway via FTA
        // This is applicable only for 1 automated FTA attempt only.
        if ($this->isPaymentUpiAndCardTransferRefund($refund, $payment, false) === true)
        {
            // This supports routing on gateway, gateway and merchant level
            $featureFlag = $payment->getGateway() . '_' . RefundConstants::RAZORX_KEY_REFUND_ROUTE_VIA_FTA_SUFFIX;

            $gatewayVariant = $this->app->razorx->getTreatment(
                $payment->getMerchantId(),
                $featureFlag,
                $this->mode
            );

            if (strtolower($gatewayVariant) === RefundConstants::RAZORX_VARIANT_ON)
            {
                $input[RefundConstants::VPA_ADDRESS] = $payment->getVpa();

                return $input;
            }

            // This supports routing on terminal, terminal and merchant level
            // one terminal is specific to one gateway
            $featureFlag = $payment->getTerminalId() . '_' . RefundConstants::RAZORX_KEY_TERMINAL_REFUNDS_ROUTE_VIA_FTA_SUFFIX;

            $terminalVariant = $this->app->razorx->getTreatment(
                $payment->getMerchantId(),
                $featureFlag,
                $this->mode
            );

            if (strtolower($terminalVariant) === RefundConstants::RAZORX_VARIANT_ON)
            {
                $input[RefundConstants::VPA_ADDRESS] = $payment->getVpa();

                return $input;
            }
        }

        return $input;
    }

    protected function refundToUpiViaFta(Payment\Entity $payment): bool
    {
        if ($payment->isUpi() === true)
        {
            // Processing refunds for upi otm payments via fta since gateways dont support refund flows yet
            if ($this->isPaymentCapturedAndUpiOtmGateway($payment) === true)
            {
                return true;
            }

            // Processing refunds for upi recurring payments via fta since gateways dont support refund flows yet
            if ($this->isPaymentAndGatewayUpiRecurring($payment) === true)
            {
                return true;
            }

            if ($this->isPaymentUpiTransferAndUpiTransferRefund($payment) === true)
            {
                return true;
            }
        }

        return false;
    }

    public function refundViaFtaOnly(Payment\Entity $payment): bool
    {
        return (($this->refundToUpiViaFta($payment) === true) or
            ($this->refundToBankAccountViaFta($payment) === true));
    }

    protected function isPaymentCapturedAndUpiOtmGateway(Payment\Entity $payment): bool
    {
        if (($payment->isUpi() === true) and
            ($payment->isGatewayCaptured() === true) and
            (Payment\Gateway::isUpiOtmSupportedGateway($payment->getGateway()) === true))
        {
            $upiMetadataEntity = $this->repo->upi_metadata->fetchByPaymentId($payment->getId());

            if ($upiMetadataEntity[UpiMetadata\Entity::TYPE] === UpiMetadata\Type::OTM)
            {
                return true;
            }
        }

        return false;
    }

    protected function isPaymentAndGatewayUpiRecurring(Payment\Entity $payment): bool
    {
        if (($payment->isUpi() === true) and
            ($payment->isRecurring() === true) and
            (Payment\Gateway::isUpiRecurringSupportedGateway($payment->getGateway()) === true))
        {
            return true;
        }

        return false;
    }

    protected function getFundTransferAttemptInput(Payment\Entity $payment, $data = []): array
    {
        $defaultRefundNarration = $this->getDefaultRefundFundTransferAttemptNarration($payment, $data);

        $input = [
            FundTransferAttempt\Entity::NARRATION => $defaultRefundNarration,
            FundTransferAttempt\Entity::MODE      => null,
        ];

        if (isset($data[RefundEntity::MODE]) === true)
        {
            $input[FundTransferAttempt\Entity::MODE] = $data[RefundEntity::MODE];

            // If mode is NEFT or RTGS we need to set initiate_at using RTGS timings
            if (in_array(
                $input[FundTransferAttempt\Entity::MODE],
                [
                    FundTransfer\Mode::NEFT,
                    FundTransfer\Mode::RTGS
                ],
                true) and
               $this->isValidTiming($input[FundTransferAttempt\Entity::MODE]) === false)
            {
                $input[FundTransferAttempt\Entity::INITIATE_AT] = $this->getFTAInitiateTime();
            }
        }

        if ($payment->isBankTransfer() === true)
        {
            if ($payment->qrPayment !== null)
            {
                $bankTransfer = $payment->qrPayment;
            }
            else
            {
                $bankTransfer = $this->repo->bank_transfer->findByPayment($payment);
            }

            $input = [
                FundTransferAttempt\Entity::NARRATION => $bankTransfer->getRefundNarration(),
                // This is not used anywhere. Not sure why is this even here. Commenting out for now.
                // FundTransferAttempt\Entity::MODE      => strtoupper($bankTransfer->getMode()),
            ];
        }

        return $input;
    }

    protected function getMcSpecificNarrationForCt(Payment\Entity $payment): string
    {
        $merchant = $payment->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Max. chars allowed is 22. Alphanumeric. For refunds, merchant name followed by payment reference number
        // Remove all characters other than a-z, A-Z, 0-9 (alphanumeric)
        // If formattedLabel is non-empty, pick the first 8 chars, else fallback to 'Razorpay'
        // Append 14 char razorpay payment id
        $formattedLabel = preg_replace('/[^a-zA-Z0-9]+/', '', $merchantBillingLabel) ? : 'Razorpay';

        $formattedLabel = Str::limit($formattedLabel, 8, '');

        return $formattedLabel . $payment->getId();
    }

    protected function getDefaultRefundFundTransferAttemptNarration(Payment\Entity $payment, array $data): string
    {
        // For fund transfers via m2p channel & mode CT, Mastercard has specific requirements on narration
        if ((isset($data[RefundEntity::MODE]) === true) and
            ($data[RefundEntity::MODE] === FundTransfer\Mode::CT) and
            (($payment->card->isMasterCard() === true) or ($payment->card->isMaestro() === true)))
        {
            return $this->getMcSpecificNarrationForCt($payment);
        }

        $merchant = $payment->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        //
        // Remove all characters other than a-z, A-Z, 0-9 and space
        // If formattedLabel is non-empty, pick the first 24 chars, else fallback to 'Razorpay'
        //
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel) ? : 'Razorpay';

        $formattedLabel = Str::limit($formattedLabel, 24, '');

        return $formattedLabel . ' Refund ' . $payment->getId();
    }

    protected function isValidTiming($mode)
    {
        $currentTimeInstance = Carbon::now(Timezone::IST);

        $currentTime = $currentTimeInstance->getTimestamp();

        $bankingStartTime = Carbon::createFromTime(FTS\Constants::RTGS_CUTOFF_HOUR_MIN, 15, 0, Timezone::IST)
                                   ->getTimestamp();

        $cutOffHour = ($mode === FundTransfer\Mode::NEFT) ?
                            FTS\Constants::NEFT_CUTOFF_HOUR_MAX : FTS\Constants::RTGS_REVISED_CUTOFF_HOUR_MAX;

        $cutOffMin  = ($mode === FundTransfer\Mode::NEFT) ?
                            FTS\Constants::NEFT_CUTOFF_MINUTE_MAX : FTS\Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX;

        $bankingCloseTime = Carbon::createFromTime($cutOffHour, $cutOffMin, 0, Timezone::IST)
                                   ->getTimestamp();

        if (($currentTime >= $bankingStartTime) and
            ($currentTime <= $bankingCloseTime) and
            (SettlementHoliday::isWorkingDay($currentTimeInstance)))
        {
            return true;
        }

        return false;
    }

    protected function getFTAInitiateTime(): int
    {
        $currentTimeInstance = Carbon::now(Timezone::IST);
        $currentTime = $currentTimeInstance->getTimestamp();

        $bankingStartTime = Carbon::createFromTime(FTS\Constants::RTGS_CUTOFF_HOUR_MIN, 15, 0, Timezone::IST)
                                  ->getTimestamp();

        if (($currentTime < $bankingStartTime) and
            (SettlementHoliday::isWorkingDay($currentTimeInstance)))
        {
            return $bankingStartTime;
        }

        else
        {
            return (SettlementHoliday::getNextWorkingDay(Carbon::now(Timezone::IST))
                                   ->addHours(FTS\Constants::RTGS_CUTOFF_HOUR_MIN)
                                   ->addMinutes(15)
                                   ->getTimestamp());
        }
    }

    protected function createAndAssociateVpa(array $vpaInput)
    {
        $vpa = (new Vpa\Core)->createForSource($vpaInput, $this->refund);

        $this->refund->vpa()->associate($vpa);
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
    }

    /**
     * Currently saving reference number sent by bank in refund response only for UPI and Cardless Emi refunds.
     *
     * @param array $response
     */
    protected function setRefundReference1(array $response)
    {
        $reference1 = $response[Payment\Gateway::GATEWAY_KEYS][RefundEntity::RRN] ?? null;

        if ((in_array($this->refund->payment->getMethod(), $this->getMethodsToSetRefundReference1(), true)) and
            ($this->isValidArn($reference1) === true) and
            (empty($this->refund->getReference1()) === true))
        {
            // To be deprecated later
            $this->updateReference1AndTriggerEventArnUpdated($this->refund, $reference1, false);
        }
    }

    /**
     *
     * Store sequence count of refund for a particular payment. This would be the order in which the refunds were
     * created for a particular payment. Only applicable to sbi netbanking gateway as of now.
     * @param $payment
     *
     */
    protected function setRefundReference3IfApplicable($payment)
    {
        if (Payment\Gateway::isSequenceNoBasedRefund($payment) === true)
        {
            $seqNo = Payment\Refund\Core::getNewRefundSequenceNumberForPayment($payment);

            $this->refund->setReference3($seqNo);
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

    protected function getRefundCreateData(Payment\Entity $payment, RefundEntity $refund = null)
    {
        //
        // Calling Scrooge for speed decisioning and mode selection
        // Scrooge calculates the mode based on the mode configuration defined by
        // product/merchant in consultation with support for modes from FTA/FTS
        //

        $queryParams = [
            RefundEntity::PAYMENT_ID               => $payment->getId(),
            RefundEntity::GATEWAY                  => $payment->getGateway(),
            RefundConstants::METHOD                => $payment->getMethod(),
            RefundConstants::MERCHANT_ID           => $payment->getMerchantId(),
            RefundConstants::PAYMENT_CREATED_AT    => strval($payment->getCreatedAt()),
            RefundConstants::MERCHANT_ACTIVATED_AT => strval($payment->merchant->getActivatedAt()),
            RefundConstants::FUNDS_ON_HOLD         => strval($payment->merchant->isFundsOnHold()),
        ];

        $queryParams = $this->setCardTokenDetailsIfApplicable($payment, $queryParams);

        // setting default amount when actual refund amount is not known yet
        // and scrooge always expects this parameter in request
        $queryParams[RefundConstants::AMOUNT] = (empty($refund) === false) ? $refund->getBaseAmount() :
            RefundConstants::DEFAULT_REFUND_AMOUNT_FOR_MODE_DECISIONING;

        if ($payment->getMethod() === Payment\Method::CARD)
        {
            //
            // The following checks have already been made in scrooge. Keeping these for sanity.
            // Therefore, Scrooge must not send a validation error.
            //

            if (($payment->hasCard() === true) and
                (empty($payment->card->getIssuer()) === false) and
                (empty($payment->card->getType()) === false))
            {
                $queryParams[RefundConstants::NETWORK_CODE] = $payment->card->getNetworkCode();
                $queryParams[RefundConstants::ISSUER] = $payment->card->getIssuer();
                $queryParams[RefundConstants::CARD_TYPE] = strtolower($payment->card->getType());
                $queryParams[RefundConstants::INTERNATIONAL] = strval($payment->card->isInternational());

                $cardEntityArray = $payment->card->toArrayRefund();
                if (isset($cardEntityArray[RefundConstants::IIN]) === true)
                {
                    $queryParams[RefundConstants::BIN] = $cardEntityArray[RefundConstants::IIN];
                }
            }
        }
        else if ($payment->getMethod() === Payment\Method::UPI)
        {
            $queryParams[RefundConstants::VPA] = $payment->getVpa();
        }

        $scroogeResponse = $this->app['scrooge']->fetchRefundCreateData($queryParams);

        // For refund_aged_payments feature enabled merchants, we do not want to block refund creation
        // so making gateway_refund_support true so that refund will be always created atleast with normal speed
        if ($this->merchant->isFeatureEnabled(Feature::REFUND_AGED_PAYMENTS) === true)
        {
            $scroogeResponse[RefundConstants::GATEWAY_REFUND_SUPPORT] = true;
        }

        return $scroogeResponse;
    }

    public function fetchBankAccountDetailsForInstantRefund(Payment\Entity $payment) : array
    {
        $netbankingEntity = $payment->netbanking;

        if (empty($netbankingEntity) === true)
        {
            return [];
        }

        $accountNo = $netbankingEntity->getAccountNumber();
        $trimmedAccountNo = trim($accountNo);

        $ifscCode  = BankCodes::getIfscForBankCode($payment->getBank());

        if ((empty($trimmedAccountNo) === true) or (empty($ifscCode) === true))
        {
            return [];
        }

        // Todo: Confirm source of customer_name if it is same as bene name and send to scrooge. Needed for NEFT
        return [
            BankAccount\Entity::IFSC_CODE => $ifscCode,
            BankAccount\Entity::ACCOUNT_NUMBER => $trimmedAccountNo,
        ];
    }

    private function checkMerchantBalance(Merchant\Entity $merchant,
                                          RefundEntity $refund,
                                          string $type,
                                          array $traceData,
                                          bool $negativeBalanceEnabled = false)
    {
        try
        {
            return (new Merchant\Balance\Core)->checkMerchantBalance($merchant, -1 * $refund->getNetAmount(),
                                                        Transaction\Type::REFUND, $negativeBalanceEnabled);
        }
        catch (\Throwable $e)
        {
            if ($type === 'refund')
            {
                if ($e->getCode() === ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED)
                {
                    $error = ErrorCode::BAD_REQUEST_NEGATIVE_BALANCE_BREACHED;
                }
                else
                {
                    if((new RefundTransactionProcessor($refund))->isMerchantRefundFallbackEnabled($merchant->getId()) === true)
                    {
                        $error = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE_FALLBACK;
                    }
                    else
                    {
                        $error = ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_BALANCE;
                    }
                }

                $this->app['segment']->trackPayment(
                    $refund->payment,
                    TraceCode::PAYMENT_REFUND_FAILURE,
                    $traceData);
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

        return;
    }

    public function isValidArn($arn)
    {
        if ((empty($arn) === false) and (is_string($arn) === true) and (strlen($arn) > 2))
        {
            return true;
        }

        return false;
    }

    public function updateReference1AndTriggerEventArnUpdated(RefundEntity & $refund, $reference1, $triggerEvent = true)
    {
        $refund->setReference1($reference1);

        if ($triggerEvent === true)
        {
            $this->eventRefundArnUpdated($refund);
        }
    }

    private function setCardTokenDetailsIfApplicable(Payment\Entity $payment, array $queryParams): array
    {
        if (($payment->getMethod() === Method::CARD) && ($payment->hasCard() === true))
        {
            $cardEntity = $payment->card->toArrayRefund();
            if (isset($cardEntity[RefundConstants::TOKENIZED]) === true)
            {
                $queryParams[RefundConstants::TOKENIZED] = $cardEntity[RefundConstants::TOKENIZED];
            }

            $tokenEntity = $payment->getGlobalOrLocalTokenEntity();

            if (empty($tokenEntity) === false)
            {
                $queryParams[RefundConstants::TOKEN_STATUS] = $tokenEntity->getStatus();
                $queryParams[RefundConstants::TOKEN_EXPIRED_AT] = strval($tokenEntity->getExpiredAt());
            }
        }
        return $queryParams;
    }

    public function getRefundEmailData(RefundEntity $refund)
    {
        $notifier = new Notify($refund->payment);

        return $notifier->getEmailDataForRefund($refund);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function isRefundRequestV1_1(string $merchantId, Payment\Entity $payment): bool
    {
        $v2Variant = $this->app->razorx->getTreatment($payment->getId(),
            Merchant\RazorxTreatment::SCROOGE_INTERNATIONAL_REFUND,
            $this->mode);

        $this->trace->info(TraceCode::SCROOGE_INTERNATIONAL_REFUND, [
            'variant'   => $v2Variant,
            'paymentId' => $payment->getId(),
            'merchantId'=> $merchantId,
        ]);

        if (($payment->isTransferred() === true) and ($payment->isTransfer() === false))
        {
            return false;
        }

        if ($v2Variant !== 'on' and (($payment->getCurrency() !== $payment->merchant->getCurrency()) or ($payment->isDCC() === true)))
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment(
            $payment->getId(),
            Merchant\RazorxTreatment::MERCHANTS_REFUND_CREATE_V_1_1,
            $this->mode
        );

        return (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON);
    }

    public function isBatchRefundRequestV1_1(Payment\Entity $payment): bool
    {
        $v2Variant = $this->app->razorx->getTreatment($payment->getId(),
            Merchant\RazorxTreatment::SCROOGE_INTERNATIONAL_REFUND,
            $this->mode);

        $this->trace->info(TraceCode::SCROOGE_INTERNATIONAL_REFUND, [
            'variant'   => $v2Variant,
            'paymentId' => $payment->getId(),
        ]);

        if ($v2Variant !== 'on' and (($payment->getCurrency() !== $payment->merchant->getCurrency()) or ($payment->isDCC() === true)))
        {
            return false;
        }

        if (($payment->isTransferred() === true) and ($payment->isTransfer() === false))
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment(
            $payment->getId(),
            Merchant\RazorxTreatment::BATCH_REFUND_CREATE_V_1_1,
            $this->mode
        );

        return (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON);
    }

    public function isNonMerchantRefundRequestV1_1(Payment\Entity $payment): bool
    {
        $v2Variant = $this->app->razorx->getTreatment($payment->getId(),
            Merchant\RazorxTreatment::SCROOGE_INTERNATIONAL_REFUND,
            $this->mode);

        $this->trace->info(TraceCode::SCROOGE_INTERNATIONAL_REFUND, [
            'variant'   => $v2Variant,
            'paymentId' => $payment->getId(),
        ]);

        if ($v2Variant !== 'on' and (($payment->getCurrency() !== $payment->merchant->getCurrency()) or ($payment->isDCC() === true)))
        {
            return false;
        }

        if (($payment->isTransferred() === true) and ($payment->isTransfer() === false))
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment(
            $payment->getId(),
            Merchant\RazorxTreatment::NON_MERCHANT_REFUND_CREATE_V_1_1,
            $this->mode
        );

        return (strtolower($variant) === RefundConstants::RAZORX_VARIANT_ON);
    }

    public function pushRefundMessageForDCCEInvoiceCreation($refund, $id)
    {
        /** @var Payment\Entity $payment */
        $payment = $this->retrieve($id);

        if ($payment->isDCC() === true
            and ($payment->isMethodInternationalApp() or $payment->isCard()))
        {
            try
            {
                (new Invoice\DccEInvoiceCore())->dispatchForInvoice($refund->getId(), Invoice\Constants::REFUND_FLOW);
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::DCC_PAYMENT_E_INVOICE_MESSAGE_DISPATCH_FAILED,[
                        'reference_id'       => $refund->getId(),
                        'reference_type'     => Invoice\Constants::REFUND_FLOW,
                    ]
                );
            }
        }
    }
}
