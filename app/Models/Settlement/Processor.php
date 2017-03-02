<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Settlement\Batch\Entity as BatchSettlement;
use RZP\Models\Settlement\Kotak;
use RZP\Trace\TraceCode;

class Processor extends Base\Core
{
    use CommonTrait;

    protected $setlTime;

    protected $channel;

    protected $input;

    protected $mutex;

    protected $batchSettlement;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processFailedSettlements(array $input, $channel)
    {
        $this->preSettlementProcessing($input, $channel);

        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function () use ($input, $channel)
            {
                return $this->retryProcessFailedSettlements($channel);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    public function process(array $input, $channel, $schedule = true)
    {
        $this->preSettlementProcessing($input, $channel);

        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function () use ($input, $channel, $schedule)
            {
                return $this->processSettlements($input, $schedule);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $data;
    }

    protected function processSettlements($input, $schedule)
    {
        try
        {
            list($settlements, $txnCount, $setlAttempts) = $this->createSettlements($this->channel, $schedule);

            $response = $this->generateAndSendSettlementFile($settlements, $setlAttempts, $txnCount);

            $this->successNotification($response, $settlements, TraceCode::SETTLEMENT_INITIATED);
        }
        catch (\Exception $e)
        {
            $this->settlementFailure($this->channel, $e, TraceCode::SETTLEMENT_INITIATE_FAILED);
        }

        return $response;
    }

    // Is schedule needed here?
    protected function retryProcessFailedSettlements($channel): array
    {
        try
        {
            $settlements = $this->repo->settlement->getFailedSettlementsWithRelations($channel,
                ['setlTransactions', 'merchant', 'bankAccount']);

            $setlAttempts = new Base\PublicCollection;

            foreach ($settlements as $setl)
            {
                $merchant = $setl->merchant;

                $merchantSettler = new Merchant($merchant, $channel, $this->repo);

                $setlTxns = $setl->setlTransactions;

                $setlTxnsCount = $setlTxns->count();

                $bankTransferAtpt = $this->repo->transaction(
                    function() use ($merchantSettler, $setl, $setlTxns, $setlTxnsCount)
                {
                    $bankTransferAtpt = $merchantSettler->retryFailedSettlement($setl);

                    $this->createOrUpdateBatchSettlementForSettlement($setl, $setlTxnsCount);

                    $setl->batchSettlement()->associate($this->batchSettlement);

                    $this->repo->saveOrFail($setl);

                    $bankTransferAtpt->batchTransfer()->associate($this->batchSettlement);

                    $this->repo->saveOrFail($bankTransferAtpt);

                    $this->repo->transaction->settled($setlTxns, $this->setlTime);

                    return $bankTransferAtpt;
                });

                $setlAttempts->push($bankTransferAtpt);
            }

            $response = $this->generateAndSendSettlementFile($settlements, $setlAttempts, $setlTxnsCount);

            $this->successNotification($response, $settlements, TraceCode::SETTLEMENT_RETRIED);
        }
        catch (\Exception $e)
        {
            $this->settlementFailure($this->channel, $e, TraceCode::SETTLEMENT_RETRY_FAILED);
        }

        return $response;
    }

    protected function generateAndSendSettlementFile($settlements, $setlAttempts, $txnCount)
    {
        $data = [
                    'channel'               => $this->channel,
                    'count'                 => $settlements->count(),
                    'transaction_count'     => $txnCount,
        ];

        if ($setlAttempts->count() > 0)
        {
            list($urlText, $urlExcel) = $this->generateSettlementFile($setlAttempts);

            $this->updateBatchSettlementEntityUrls($urlText, $urlExcel);

            $data['settlement_text_file']  = $urlText;
            $data['settlement_excel_file'] = $urlExcel;
        }
        else
        {
            $data['message'] = 'No settlements found!';
        }

        return $data;
    }

    protected function createSettlements($channel, $schedule): array
    {
        if ($schedule === false)
        {
            $txns = $this->repo->transaction->fetchUnsettledTransactions($this->setlTime);

            list($settlements, $settledTxnsCount, $setlAttempts) =
                $this->processUnsettledTransactions($txns, $channel);
        }
        else
        {
            $schedules = $this->repo->schedule->fetchSchedulesWithDueRun($this->setlTime);

            $txns = $this->repo->transaction->fetchUnsettledTxnsForDueSchedules($this->setlTime);

            list($settlements, $settledTxnsCount, $setlAttempts) =
                $this->processUnsettledTransactions($txns, $channel);

            $schedules->callOnEveryItem('updateNextRun');

            $this->repo->saveOrFailCollection($schedules);

            $this->trace->info(TraceCode::SCHEDULE_NEXT_RUN_UPDATED, $schedules->getIds());
        }

        return [$settlements, $settledTxnsCount, $setlAttempts];
    }

    protected function processUnsettledTransactions($txns, $channel): array
    {
        $txns = $this->filterTransactionsForSettlement($txns, $channel);

        list($settlements, $settledTxnsCount, $setlAttempts) = $this->createSettlementsFromTxns($txns, $channel);

        return [$settlements, $settledTxnsCount, $setlAttempts];
    }

    protected function createBatchSettlementEntity($setl, $txnsCount)
    {
        $batchSettlement = new BatchSettlement;

        $input = [
            BatchSettlement::CHANNEL           => $setl->getChannel(),
            BatchSettlement::AMOUNT            => $setl->getAmount(),
            BatchSettlement::FEES              => $setl->getFees(),
            BatchSettlement::SERVICE_TAX       => $setl->getServiceTax(),
            BatchSettlement::SETTLEMENT_COUNT  => 1,
            BatchSettlement::TRANSACTION_COUNT => $txnsCount,
            BatchSettlement::INITIATED_AT      => time(),
            BatchSettlement::API_FEE           => 0,
            BatchSettlement::GATEWAY_FEE       => 0,
            BatchSettlement::URLS              => null,
        ];

        $batchSettlement->build($input);

        return $batchSettlement;
    }

    protected function createOrUpdateBatchSettlementForSettlement($setl, $txnsCount)
    {
        if ($this->batchSettlement === null)
        {
            $this->batchSettlement = $this->createBatchSettlementEntity($setl, $txnsCount);
        }
        else
        {
            $this->batchSettlement->incrementAmount($setl->getAmount());
            $this->batchSettlement->incrementFees($setl->getFees());
            $this->batchSettlement->incrementServiceTax($setl->getServiceTax());
            $this->batchSettlement->incrementSettlementCount();
            $this->batchSettlement->incrementTransactionCount($txnsCount);
        }

        $this->repo->saveOrFail($this->batchSettlement);
    }

    protected function updateBatchSettlementEntityUrls($urlText, $urlExcel)
    {
        $batchSettlement = $this->batchSettlement;

        $urls = [
            'kotak_settlement_txt'   => $urlText,
            'kotak_settlement_excel' => $urlExcel
        ];

        $batchSettlement->setUrls($urls);

        $this->repo->saveOrFail($batchSettlement);
    }

    protected function generateSettlementFile($setlAttempts)
    {
        $data = null;

        if ($this->channel === Channel::KOTAK)
        {
            $data = (new Kotak\NodalAccount)->generateSettlementFile($setlAttempts);
        }

        return $data;
    }

    protected function preSettlementProcessing(array $input, $channel)
    {
        $this->inititalizeVariables($input, $channel);

        list($shouldProcess, $message) = $this->shouldProcessSettlements();

        if ($shouldProcess === false)
        {
            return $message;
        }

        $this->increaseAllowedSystemLimits();
    }

    protected function inititalizeVariables(array $input, $channel)
    {
        $this->setlTime = Carbon::now('Asia/Kolkata')->timestamp;

        $this->input = $input;

        //set channel
        $this->channel = $channel;

        if ($channel === null)
        {
            $this->channel = Channel::KOTAK;
        }
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

    protected function checkInvalidSettlementTime()
    {
        // NEFT can be processed between 8am and 6 pm only, while batch file can
        // be uploaded anytime

        $sevenAm = Carbon::today('Asia/Kolkata')->hour(7)->timestamp;

        // Cron runs at 5.01pm.
        $fivePm = Carbon::today('Asia/Kolkata')->hour(17)->minute(10)->timestamp;

        if (($this->mode === Mode::LIVE) and
            (($this->setlTime <= $sevenAm) or
             ($this->setlTime >= $fivePm)))
        {
            return true;
        }

        return false;
    }
}
