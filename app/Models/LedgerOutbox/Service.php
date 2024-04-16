<?php

namespace RZP\Models\LedgerOutbox;

use Carbon\Carbon;
use Razorpay\Trace\Facades\Trace;
use RZP\Constants\Timezone;
use RZP\Jobs\Ledger\CreateMissingRefundTransactionsForReverseShadow;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\LedgerOutbox;
use RZP\Models\Ledger\ReverseShadow;
use function RZP\Console\Commands\laravelPatternToEdgeRoute;


class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new LedgerOutbox\Core;
    }

    //pg-ledger outbox cron retries journal and txn creation for non-deleted outbox entries in reverse-shadow mode
    public function retryFailedReverseShadowTransactions(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $limit = $input['limit'] ?? Constants::DEFAULT_LIMIT;

        $response = $this->core->retryFailedReverseShadowTransactions($limit);

        return $response;
    }

    // Below values defined in seconds
    const DEFAULT_START_TIME_FOR_SYNC_FLOW_TXN_CREATION = 86400;
    const DEFAULT_END_TIME_FOR_SYNC_FLOW_TXN_CREATION = 18000;

    public function createMissingTransactionsForReverseShadowRefunds(array $input)
    {
        $response = new Base\Collection();
        $failureIds = [];
        $successIds = [];

        if (isset($input['refunds_arr']) === true)
        {
            $refundsArrString = $input['refunds_arr'];

            $refundsArr = explode(',', $refundsArrString);

            for ($i = 0; $i < count($refundsArr); $i++)
            {
                try
                {
                    $currentRefundId = $refundsArr[$i];

                    CreateMissingRefundTransactionsForReverseShadow::dispatch($this->mode, $currentRefundId);

                    array_push($successIds, $currentRefundId);
                }
                catch(\Exception $e)
                {
                    $this->trace->traceException($e, [
                        "msg"       => $e->getMessage(),
                        "refund_id" => $refundsArr[$i],
                    ]);

                    array_push($failureIds, [$refundsArr[$i] => $e->getMessage()]);
                }
            }

            $response->push([
                "failures"  => $failureIds,
                "success"   => $successIds
            ]);

            return $response;
        }

        $currentTimestamp = time();

        $startDateTimestamp = strtotime('-7 days', $currentTimestamp);

        $endDateTimestamp = strtotime('-4 days', $currentTimestamp);

        $refundsWithMissingTxn = $this->core->fetchRefundWithMissingTransactions($startDateTimestamp, $endDateTimestamp);

        for ($i = 0; $i < count($refundsWithMissingTxn); $i++)
        {
            try
            {
                $currentRefund = $refundsWithMissingTxn[$i];
                $currentRefundId = $currentRefund["id"];

                CreateMissingRefundTransactionsForReverseShadow::dispatch($this->mode, $currentRefundId);

                array_push($successIds, $currentRefundId);
            }
            catch(\Exception $e)
            {

                $currentRefund = $refundsWithMissingTxn[$i];
                $currentRefundId = $currentRefund["id"];

                $this->trace->traceException($e, [
                    "msg"       => $e->getMessage(),
                    "refund_id" => $currentRefundId,
                ]);

                array_push($failureIds, [ $currentRefundId => $e->getMessage()]);
            }
        }

        $response->push([
            "failures" => $failureIds,
            "success" => $successIds
        ]);

        return $response;
    }

    public function createMissingTransactionsForReverseShadowAdjustments(array $input)
    {
        $response = new Base\Collection();

        $createdAtLessThanMinutes = (int) ($input['less_than'] ?? 60);
        $endDateTimestamp = Carbon::now(Timezone::IST)->subMinutes($createdAtLessThanMinutes)->getTimestamp();

        $createdAtGreaterThanMinutes = (int) ($input['greater_than'] ?? 24*60);
        $startDateTimestamp = Carbon::now(Timezone::IST)->subMinutes($createdAtGreaterThanMinutes)->getTimestamp();

        $txnResponse = $this->core->createMissingAdjustmentTransactions($startDateTimestamp, $endDateTimestamp, $input["transactor_ids"] );

        $response->push([
            "response" => $txnResponse,
        ]);

        return $response;
    }



    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(600);

        RuntimeManager::setMaxExecTime(600);
    }

    public function createLedgerOutboxPartition()
    {
        return $this->core->createLedgerOutboxPartition();
    }

    //pg-ledger outbox cron retries journal and txn creation for non-deleted outbox entries in reverse-shadow mode for transfer entities
    public function retryFailedReverseShadowTransferTransactions(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $limit = $input['limit'] ?? Constants::DEFAULT_LIMIT;

        $maxRetryCount = $input['retry_count'] ?? ReverseShadow\Constants::MAX_RETRY_COUNT_TRANSFER_CRON;

        $response = (new LedgerOutbox\Cron\Transfer\Core())->retryFailedReverseShadowTransferTransactions($limit, $maxRetryCount);
        return $response;
    }

    //pg-ledger outbox cron retries journal and txn creation for non-deleted outbox entries in reverse-shadow mode for settlement.ondemand entities
    public function retryFailedReverseShadowSettlementOndemandTransactions(array $input)
    {
        $this->increaseAllowedSystemLimits();

        $limit = $input['limit'] ?? Constants::DEFAULT_LIMIT;

        $response = (new LedgerOutbox\Cron\OndemandSettlement\Core())->retryFailedReverseShadowSettlementOndemandTransactions($limit);

        return $response;
    }

}

