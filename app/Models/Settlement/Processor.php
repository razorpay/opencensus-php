<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Batch\Entity as BatchFundTransfer;
use RZP\Models\FundTransfer\Batch\BatchFundTransferTrait;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Trace\TraceCode;

class Processor extends Base\Core
{
    use SettlementTrait;
    use BatchFundTransferTrait;

    protected $setlTime;

    protected $input;

    protected $mutex;

    const MUTEX_RESOURCE        = 'SETTLEMENT_PROCESSING_%s';

    const MUTEX_RETRY_RESOURCE  = 'SETTLEMENT_RETRY_%s';

    const MUTEX_LOCK_TIMEOUT    = 900;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function processFailedSettlements(array $input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_RETRY_REQUEST,
            $input
        );

        $this->preSettlementProcessing($input);

        list($shouldProcess, $data) = $this->shouldProcessSettlements();

        if ($shouldProcess === true)
        {
            $mutexResource = sprintf(self::MUTEX_RETRY_RESOURCE, $this->mode);

            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($input)
                {
                    return $this->retryProcessFailedSettlements();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

        return $data;
    }

    public function process(array $input, $channel)
    {
        $this->preSettlementProcessing($input);

        list($shouldProcess, $data) = $this->shouldProcessSettlements();

        if ($shouldProcess === true)
        {
            $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->mode);

            $data = $this->mutex->acquireAndRelease(
                $mutexResource,
                function () use ($channel)
                {
                    return $this->processSettlements($channel);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_SETTLEMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }

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
                $this->batchFundTransfer = null;

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

    protected function retryProcessFailedSettlements(): array
    {
        $response = [];
        $channel = null;

        try
        {
            (new Validator)->validateInput('retry', $this->input);

            $setlIds = $this->input['settlement_ids'];

            Entity::verifyIdAndStripSignMultiple($setlIds);

            $channels = $this->getArrayedChannels();

            foreach ($channels as $channel)
            {
                $response[$channel] = $this->retrySettlementsForChannel($setlIds, $channel);
            }
        }
        catch (\Exception $e)
        {
            $this->settlementFailure(null, $e, TraceCode::SETTLEMENT_RETRY_FAILED);
        }

        return $response;
    }

    protected function retrySettlementsForChannel($setlIds, $channel)
    {
        $setlAttempts = new Base\PublicCollection;

        $totalTxns = 0;

        $settlements = $this->repo->settlement->getFailedSettlementsForRetry($setlIds, $channel);

        foreach ($settlements as $setl)
        {
            $setlTxns = $setl->setlTransactions;

            $setlTxnsCount = $setlTxns->count();

            $merchantSettler = new Merchant($setl->merchant, $channel, $this->repo);

            list($setl, $bankTransferAtpt) = $this->repo->transaction(
                function() use ($merchantSettler, $setl, $setlTxns, $setlTxnsCount)
            {
                list($setl, $bankTransferAtpt) = $merchantSettler->retryFailedSettlement($setl);

                return $this->createAndupdateBatchEntities($setl, $setlTxnsCount, $bankTransferAtpt);
            });

            $setlAttempts->push($bankTransferAtpt);

            $totalTxns += $setlTxnsCount;
        }

        $response = $this->generateAndSendSettlementFile($settlements, $setlAttempts, $totalTxns, $channel);

        return $response;
    }

    protected function generateAndSendSettlementFile(
        $settlements,
        $setlAttempts,
        $txnCount,
        $channel,
        $h2h = true)
    {
        $returnData = [
            'count'             => $settlements->count(),
            'transaction_count' => $txnCount,
        ];

        if ($setlAttempts->count() > 0)
        {
            list($txtFileEntity, $excelFileEntity) =
                $this->generateSettlementFile($setlAttempts, $channel, $h2h);

            $txtFileDetails = $txtFileEntity->get();
            $excelFileDetails = $excelFileEntity->get();

            $txtUrl = $txtFileEntity->getUrl();
            $excelUrl = $excelFileEntity->getUrl();

            $urls = [
                'kotak_settlement_txt'   => $txtUrl,
                'kotak_settlement_excel' => $excelUrl,
            ];

            $this->updateFileDetailsInBatchFundTransferEntity(
                [
                    'urls' => $urls,
                    'txt_file_id' => $txtFileDetails['id'],
                    'excel_file_id' => $excelFileDetails['id'],
                ]);

            $slackData = $returnData;

            $this->successNotification($slackData, $settlements, TraceCode::SETTLEMENT_INITIATED);
            $returnData['settlement_text_file'] = $txtFileDetails;
            $returnData['settlement_excel_file'] = $excelFileDetails;
        }
        else
        {
            $returnData['message'] = 'No settlements found!';
        }

        return $returnData;
    }

    protected function createSettlements($channel): array
    {
        $txns = $this->repo->transaction->fetchUnsettledTransactions($this->setlTime, $channel);

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

    protected function generateSettlementFile($setlAttempts, $channel, $h2h)
    {
        $data = [null, null];

        if ($channel === Channel::KOTAK)
        {
            $data = (new Kotak\NodalAccount)->generateSettlementFile($setlAttempts, $h2h);
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
        $this->setlTime = Carbon::now()->getTimestamp();

        if (($this->mode === Mode::TEST) and
            (empty($input['testSettleTimeStamp']) === false))
        {
            $this->setlTime = $input['testSettleTimeStamp'];
        }

        $this->input = $input;
    }

    protected function shouldProcessSettlements()
    {
        if (($this->mode === Mode::TEST) and
            ($this->env === 'testing'))
        {
            return [true, null];
        }

        $today = Carbon::today(Timezone::IST);

        if (Holidays::isWorkingDay($today) === false)
        {
            return [false, Holidays::HOLIDAY_MESSAGE];
        }

        if ($this->isInvalidSettlementTime() === true)
        {
            return [false, ['message' => 'settlements cannot be processed now']];
        }

        return [true, null];
    }

    /**
     *  NEFT can be processed between 8am and 6 pm only, while batch file can be
     *  uploaded anytime.
     *
     * @return bool returns if settlement can be processed now
     */
    protected function isInvalidSettlementTime(): bool
    {
        // Cron runs at 5.01pm.
        $fivePm = Carbon::today(Timezone::IST)->hour(17)->minute(10)->getTimestamp();

        // No settlements after five PM but allow settlements file upload anytime
        // before that, we want to do it before 8 am as well as that allows us
        // some time for fixing things before settlement window opens.
        if (($this->setlTime >= $fivePm) and ($this->env !== 'testing'))
        {
            return true;
        }

        return false;
    }
}
