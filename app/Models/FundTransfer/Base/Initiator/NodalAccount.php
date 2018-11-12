<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Holidays;
use RZP\Exception\RuntimeException;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Batch\Entity;
use RZP\Models\FundTransfer\Attempt\Metric;

abstract class NodalAccount extends Base\Core
{
    const SUCCESS                = 'success';

    const FAILED                 = 'failed';

    const MIN_RTGS_AMOUNT        = 200000;

    const MAX_IMPS_AMOUNT        = 200000;

    const RTGS_CUTOFF_HOUR_MIN   = 8;

    const RTGS_CUTOFF_HOUR_MAX   = 15;

    const RTGS_CUTOFF_MINUTE_MAX = 45;

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

    public function __construct(string $purpose = null)
    {
        $this->purpose = $purpose;

        $currentTime = Carbon::now(Timezone::IST);

        $this->isWorkingDay = Holidays::isWorkingDay($currentTime);

        $this->bankingStartTime = Carbon::today(Timezone::IST)->hour(8)->getTimestamp();

        $this->bankingEndTime = Carbon::today(Timezone::IST)->hour(18)->minute(15)->getTimestamp();

        $this->initSummary();

        parent::__construct();
    }

    public function initiateTransfer(Base\PublicCollection $attempts): array
    {
        $this->updateAttemptStatus($attempts);

        $this->trace->info(TraceCode::FTA_UPDATE_STATUS);

        return $this->process($attempts);
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
        $rtgsMinCutoffTime = Carbon::createFromTime(
            self::RTGS_CUTOFF_HOUR_MIN,
            0,
            0,
            Timezone::IST
        )->getTimestamp();

        $rtgsMaxCutoffTime = Carbon::createFromTime(
            self::RTGS_CUTOFF_HOUR_MAX,
            self::RTGS_CUTOFF_MINUTE_MAX,
            0,
            Timezone::IST)->getTimestamp();


        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $mode = Mode::NEFT;

        if ((($now >= $rtgsMinCutoffTime) and ($now <= $rtgsMaxCutoffTime)) and
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

                $attempt->setStatus(Attempt\Status::INITIATED);

                $attempt->source->batchFundTransfer()->associate($this->batchFundTransfer);

                $attempt->source->setStatus(Attempt\Status::INITIATED);

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

    /**
     * It'll create batchFundTransfer entity only if its not created
     */
    protected function createBatchFundTransferEntity()
    {
        $this->batchFundTransfer = new Entity;

        $input = [
            Entity::TYPE              => $this->type,
            Entity::CHANNEL           => $this->channel,
            Entity::AMOUNT            => $this->amount,
            Entity::FEES              => $this->fees,
            Entity::TAX               => $this->tax,
            Entity::TOTAL_COUNT       => 1,
            Entity::TRANSACTION_COUNT => $this->txnsCount,
            Entity::INITIATED_AT      => time(),
            Entity::API_FEE           => 0,
            Entity::GATEWAY_FEE       => 0,
            Entity::URLS              => null,
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
    }

    protected function getTransactionsCount(Attempt\Entity $attempt): int
    {
        $sourceType = $attempt->getSourceType();

        switch ($sourceType)
        {
            case Attempt\Type::SETTLEMENT:
                return $this->repo->transaction->fetchTransactionCountForSettlementId($attempt->getSourceId());

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
}
