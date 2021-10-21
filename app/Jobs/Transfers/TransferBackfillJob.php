<?php

namespace RZP\Jobs\Transfers;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Service;

class TransferBackfillJob extends Job
{
    protected $merchantId;

    protected $startDate;

    protected $endDate;

    protected $queueConfigKey = 'batch';

    public $timeout = 5 * 3600; // 5 hours

    public function __construct(string $mode, string $merchantId, int $startDate, int $endDate)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->startDate = $startDate;

        $this->endDate = $endDate;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::TRANSFER_BACKFILL_JOB_RECEIVED,
            [
                'merchant_id'   => $this->merchantId,
                'start_date'    => $this->startDate,
                'end_date'      => $this->endDate,
            ]
        );

        $startTime = microtime(true);

        $transferCount = (new Service())->updateSettlementStatusAndErrorCode($this->merchantId, $this->startDate, $this->endDate);

        $endTime = microtime(true);

        $this->trace->info(
            TraceCode::TRANSFER_BACKFILL_JOB_COMPLETED,
            [
                'merchant_id'       => $this->merchantId,
                'start_date'        => $this->startDate,
                'end_date'          => $this->endDate,
                'transfer_count'    => $transferCount,
                'time_taken'        => $endTime - $startTime,
            ]
        );

        $this->delete();
    }
}
