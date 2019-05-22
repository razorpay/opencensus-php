<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Services\Mutex;
use RZP\Models\Customer;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Jobs\FundTransfer;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Jobs\QueuedPayouts;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;

/**
 * Class Core
 *
 * IMPORTANT: None of the flows in payout should rely on Basic Auth's merchant
 * since payout creation can happen via admin route too (retry_payouts)
 * In this class, everything should be taken in the input only.
 *
 * @package RZP\Models\Payout
 */
class Core extends Base\Core
{
    const MUTEX_RESOURCE                    = 'PAYOUT_PROCESSING_%s_%s';

    const CUSTOMER_WALLET_MUTEX_RESOURCE    = 'CUSTOMER_WALLET_PAYOUT_%s_%s_%s';

    const MAX_PAYOUT_AMOUNT                 = 800000000; // 80 Lakhs

    const MUTEX_LOCK_TIMEOUT                = 300;

    const PAYOUT_MUTEX_LOCK_TIMEOUT         = 180;

    /**
     * @var Mutex
     */
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Here, onDemand is used to do payout calculation for
     * merchant with es_on_demand feature enabled
     *
     * SOURCE: Merchant PG balance
     * TO: Merchant linked bank account (destination_id)
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return mixed|null
     */
    public function createPayoutToMerchant(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_INTERNAL_MERCHANT_CREATE_REQUEST,
            [
                'input' => $input,
            ]);

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $merchant->getId(), $this->mode);

        $payout = $this->mutex->acquireAndRelease(
            $mutexResource,
            function () use ($input, $merchant)
            {
                $amount = $this->getMerchantPayoutAmount($input, $merchant);

                $currency = $this->getCurrency($input);

                $onDemand = $this->getOnDemandStatus($input);

                $payoutInput = [
                    Entity::PURPOSE   => Purpose::PAYOUT,
                    Entity::AMOUNT    => $amount,
                    Entity::CURRENCY  => $currency,
                    Entity::TYPE      => $onDemand,
                ];

                return $this->getProcessor('merchant_payout')
                            ->setMerchant($merchant)
                            ->createPayout($payoutInput);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);

        if ((isset($input[Entity::TYPE])) and
            ($input[Entity::TYPE] === Entity::ON_DEMAND))
        {
            $this->dispatchFtaInitiate($payout);
        }

        return $payout;
    }

    /**
     * Payouts to a fund account
     *
     * SOURCE: Merchant Balance (PG/Banking)
     * TO: Fund Account (BankAccount/VPA/Card etc) (fund_account_id)
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param Batch\Entity    $batch
     *
     * @return Entity
     */
    public function createPayoutToFundAccount(array $input,
                                              Merchant\Entity $merchant,
                                              Batch\Entity $batch = null): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_TO_FUND_ACCOUNT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $payout = $this->getProcessor('fund_account_payout')
                       ->setMerchant($merchant)
                       ->setBatch($batch)
                       ->createPayout($input);

        $this->dispatchFtaInitiate($payout);

        return $payout;
    }

    /**
     * IMPS payout from a customer wallet to a func account
     *
     * SOURCE: Customer Wallet Balance
     * TO: Fund Account (BankAccount/VPA/Card etc) (fund_account_id)
     *
     * @param Customer\Entity $customer
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createPayoutFromCustomerWallet(
        array $input,
        Customer\Entity $customer,
        Merchant\Entity $merchant): Entity
    {
        $customerId = $customer->getId();

        $this->trace->info(
            TraceCode::PAYOUT_FROM_CUSTOMER_WALLET_CREATE_REQUEST,
            [
                'input'       => $input,
                'customer_id' => $customerId
            ]);

        $mutexResource = sprintf(
            self::CUSTOMER_WALLET_MUTEX_RESOURCE,
            $merchant->getId(),
            $customerId,
            $this->mode);

        return $this->mutex->acquireAndRelease(
            $mutexResource,
            function() use ($input, $customer, $merchant)
            {
                return $this->getProcessor('customer_wallet_payout')
                            ->setSourceCustomer($customer)
                            ->setMerchant($merchant)
                            ->createPayout($input);
            },
            self::PAYOUT_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);
    }

    /**
     * Makes a payout from merchant primary balance, but link the Payout to a payment ID
     *
     * SOURCE: Merchant Balance (PG/Banking) - Payment ID
     * TO: Fund Account (BankAccount/VPA/Card etc)
     *
     * @param Payment\Entity  $payment
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function createPayoutFromPayment(Payment\Entity $payment, array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_FOR_PAYMENT_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        // The mutex for this is handled in `createPayoutToFundAccount()`.
        (new Validator)->validatePaymentForPayout($input, $payment);

        $payout = $this->createPayoutToFundAccount($input, $merchant);

        $payout->payment()->associate($payment);

        $this->repo->saveOrFail($payout);

        return $payout;
    }

    public function retryReversedPayout(Entity $payout): Entity
    {
        // We can't just retry the existing payout (create another FTA and process it via that) since the
        // payout will be marked as reversed. A reverse transaction would also get created for the same.
        // Hence, we create a new payout and a new transaction and so on.

        $this->trace->info(
            TraceCode::PAYOUT_RETRY_REQUEST,
            [
                'payout' => $payout->toArray(),
            ]);

        $payout->getValidator()->validateRetryPayout();

        if ($payout->hasFundAccount() === true)
        {
            if ($payout->hasCustomer() === true)
            {
                $payoutInput = $this->getRetryPayoutInputForCustomerWallet($payout);

                return $this->createPayoutFromCustomerWallet($payoutInput, $payout->customer, $payout->merchant);
            }
            else
            {
                $payoutInput = $this->getRetryPayoutInputForFundAccount($payout);

                // Note that if the payout was created by batch,
                // the information is not percolated to the new payout.
                // This is because, this new payout was not created by the batch.
                return $this->createPayoutToFundAccount($payoutInput, $payout->merchant);
            }
        }
        else
        {
            $payoutInput = $this->getRetryPayoutInputForMerchant($payout);

            return $this->createPayoutToMerchant($payoutInput, $payout->merchant);
        }
    }

    public function updateStatusAfterFtaRecon(Entity $payout, array $ftaData)
    {
        switch ($ftaData[Attempt\Constants::FTA_STATUS])
        {
            case Attempt\Status::PROCESSED:
                $this->handleFtaProcessed($payout);
                break;

            case Attempt\Status::FAILED:
                $this->handleFtaFailed($payout, $ftaData[Attempt\Constants::FAILURE_REASON]);
                break;

            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                break;

            default:
                $this->trace->warning(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_PAYOUT,
                    $ftaData);
        }
    }

    public function updateStatusAfterFtaInitiated(Entity $payout, Attempt\Entity $fta)
    {
        $payout->batchFundTransfer()->associate($fta->batchFundTransfer);

        $payout->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($payout);

        $this->pushInitiatedMetrics($payout);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $payout, array $ftaData = [])
    {
        // For non-Yesbank, we will not get public_failure_reason
        $failureReason = $responseData[Attempt\Constants::FAILURE_REASON] ?? null;

        $payout->setUtr($ftaData[Attempt\Constants::UTR]);

        $payout->setRemarks($ftaData[Attempt\Constants::REMARKS]);

        //
        // For VPA type, we always set it to UPI only
        // at build and we don't take the mode from FTA.
        //
        // Also, we don't want to override the payout's mode if it's already set.
        //
        if ((empty($ftaData[Attempt\Constants::VPA_ID]) === true) and
            ($payout->getMode() === null))
        {
            $payout->setMode($ftaData[Attempt\Constants::MODE]);
        }

        $payout->setFailureReason($failureReason);

        $this->repo->saveOrFail($payout);
    }

    public function processDispatchForQueuedPayouts(Base\PublicCollection $queuedPayouts)
    {
        $grouped = $queuedPayouts->groupBy(Entity::BALANCE_ID);

        $traceData = [];

        foreach ($grouped as $balanceId => $payouts)
        {
            // We get balance via payout since we would have already fetched balance entity
            // when fetching the payouts list. Avoiding an extra DB query here by doing this.
            $balanceEntity = $payouts->first()->balance;

            $balanceAmount = $balanceEntity->getBalance();

            $dispatchedData = $this->dispatchApplicablePayouts($balanceAmount, $payouts);

            $traceData[$balanceId] = [
                'original_balance'          => $balanceAmount,
                'balance_remaining'         => $dispatchedData['balance_remaining'],
                'total_payout_count'        => count($payouts),
                'dispatched_payout_count'   => $dispatchedData['dispatched_payout_count'],
                'dispatched_payout_amount'  => ($balanceAmount - $dispatchedData['balance_remaining']),
            ];
        }

        $this->trace->info(
            TraceCode::PAYOUT_DISPATCH_SUMMARY,
            $traceData
        );

        return $traceData;
    }

    public function processQueuedPayout(string $payoutId): Entity
    {
        return $this->mutex->acquireAndRelease(
                $payoutId,
                function() use ($payoutId)
                {
                    /** @var Entity $payout */
                    $payout = $this->repo->payout->findOrFail($payoutId);

                    $payout->getValidator()->validateProcessingQueuedPayout();

                    //
                    // Currently, we support queued concept only for Fund Account type.
                    // If we are supporting for others, the processor call needs to be fixed here.
                    // Also, need to fix transaction.created event in the processor since
                    // we do that only for fund_account and not for others.
                    //
                    // Apart from this, we also have to handle dispatching FTA for queued payouts.
                    //
                    // We also have to handle the fund transfer destination while processing the queued payout.
                    //
                    $payout = $this->getProcessor('fund_account_payout')
                                   ->setMerchant($payout->merchant)
                                   ->processQueuedPayout($payout);

                    //
                    // There might be some type of payouts where we don't want to dispatch FTA.
                    // Should handle that before adding any other type of payouts as queued.
                    //
                    $this->dispatchFtaInitiate($payout);

                    return $payout;
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    public function cancelPayout(Entity $payout): Entity
    {
        return $this->mutex->acquireAndRelease(
                $payout->getId(),
                function() use ($payout)
                {
                    $payout->getValidator()->validateCancel();

                    $payout->setStatus(Status::CANCELLED);

                    $this->repo->saveOrFail($payout);

                    return $payout;
                },
                self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYOUT_ALREADY_BEING_PROCESSED);
    }

    protected function dispatchApplicablePayouts(int $totalBalance, Base\PublicCollection $payouts)
    {
        $dispatchedCount = 0;

        foreach ($payouts as $payout)
        {
            $payoutAmount = $payout->getAmount();

            // We have to explicitly calculate fees here since if it's queued, transaction wouldn't
            // have been created and hence the fees also wouldn't have been calculated.
            list($payoutFees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            $totalPayoutAmount = $payoutAmount + $payoutFees;

            if ($totalBalance < $totalPayoutAmount)
            {
                continue;
            }

            $totalBalance -= $totalPayoutAmount;

            $this->dispatchQueuedPayout($payout, $payoutFees, $totalBalance);

            $dispatchedCount += 1;
         }

         return [
             'balance_remaining'        => $totalBalance,
             'dispatched_payout_count'  => $dispatchedCount,
         ];
    }

    protected function dispatchQueuedPayout(Entity $payout, int $fees, int $currentBalance)
    {
        $payoutId = $payout->getId();

        $traceInfo = [
            'payout_id'         => $payoutId,
            'amount'            => $payout->getAmount(),
            'fees'              => $fees,
            'current_balance'   => $currentBalance,
        ];

        try
        {
            $this->trace->info(TraceCode::PAYOUT_QUEUE_DISPATCH_INIT, $traceInfo);

            QueuedPayouts::dispatch($this->mode, $payoutId);

            $this->trace->info(TraceCode::PAYOUT_QUEUE_DISPATCH_COMPLETE, $traceInfo);
        }
        catch (\Throwable $e)
        {
            // If the dispatch fails due to any reason, cron will
            // pick up these payouts again and attempt to dispatch.

            $data = $traceInfo + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYOUT_QUEUE_DISPATCH_FAILED,
                $data);
        }
    }

    protected function getRetryPayoutInputForMerchant(Entity $payout): array
    {
        $type = $payout->getPayoutType();

        $amount = $payout->getAmount();

        $payoutInput = [
            Entity::TYPE        => $type,
            Entity::AMOUNT      => $amount,
            Entity::CURRENCY    => $payout->getCurrency(),
        ];

        //
        // If the merchant makes an on_demand payout request with 100rs,
        // we actually create the payout entity with amount 98rs. We do
        // this only for on_demand payouts. Hence, when retrying, we
        // add the fees and amount to create a payout request of the
        // original amount which the merchant would have sent initially.
        //
        if ($type === Entity::ON_DEMAND)
        {
            $fees = $payout->getFees();

            $payoutInput[Entity::AMOUNT] = $amount + $fees;
        }

        return $payoutInput;
    }

    protected function getRetryPayoutInputForFundAccount(Entity $payout): array
    {
        $payoutInput = [
            Entity::FUND_ACCOUNT_ID => FundAccount\Entity::getSignedId($payout->getFundAccountId()),
            Entity::AMOUNT          => $payout->getAmount(),
            Entity::CURRENCY        => $payout->getCurrency(),
            Entity::PURPOSE         => $payout->getPurpose(),
            Entity::BALANCE_ID      => $payout->getBalanceId(),
            Entity::MODE            => $payout->getMode(),
        ];

        // TODO: Support Notes copy also.

        return $payoutInput;
    }

    protected function getRetryPayoutInputForCustomerWallet(Entity $payout): array
    {
        $payoutInput = [
            Entity::FUND_ACCOUNT_ID => FundAccount\Entity::getSignedId($payout->getFundAccountId()),
            Entity::AMOUNT          => $payout->getAmount(),
            Entity::CURRENCY        => $payout->getCurrency(),
            Entity::PURPOSE         => $payout->getPurpose(),
        ];

        // TODO: Support Notes copy also.

        return $payoutInput;
    }

    protected function handleFtaProcessed(Entity $payout)
    {
        $payout->setStatus(Status::PROCESSED);

        $this->repo->saveOrFail($payout);

        $this->pushProcessedMetrics($payout);

        $this->app->events->fire('api.payout.processed', [$payout]);
    }

    protected function handleFtaFailed(Entity $payout, string $ftaFailureReason = null)
    {
        $this->reversePayout($payout, $ftaFailureReason);

        $this->app->events->fire('api.payout.reversed', [$payout]);
    }

    protected function reversePayout(Entity $payout, string $reverseReason = null): Reversal\Entity
    {
        $this->trace->info(
            TraceCode::PAYOUT_REVERSAL_INITIATED,
            [
                'payout_id' => $payout->getId(),
            ]);

        if ($payout->isStatusReversed() === true)
        {
            throw new Exception\LogicException(
                'Attempted to reverse an already reversed payout',
                null,
                [
                    'payout_id'      => $payout->getId(),
                    'status'         => $payout->getStatus(),
                    'reverse_reason' => $reverseReason,
                ]);
        }

        $reversal = $this->repo->transaction(
            function() use ($payout, $reverseReason) {
                $reversal = (new Reversal\Core)->reverseForPayout($payout);

                $payout->setStatus(Status::REVERSED);

                $payout->setFailureReason($reverseReason);

                $this->repo->saveOrFail($payout);

                return $reversal;
            });

        $this->pushReversedMetrics($payout);

        return $reversal;
    }

    protected function pushReversedMetrics(Entity $payout)
    {
        $metricDimensions        = $payout->getMetricDimensions();
        $createdToReversedTime   = $payout->getReversedAt() - $payout->getCreatedAt();
        $processedToReversedTime = $payout->getReversedAt() - $payout->getProcessedAt();

        $this->trace->histogram(
            Metric::PAYOUT_CREATED_TO_REVERSED_DURATION_MILLISECONDS,
            $createdToReversedTime,
            $metricDimensions);

        $this->trace->histogram(
            Metric::PAYOUT_PROCESSED_TO_REVERSED_DURATION_MILLISECONDS,
            $processedToReversedTime,
            $metricDimensions);
    }

    protected function pushInitiatedMetrics(Entity $payout)
    {
        $metricDimensions        = $payout->getMetricDimensions();
        $createdToInitiatedTime  = $payout->getInitiatedAt() - $payout->getCreatedAt();

        $this->trace->histogram(
            Metric::PAYOUT_CREATED_TO_INITIATED_DURATION_MILLISECONDS,
            $createdToInitiatedTime,
            $metricDimensions);
    }

    protected function pushProcessedMetrics(Entity $payout)
    {
        $metricDimensions         = $payout->getMetricDimensions();
        $createdToProcessedTime   = $payout->getProcessedAt() - $payout->getCreatedAt();
        $initiatedToProcessedTime = $payout->getProcessedAt() - $payout->getInitiatedAt();

        $this->trace->histogram(
            Metric::PAYOUT_CREATED_TO_PROCESSED_DURATION_MILLISECONDS,
            $createdToProcessedTime,
            $metricDimensions);

        $this->trace->histogram(
            Metric::PAYOUT_INITIATED_TO_PROCESSED_DURATION_MILLISECONDS,
            $initiatedToProcessedTime,
            $metricDimensions);
    }

    protected function getMerchantPayoutAmount(array $input, Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        if (isset($input[Entity::AMOUNT]) === true)
        {
            $amount = $input[Entity::AMOUNT];
        }
        else
        {
            $merchantBalance = $merchant->primaryBalance->getBalance();

            if ((isset($input[Entity::BUFFER_AMOUNT]) === true) and
                ($merchantBalance < $input[Entity::BUFFER_AMOUNT]))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'merchant balance is less than buffer amount',
                    Entity::BUFFER_AMOUNT,
                    [
                        'merchant_id' => $merchantId,
                        'buffer_amount' => $input[Entity::BUFFER_AMOUNT],
                        'balance'       => $merchantBalance
                    ]);
            }

            $amount = $merchantBalance - ($input[Entity::BUFFER_AMOUNT] ?? 0);

            $amount = ($amount > self::MAX_PAYOUT_AMOUNT) ? self::MAX_PAYOUT_AMOUNT : $amount;
        }

        if ((isset($input[Entity::MIN_AMOUNT]) === true) and
            ($amount < $input[Entity::MIN_AMOUNT]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'amount is less than min amount',
                Entity::MIN_AMOUNT,
                [
                    'merchant_id' => $merchantId,
                    'min_amount'  => $input[Entity::MIN_AMOUNT],
                    'amount'      => $amount
                ]);
        }

        //
        // Modulo will convert the amount into multiples
        // of modulo value
        //
        if (isset($input[Entity::MODULO]) === true)
        {
            $moduloAmount = $amount % $input[Entity::MODULO];

            $amount = $amount - $moduloAmount;
        }

        return $amount;
    }

    protected function getCurrency(array $input): string
    {
        if (isset($input[Entity::CURRENCY]) === true)
        {
            return $input[Entity::CURRENCY];
        }

        return Currency::INR;
    }

    protected function getOnDemandStatus(array $input): string
    {
        return ($input[Entity::TYPE] ?? Entity::DEFAULT);
    }

    protected function getProcessor(string $type): Processor\Base
    {
        $processor = __NAMESPACE__ . '\\' . 'Processor';

        $processor .= '\\' . studly_case($type);

        return new $processor();
    }

    protected function dispatchFtaInitiate(Entity $payout)
    {
        //
        // When we queue a payout, we don't create any transaction or FTA.
        // We do it later when we actually process that queued payout.
        //
        if ($payout->isStatusQueued() === true)
        {
            return;
        }

        $ftaId = $payout->fundTransferAttempts->first()->getId();

        $info = [
            'fta_id'    => $ftaId,
            'payout_id' => $payout->getId()
        ];

        try
        {
            $this->trace->info(TraceCode::FTA_DISPATCH_FOR_PAYOUT_INIT, $info);

            FundTransfer::dispatch($this->mode, $ftaId);

            $this->trace->info(TraceCode::FTA_DISPATCH_FOR_PAYOUT_COMPLETE, $info);
        }
        catch (\Throwable $e)
        {
            $data = $info + [ 'message' => $e->getMessage() ];

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_DISPATCH_FOR_MERCHANT_FAILED,
                $data);

            (new Settlement\SlackNotification)->send(
                'FundTransfer dispatch for merchant failed',
                $data,
                $e,
                1);
        }
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        $entity->setFTSTransferId($ftsTransferId);

        $this->repo->saveOrFail($entity);
    }
}
