<?php

namespace RZP\Models\LedgerOutbox;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\LedgerOutbox;
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

                    $refund = $this->repo->refund->findOrFail($currentRefundId);

                    $this->core->validateAndCreateMissingRefundTransaction($refund);

                    array_push($successIds, $currentRefundId);
                }
                catch(\Exception $e)
                {
                    $this->trace->traceException($e, [
                        "msg"   => $e->getMessage(),
                        "refund_id" => $refundsArr[$i],
                    ]);

                    array_push($failureIds, [$refundsArr[$i] => $e->getMessage()]);
                }

            }

            $response->push([
                "failures" => $failureIds,
                "success" => $successIds
            ]);

            return $response;
        }

        $this->trace->info(TraceCode::NO_REFUND_IN_INPUT, $input);

        return [];
    }

    public function createMissingTransactionsForReverseShadowAdjustments(array $input)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $startDate = (isset($input["start_date"]) === true) ? $input["start_date"] : $currentTime - self::DEFAULT_START_TIME_FOR_SYNC_FLOW_TXN_CREATION;
        $endDate = (isset($input["end_date"]) === true) ? $input["end_date"] : $currentTime - self::DEFAULT_END_TIME_FOR_SYNC_FLOW_TXN_CREATION;

        $transactorIds = [];

        if(isset($input["transactor_ids"]) === true)
        {
            $transactorIds = explode(',', $input["transactor_ids"]);
        }

        $responses = $this->core->createMissingAdjustmentTransactions($startDate, $endDate, $transactorIds);
        return $responses;
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

        $response = (new LedgerOutbox\Cron\Transfer\Core())->retryFailedReverseShadowTransferTransactions($limit);
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

