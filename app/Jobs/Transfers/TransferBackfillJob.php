<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service;

class TransferBackfillJob extends Job
{
    protected $merchantId;

    protected $queueConfigKey = 'batch';

    public $timeout = 3600;

    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::TRANSFER_BACKFILL_JOB_RECEIVED,
            [
                'merchant_id' => $this->merchantId,
            ]
        );

        $startTime = microtime(true);

        $transferCount = (new Service())->updateSettlementStatusAndErrorCode($this->merchantId);

        $endTime = microtime(true);

        $this->trace->info(
            TraceCode::TRANSFER_BACKFILL_JOB_COMPLETED,
            [
                'merchant_id'       => $this->merchantId,
                'transfer_count'    => $transferCount,
                'time_taken'        => $endTime - $startTime,
            ]
        );

        $this->delete();
    }
}
