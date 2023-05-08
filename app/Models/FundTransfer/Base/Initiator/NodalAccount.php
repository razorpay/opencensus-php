<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use Cache;
use Carbon\Carbon;
use Monolog\Logger;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Batch;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Holidays;
use RZP\Exception\RuntimeException;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Attempt\Metric;
use RZP\Models\FundTransfer\Attempt\Alerts;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\StatusProcessor;

abstract class NodalAccount extends Base\Core
{
    const SUCCESS                = 'success';

    const FAILED                 = 'failed';

    const LOW_BALANCE_ALERT      = 'low_balance_alert';

    const MIN_RTGS_AMOUNT        = 200000;
    const MAX_IMPS_AMOUNT        = 500000;

    const RTGS_CUTOFF_HOUR_MIN   = 8;
    const RTGS_CUTOFF_HOUR_MAX   = 15;
    const RTGS_CUTOFF_MINUTE_MAX = 45;

    const RTGS_REVISED_CUTOFF_HOUR_MAX   = 17;
    const RTGS_REVISED_CUTOFF_MINUTE_MAX = 30;

    protected $batchFundTransfer = null;

    protected $amount = 0;
    protected $fees = 0;
    protected $tax = 0;

    protected $count = 0;
    protected $txnsCount = 0;

    protected $channel = null;

    protected $type = null;

    protected $summary = [];

    protected $purpose = null;

    protected $transferStatus = [];

    protected $isWorkingDay;

    protected $bankingStartTime;
    protected $bankingEndTime;
    protected $bankingStartTimeRtgs;
    protected $bankingEndTimeRtgs;

    public function __construct(string $purpose = null)
    {
        $this->purpose = $purpose;

        $currentTime = Carbon::now(Timezone::IST);

        $this->isWorkingDay = Holidays::isWorkingDay($currentTime);

        $this->bankingStartTime = Carbon::today(Timezone::IST)->hour(8)->getTimestamp();

        $this->bankingEndTime = Carbon::today(Timezone::IST)->hour(18)->minute(15)->getTimestamp();

        $this->bankingStartTimeRtgs = Carbon::createFromTime(self::RTGS_CUTOFF_HOUR_MIN, 0, 0, Timezone::IST)
                                            ->getTimestamp();

        $this->bankingEndTimeRtgs = Carbon::createFromTime(
                                                self::RTGS_REVISED_CUTOFF_HOUR_MAX,
                                                self::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                                                0,
                                                Timezone::IST)
                                          ->getTimestamp();

        $this->initSummary();

        parent::__construct();
    }

    public function initiateTransfer(Base\PublicCollection $attempts, bool $forceFlag = false): array
    {
        $this->updateAttemptStatus($attempts);

        $this->trace->info(TraceCode::FTA_UPDATE_STATUS);

        return $this->process($attempts, $forceFlag);
    }

    protected function isRefund(): bool
    {
        return ($this->purpose === Attempt\Purpose::REFUND);
    }

    protected function isSettlement(): bool
    {
        return ($this->purpose === Attempt\Purpose::SETTLEMENT);
    }

    protected function getTransferMode($amount, Merchant\Entity $merchant): string
    {
        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $mode = Mode::NEFT;

        if ((($now >= $this->bankingStartTimeRtgs) and
             ($now <= $this->bankingEndTimeRtgs)) and
            ($amount >= self::MIN_RTGS_AMOUNT))
        {
            $mode = Mode::RTGS;
        }

        //
        // Need this only for Piggy merchants currently. Hence
        // the check against parentId and not the merchantId.
        // Temporary solution. Proper solution coming soon.
        //
        if (in_array($merchant->getParentId(), Merchant\Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $mode = Mode::NEFT;
        }

        if(in_array($merchant->getId(), Merchant\Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $mode = Mode::NEFT;
        }

        return $mode;
    }

    protected function updateAttemptStatus(Base\PublicCollection $attempts)
    {
        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_FTA_UPDATE_STATUS_BEGIN);

        try
        {
            foreach ($attempts as $attempt)
            {
                $txnsCount = $this->getTransactionsCount($attempt);

                $source = $attempt->source;

                $this->count++;

                $this->type      = $source->getEntity();

                $this->channel   = $attempt->getChannel();

                $this->tax       += $source->getTax();

                $this->fees      += $source->getFees();

                $this->amount    += $source->getAmount();

                $this->txnsCount += $txnsCount;

                if ($this->batchFundTransfer === null)
                {
                    $this->createBatchFundTransferEntity();
                }

                $attempt->batchFundTransfer()->associate($this->batchFundTransfer);

                $this->setAttemptToInitiated($attempt);

                $this->trace->info(
                    TraceCode::FUND_TRANSFER_ATTEMPT_STATUS_UPDATED,
                    ['fta_id' => $attempt->getId()]);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FUND_TRANSFER_ATTEMPT_STATUS_UPDATE_FAILED
            );
        }

        $this->updateBatchFundTransferEntity();

        $this->traceMemoryUsage(TraceCode::MEMORY_USAGE_FTA_UPDATE_STATUS_END);
    }

    protected function setAttemptToInitiated(Attempt\Entity $attempt)
    {
        $attempt->setStatus(Attempt\Status::INITIATED);

        $cacheKey = StatusProcessor::FTA_INITIATED . '_' . $attempt->getId();

        Cache::put($cacheKey, true, (StatusProcessor::TIME_OFFSET / 60));
    }

    protected function getInitiatedStatusForEntity(string $sourceEntityName): string
    {
        /** @var Payout\Status|Settlement\Status|Refund\Status $entityStatusClass */
        $entityStatusClass = Constants\Entity::getEntityNamespace($sourceEntityName) . '\\Status';

        return $entityStatusClass::INITIATED;
    }

    /**
     * It'll create batchFundTransfer entity only if its not created
     */
    protected function createBatchFundTransferEntity()
    {
        $this->batchFundTransfer = new Batch\Entity;

        $input = [
            Batch\Entity::TYPE              => $this->type,
            Batch\Entity::CHANNEL           => $this->channel,
            Batch\Entity::AMOUNT            => $this->amount,
            Batch\Entity::FEES              => $this->fees,
            Batch\Entity::TAX               => $this->tax,
            Batch\Entity::TOTAL_COUNT       => 1,
            Batch\Entity::TRANSACTION_COUNT => $this->txnsCount,
            Batch\Entity::INITIATED_AT      => time(),
            Batch\Entity::API_FEE           => 0,
            Batch\Entity::GATEWAY_FEE       => 0,
            Batch\Entity::URLS              => null,
        ];

        $this->batchFundTransfer->build($input);

        $this->repo->saveOrFail($this->batchFundTransfer);

        $this->trace->info(
            TraceCode::BATCH_FUND_TRANSFER_CREATED,
            [
                'batch_fund_transfer_id' => $this->batchFundTransfer->getId()
            ]);
    }

    protected function updateBatchFundTransferEntity()
    {
        if ($this->batchFundTransfer === null)
        {
            throw new RuntimeException('Trying to update batchFundTransfer entity before creating');
        }

        $this->batchFundTransfer->setAmount($this->amount);

        $this->batchFundTransfer->setFees($this->fees);

        $this->batchFundTransfer->setTax($this->tax);

        $this->batchFundTransfer->setTotalCount($this->count);

        $this->batchFundTransfer->setTransactionCount($this->txnsCount);

        $this->repo->saveOrFail($this->batchFundTransfer);
    }

    protected function getTransactionsCount(Attempt\Entity $attempt): int
    {
        $sourceType = $attempt->getSourceType();

        switch ($sourceType)
        {
//            Slack Ref : https://razorpay.slack.com/archives/C01FCB94K6V/p1617418029324200
//            case Attempt\Type::SETTLEMENT:
//                return $this->repo->transaction->fetchTransactionCountForSettlementId($attempt->getSourceId());

            default:
                return 1;
        }
    }

    /**
     * Initialize the Settlement summary variables
     */
    protected function initSummary()
    {
        $this->summary = [
            'total' => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::NEFT  => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::RTGS  => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::IFT   => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::IMPS   => [
                'amount'    => 0,
                'count'     => 0
            ],
        ];
    }

    /**
     * This is used to initialize the response status for the API based nodal accounts
     */
    protected function initStats()
    {
        $this->transferStatus = [
            self::SUCCESS      => 0,
            self::FAILED       => 0,
        ];
    }

    /**
     * This is used to update the response status for the API based nodal accounts
     *
     * @param int $initiated
     */
    protected function updateTransferStatus(int $initiated)
    {
        $this->transferStatus[self::SUCCESS] = $initiated;

        $this->transferStatus[self::FAILED] = $this->count - $initiated;
    }

    protected function updateSummary($type, $amount)
    {
        $this->summary['total']['count']++;
        $this->summary['total']['amount'] += $amount;

        $this->summary[$type]['amount'] += $amount;
        $this->summary[$type]['count']++;
    }

    protected function trackAttemptsInitiatedSuccess($channel, $purpose = null, $sourceType)
    {
        $dimensions = Metric::getDimensionsAttemptsInitiated($channel, $purpose, $sourceType);

        $this->trace->count(
            Metric::ATTEMPTS_INITIATE_SUCCESS_TOTAL,
            $dimensions);
    }

    protected function trackAttemptsInitiatedFailure($channel, $purpose = null, $sourceType)
    {
        $dimensions = Metric::getDimensionsAttemptsInitiated($channel, $purpose, $sourceType);

        $this->trace->count(
            Metric::ATTEMPTS_INITIATE_FAILURE_TOTAL,
            $dimensions);
    }


    protected function traceMemoryUsage(string $traceCode)
    {
        $memoryAllocated = get_human_readable_size(memory_get_usage(true));
        $memoryUsed = get_human_readable_size(memory_get_usage());
        $memoryPeakUsage = get_human_readable_size(memory_get_peak_usage());
        $memoryPeakUsageAllocated = get_human_readable_size(memory_get_peak_usage(true));

        $this->trace->info(
            $traceCode,
            [
                'memory_allocated'               => $memoryAllocated,
                'memory_used'                    => $memoryUsed,
                'memory_peak_usage'              => $memoryPeakUsage,
                'memory_peak_usage_allocated'    => $memoryPeakUsageAllocated,
            ]);
    }

    protected function sendLowBalanceAlert(array $data)
    {
        try
        {
            // sending 3rd and 4th param just represent this as an failure alert
            // because immediate action is required for this
            (new Settlement\SlackNotification)->send('low_balance_alert', $data, null, 1);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::SLACK_NOTIFICATION_SEND_FAILED,
                $data);
        }
    }

    /**
     * Gives request type for the given attempt.
     * Based on these attempts nodal config will be picked while making any request to bank
     *
     * @param Attempt\Entity $attempt
     * @return string
     */
    protected function getRequestType(Attempt\Entity $attempt): string
    {
        switch (true)
        {
            case $attempt->isOfBanking():
                return Attempt\Type::BANKING;

            case $attempt->isPennyTesting():
                return Attempt\Type::SYNC;

            default:
                return Attempt\Type::PRIMARY;
        }
    }

    protected function postFtaInitiateProcess(Attempt\Entity $fta)
    {
        try
        {
            $source = $fta->source;

            $entityType = $source->getEntity();

            $sourceCoreClass = Entity::getEntityNamespace($entityType) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateStatusAfterFtaInitiated') === false)
            {
                return;
            }

            $sourceCore->updateStatusAfterFtaInitiated($source, $fta);
        }
        catch (\Throwable $e)
        {
            $data = [
                'fta_id'      => $fta->getId(),
                'status'      => $fta->getStatus(),
                'merchant_id' => $fta->getMerchantId(),
                'source_id'   => $fta->getSourceId(),
                'source_type' => $fta->getSourceType(),
                'error'       => $e->getMessage(),
            ];

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_SOURCE_PROCESSING_FAILED,
                $data
            );

            $this->trace->count(Metric::WEBHOOK_UPDATE_FAILURE_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);
        }
    }

    /**
     * @param Attempt\Entity $attempt
     * @param                $amount
     * @return string
     * @throws BadRequestValidationFailureException
     */
    public function getPaymentModeForCard(Attempt\Entity $attempt, $amount): string
    {
        if ($attempt->hasMode() === true)
        {
            return $attempt->getMode();
        }

        $iin = $attempt->card->iinRelation;

        if ($iin !== null)
        {
            $issuer = $iin->getIssuer();
        }
        else
        {
            throw new BadRequestValidationFailureException("iin is not valid mode for issuer");
        }

        $networkCode = $attempt->card->getNetworkCode();

        $supportedModes = Mode::getSupportedModes($issuer, $networkCode);

        if ($amount < self::MAX_IMPS_AMOUNT)
        {
            $mode =  Mode::IMPS;
        }
        else
        {
            $mode = Mode::NEFT;

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            if ((($now >= $this->bankingStartTimeRtgs) and
                    ($now <= $this->bankingEndTimeRtgs)) and
                ($amount >= self::MIN_RTGS_AMOUNT))
            {
                $mode = Mode::RTGS;
            }
        }

        if (in_array($mode, $supportedModes, true) === true)
        {
            return $mode;
        }
        else if (in_array(Mode::NEFT, $supportedModes, true) === true)
        {
            return Mode::NEFT;
        }
        else
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for issuer $issuer");
        }
    }

    protected function markAttemptAsFailed(Attempt\Entity $entity, $remarks, $failureReason)
    {
        $attemptCore = new Attempt\Core;

        $statusNamespace = $attemptCore->getStatusClass($entity);

        $statusClass = new $statusNamespace;

        $failureStatuses = $statusClass::getFailureStatus();

        $failureStatus = array_keys($failureStatuses)[0];

        $entity->setBankStatusCode($failureStatus);

        $entity->setRemarks($remarks);

        $entity->setFailureReason($failureReason);

        $this->repo->saveOrFail($entity);
    }

    protected function markFailedIfBANotExists(Attempt\Entity $entity) : bool
    {
        if($entity->hasBankAccount() === true)
        {
            if(empty($entity->bankAccount) === true)
            {
                $remarks = 'Associated Bank Account is not active.';

                $failureReason = 'Associated Bank Account is not active.';

                $this->markAttemptAsFailed($entity, $remarks, $failureReason);

                $this->trace->info(TraceCode::FTA_BANK_ACCOUNT_EMPTY,
                    [
                        'fta_id'            => $entity->getId(),
                        'remarks'           => $remarks
                    ]);

                return true;
            }
        }
        return false;
    }
}
