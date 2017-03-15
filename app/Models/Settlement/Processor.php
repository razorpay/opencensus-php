<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Batch\Entity as BatchFundTransfer;
use RZP\Models\FundTransfer\Batch\BatchFundTransferTrait;
use RZP\Models\FundTransfer\Kotak;
use RZP\Trace\TraceCode;

class Processor extends Base\Core
{
    use SettlementTrait;
    use BatchFundTransferTrait;

    protected $setlTime;

    protected $input;

    protected $mutex;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING';

    const MUTEX_RETRY_RESOURCE  = 'SETTLEMENT_RETRY';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processFailedSettlements(array $input, string $channel)
    {
        $this->preSettlementProcessing($input);

        list($shouldProcess, $message) = $this->shouldProcessSettlements();

        if ($shouldProcess === false)
        {
            return $message;
        }

        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RETRY_RESOURCE,
            function () use ($input)
            {
                return $this->retryProcessFailedSettlements($input);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    public function process(array $input, $channel)
    {
        $this->preSettlementProcessing($input);

        list($shouldProcess, $data) = $this->shouldProcessSettlements();

        if ($shouldProcess === true)
        {
            $data = $this->mutex->acquireAndRelease(
                self::MUTEX_RESOURCE,
                function () use ($channel)
                {
                    return $this->processSettlements($channel);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        $schedules = $this->repo->schedule->fetchSchedulesWithDueRun($this->setlTime);

        $schedules->callOnEveryItem('updateNextRun');

        $this->repo->saveOrFailCollection($schedules);

        $this->trace->info(TraceCode::SCHEDULE_NEXT_RUN_UPDATED, $schedules->getIds());

        return $data;
    }

    protected function processSettlements($channel)
    {
        $response = [];

        try
        {
            $channels = $this->getArrayedChannels($channel);

            foreach ($channels as $channel)
            {
                list($settlements, $txnCount, $setlAttempts) = $this->createSettlements($channel);

                $response[$channel] = $this->generateAndSendSettlementFile($settlements, $setlAttempts, $txnCount, $channel);
            }
        }
        catch (\Exception $e)
        {
            $this->settlementFailure($channel, $e, TraceCode::SETTLEMENT_INITIATE_FAILED);
        }

        return $response;
    }

    protected function retryProcessFailedSettlements(array $input): array
    {
        try
        {
            if (isset($input['settlement_ids']) === false)
            {
                throw new Exception\LogicException('No settlement IDs provided for retry.');
            }

            $setlIds = $input['settlement_ids'];

            Entity::verifyIdAndStripSignMultiple($setlIds);

            $settlements = $this->repo->settlement->getFailedSettlementsForRetry($setlIds);

            $setlAttempts = new Base\PublicCollection;

            $totalTxns = 0;

            foreach ($settlements as $setl)
            {
                $setlTxns = $setl->setlTransactions;

                $setlTxnsCount = $setlTxns->count();

                $merchantSettler = new Merchant($setl->merchant, $this->channel, $this->repo);

                list($setl, $bankTransferAtpt) = $this->repo->transaction(
                    function() use ($merchantSettler, $setl, $setlTxns, $setlTxnsCount)
                {
                    list($setl, $bankTransferAtpt) = $merchantSettler->retryFailedSettlement($setl);

                    return $this->createAndupdateBatchEntities($setl, $setlTxnsCount, $bankTransferAtpt);
                });

                $setlAttempts->push($bankTransferAtpt);

                $totalTxns += $setlTxnsCount;
            }

            $response = $this->generateAndSendSettlementFile($settlements, $setlAttempts, $totalTxns);
        }
        catch (\Exception $e)
        {
            $this->settlementFailure($this->channel, $e, TraceCode::SETTLEMENT_RETRY_FAILED);
        }

        return $response;
    }

    protected function generateAndSendSettlementFile($settlements, $setlAttempts, $txnCount, $channel)
    {
        $data = [
            'count'             => $settlements->count(),
            'transaction_count' => $txnCount,
        ];

        if ($setlAttempts->count() > 0)
        {
            list($urlText, $urlExcel) = $this->generateSettlementFile($setlAttempts, $channel);

            $urls = [
                'kotak_settlement_txt'   => $urlText,
                'kotak_settlement_excel' => $urlExcel
            ];

            $this->updateBatchFundTransferEntityUrls($urls);

            $data['settlement_text_file']  = $urlText;
            $data['settlement_excel_file'] = $urlExcel;

            $this->successNotification($data, $settlements, TraceCode::SETTLEMENT_INITIATED);
        }
        else
        {
            $data['message'] = 'No settlements found!';
        }

        return $data;
    }

    protected function createSettlements($channel): array
    {
        $txns = $this->repo->transaction->fetchUnsettledTxnsForDueSchedules($this->setlTime, $channel);

        list($settlements, $settledTxnsCount, $setlAttempts) =
            $this->processUnsettledTransactions($txns, $channel);

        return [$settlements, $settledTxnsCount, $setlAttempts];
    }

    protected function processUnsettledTransactions($txns, $channel): array
    {
        $txns = $this->filterTransactionsForSettlement($txns);

        list($settlements, $settledTxnsCount, $setlAttempts) =
            $this->createSettlementsFromTxns($txns, $channel);

        return [$settlements, $settledTxnsCount, $setlAttempts];
    }

    protected function generateSettlementFile($setlAttempts, $channel)
    {
        $data = [null, null];

        if ($channel === Channel::KOTAK)
        {
            $data = (new Kotak\NodalAccount)->generateSettlementFile($setlAttempts);
        }

        return $data;
    }

    protected function preSettlementProcessing(array $input)
    {
        $this->inititalizeVariables($input);

        $this->increaseAllowedSystemLimits();
    }

    protected function inititalizeVariables(array $input)
    {
        $this->setlTime = Carbon::now('Asia/Kolkata')->timestamp;

        $this->input = $input;
    }

    protected function shouldProcessSettlements()
    {
        $today = Carbon::today('Asia/Kolkata');

        if (($this->mode === Mode::LIVE) and
            (Holidays::isWorkingDay($today) === false))
        {
            return [false, Holidays::HOLIDAY_MESSAGE];
        }

        if ($this->checkInvalidSettlementTime() === true)
        {
            return [false, ['message' => 'settlements cannot be processed now']];
        }

        return [true, null];
    }

    /**
     *  NEFT can be processed between 8am and 6 pm only, while batch file can be
     *  uploaded anytime.
     * @return [boolean] [returns if settlement can be proessed now]
     */
    protected function checkInvalidSettlementTime()
    {
        // Cron runs at 5.01pm.
        $fivePm = Carbon::today('Asia/Kolkata')->hour(17)->minute(10)->timestamp;

        // No settlements after five PM but allow settlements file upload anytime
        // before that, we want to do it before 8 am as well as that allows us
        // some time for fixing things before settlement window opens.
        if (($this->mode === Mode::LIVE) and
            ($this->setlTime >= $fivePm))
        {
            return true;
        }

        return false;
    }
}
