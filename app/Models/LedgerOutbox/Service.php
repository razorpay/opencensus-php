<?php

namespace RZP\Models\LedgerOutbox;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;
use RZP\Models\LedgerOutbox;


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

